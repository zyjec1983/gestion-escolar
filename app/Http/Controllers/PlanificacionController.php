<?php

namespace App\Http\Controllers;

use App\Models\ClaseDocente;
use App\Models\Curso;
use App\Models\Institucion;
use App\Models\Materia;
use App\Models\Paralelo;
use App\Models\PeriodoLectivo;
use App\Models\PlanificacionAuditoria;
use App\Models\Unidad;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PlanificacionController extends Controller
{
    private const ROLES_AUTORIDAD = ['ADMIN', 'SECRETARIA', 'RECTORA', 'VICERRECTORA'];

    private const ROLES_EDICION = ['DOCENTE', 'ADMIN', 'SECRETARIA', 'RECTORA', 'VICERRECTORA'];

    private const MINUTOS_AUTORIZACION = 30;

    public function index(Request $request)
    {
        $filtros = $request->validate([
            'materia_id' => ['nullable', 'integer', 'exists:materias,id'],
            'curso_id' => ['nullable', 'integer', 'exists:cursos,id'],
            'paralelo_id' => ['nullable', 'integer', 'exists:paralelos,id'],
            'docente_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $query = Unidad::with([
            'materia',
            'claseDocente.curso',
            'claseDocente.paralelo',
            'claseDocente.docente',
            'periodoLectivo',
            'creador',
        ])->latest('fecha_inicio');

        $this->aplicarFiltros($query, $filtros);

        $unidades = $query->get();

        $planificaciones = $unidades
            ->sortBy(fn (Unidad $unidad) => $unidad->materia?->nombre ?? 'Sin materia')
            ->groupBy(fn (Unidad $unidad) => $unidad->materia?->nombre ?? 'Sin materia');

        $materias = Materia::query()->whereHas('unidades')->orderBy('nombre')->get();
        $cursos = Curso::query()->whereHas('clasesDocentes.unidades')->enOrdenEscolar()->get();
        $paralelos = Paralelo::query()->whereHas('clasesDocentes.unidades')->orderBy('nombre')->get();
        $docentes = User::query()
            ->where('rol', 'DOCENTE')
            ->whereHas('clasesDocentes.unidades')
            ->orderBy('apellidos')
            ->orderBy('name')
            ->get();

        $paralelosPorCurso = ClaseDocente::query()
            ->select('curso_id', 'paralelo_id')
            ->distinct()
            ->get()
            ->groupBy('curso_id')
            ->map(fn ($items) => $items->pluck('paralelo_id')->unique()->values());

        $historialPorGrupo = PlanificacionAuditoria::query()
            ->where(function ($consulta) use ($unidades) {
                $consulta->whereIn('unidad_id', $unidades->pluck('id'));

                $grupos = $unidades->pluck('grupo_id')->filter()->unique();

                if ($grupos->isNotEmpty()) {
                    $consulta->orWhereIn('grupo_id', $grupos);
                }
            })
            ->latest()
            ->get()
            ->groupBy(fn (PlanificacionAuditoria $auditoria) => $auditoria->grupo_id ?: 'unidad-'.$auditoria->unidad_id);

        $historial = fn (Unidad $unidad) => $historialPorGrupo->get(
            $unidad->grupo_id ?: 'unidad-'.$unidad->id,
            collect()
        );

        $datosModal = $unidades->mapWithKeys(function (Unidad $unidad) use ($historial) {
            return [$unidad->id => [
                'titulo' => $unidad->titulo,
                'descripcion' => $unidad->descripcion,
                'materia' => $unidad->materia?->nombre,
                'nivelParalelo' => $unidad->nivelParalelo(),
                'docente' => $unidad->docenteTitular()?->nombreCompleto(),
                'quimestre' => $unidad->quimestre,
                'parcial' => $unidad->parcial,
                'fechaInicio' => $unidad->fecha_inicio?->format('d/m/Y'),
                'fechaFin' => $unidad->fecha_fin?->format('d/m/Y'),
                'duracion' => $unidad->duracionDias(),
                'creadoPor' => $unidad->creador?->nombreCompleto(),
                'periodo' => $unidad->periodoLectivo?->nombre,
                'printUrl' => route('planificaciones.print', $unidad),
                'auditorias' => $historial($unidad)->map(fn (PlanificacionAuditoria $auditoria) => [
                    'fecha' => $auditoria->created_at?->format('d/m/Y H:i:s'),
                    'usuario' => $auditoria->usuario_nombre,
                    'rol' => $auditoria->usuario_rol,
                    'accion' => $auditoria->accionLabel(),
                    'motivo' => $auditoria->motivo,
                ])->values(),
            ]];
        });

        return view('planificaciones.index', compact(
            'planificaciones',
            'materias',
            'cursos',
            'paralelos',
            'docentes',
            'filtros',
            'paralelosPorCurso',
            'historial',
            'datosModal'
        ));
    }

    public function create(Request $request)
    {
        $this->autorizarDocente($request);

        $combos = ClaseDocente::with(['materia', 'curso', 'paralelo'])
            ->where('user_id', $request->user()->id)
            ->get()
            ->groupBy(fn (ClaseDocente $clase) => $clase->materia_id.'-'.$clase->curso_id)
            ->map(function ($items) {
                $primera = $items->first();

                return [
                    'clave' => $primera->materia_id.'-'.$primera->curso_id,
                    'materia_id' => $primera->materia_id,
                    'materia' => $primera->materia->nombre,
                    'curso_id' => $primera->curso_id,
                    'curso' => $primera->curso->nombre,
                    'paralelos' => $items->map(fn (ClaseDocente $clase) => [
                        'id' => $clase->paralelo_id,
                        'nombre' => $clase->paralelo->nombre,
                        'clase_docente_id' => $clase->id,
                    ])->values(),
                ];
            })
            ->values()
            ->sortBy(fn ($combo) => $combo['curso'].' '.$combo['materia'])
            ->values();

        $quimestres = ['Q1' => 'Primer Quimestre', 'Q2' => 'Segundo Quimestre'];
        $parciales = ['P1' => 'Parcial 1', 'P2' => 'Parcial 2', 'P3' => 'Parcial 3'];
        $periodo = PeriodoLectivo::where('activo', true)->first();

        return view('planificaciones.create', compact('combos', 'quimestres', 'parciales', 'periodo'));
    }

    public function store(Request $request)
    {
        $this->autorizarDocente($request);

        $validated = $request->validate([
            'materia_id' => ['required', 'exists:materias,id'],
            'curso_id' => ['required', 'exists:cursos,id', function ($atributo, $valor, $fail) use ($request) {
                if (! ClaseDocente::where('user_id', $request->user()->id)
                    ->where('materia_id', $request->input('materia_id'))
                    ->where('curso_id', $valor)
                    ->exists()) {
                    $fail('No existe una clase para esa materia y nivel.');
                }
            }],
            'paralelo_ids' => ['required', 'array', 'min:1', function ($atributo, $valor, $fail) use ($request) {
                $disponibles = ClaseDocente::where('user_id', $request->user()->id)
                    ->where('materia_id', $request->input('materia_id'))
                    ->where('curso_id', $request->input('curso_id'))
                    ->pluck('paralelo_id')
                    ->unique();

                if ($disponibles->intersect($valor)->count() !== count(array_unique($valor))) {
                    $fail('Uno de los paralelos seleccionados no pertenece a ese nivel y materia.');
                }
            }],
            'paralelo_ids.*' => 'distinct|exists:paralelos,id',
            'titulo' => 'required|string|max:255',
            'descripcion' => 'required|string',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
            'quimestre' => 'required|in:Q1,Q2',
            'parcial' => 'required|in:P1,P2,P3',
        ]);

        $periodo = PeriodoLectivo::where('activo', true)->first();
        $grupoId = (string) Str::uuid();

        $clases = ClaseDocente::where('user_id', $request->user()->id)
            ->where('materia_id', $validated['materia_id'])
            ->where('curso_id', $validated['curso_id'])
            ->whereIn('paralelo_id', $validated['paralelo_ids'])
            ->get();

        $creadas = 0;

        foreach ($validated['paralelo_ids'] as $paraleloId) {
            $clase = $clases->firstWhere('paralelo_id', $paraleloId);

            Unidad::create([
                'materia_id' => $validated['materia_id'],
                'clase_docente_id' => $clase?->id,
                'titulo' => $validated['titulo'],
                'descripcion' => $validated['descripcion'],
                'fecha_inicio' => $validated['fecha_inicio'],
                'fecha_fin' => $validated['fecha_fin'],
                'quimestre' => $validated['quimestre'],
                'parcial' => $validated['parcial'],
                'periodo_lectivo_id' => $periodo?->id,
                'creado_por' => $request->user()->id,
                'grupo_id' => $grupoId,
            ]);

            $creadas++;
        }

        return redirect()->route('planificaciones.index')
            ->with('success', "Planificación guardada y aplicada a {$creadas} paralelo(s).");
    }

    public function edit(Request $request, Unidad $unidad)
    {
        $intervencion = $this->resolverAccesoEdicion($request, $unidad);

        $grupo = $unidad->grupo_id
            ? Unidad::where('grupo_id', $unidad->grupo_id)
                ->with(['claseDocente.curso', 'claseDocente.paralelo'])
                ->get()
            : collect([$unidad])->load(['claseDocente.curso', 'claseDocente.paralelo']);

        $quimestres = ['Q1' => 'Primer Quimestre', 'Q2' => 'Segundo Quimestre'];
        $parciales = ['P1' => 'Parcial 1', 'P2' => 'Parcial 2', 'P3' => 'Parcial 3'];

        return view('planificaciones.edit', compact('unidad', 'grupo', 'quimestres', 'parciales', 'intervencion'));
    }

    public function update(Request $request, Unidad $unidad)
    {
        $intervencion = $this->resolverAccesoEdicion($request, $unidad);

        $validated = $request->validate([
            'titulo' => 'required|string|max:255',
            'descripcion' => 'required|string',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
            'quimestre' => 'required|in:Q1,Q2',
            'parcial' => 'required|in:P1,P2,P3',
        ]);

        $grupo = $unidad->grupo_id
            ? Unidad::where('grupo_id', $unidad->grupo_id)->get()
            : collect([$unidad]);

        $datosAnteriores = $this->snapshot($unidad);
        $usuario = $request->user();

        DB::transaction(function () use ($grupo, $validated, $unidad, $usuario, $intervencion, $datosAnteriores, $request) {
            $grupo->each->update($validated);

            if ($intervencion) {
                PlanificacionAuditoria::create([
                    'unidad_id' => $unidad->id,
                    'grupo_id' => $unidad->grupo_id,
                    'unidad_titulo' => $validated['titulo'],
                    'usuario_id' => $usuario->id,
                    'usuario_nombre' => $usuario->nombreCompleto(),
                    'usuario_rol' => $usuario->rol,
                    'accion' => PlanificacionAuditoria::ACCION_ACTUALIZACION,
                    'motivo' => $intervencion['motivo'],
                    'datos_anteriores' => $datosAnteriores,
                    'datos_nuevos' => $this->snapshot($unidad->fresh()),
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]);
            }
        });

        if ($intervencion) {
            $request->session()->forget($this->claveIntervencion($unidad));
        }

        $total = $grupo->count();

        return redirect()->route('planificaciones.index')
            ->with('success', $intervencion
                ? "Intervención administrativa registrada. Planificación actualizada en {$total} paralelo(s)."
                : ($total > 1
                    ? "Planificación actualizada y aplicada a {$total} paralelo(s)."
                    : 'Planificación actualizada correctamente.'));
    }

    public function destroy(Request $request, Unidad $unidad)
    {
        $this->autorizarDocentePropietario($request, $unidad);

        $grupo = $unidad->grupo_id
            ? Unidad::where('grupo_id', $unidad->grupo_id)->get()
            : collect([$unidad]);

        $total = $grupo->count();
        $grupo->each->delete();

        return redirect()->route('planificaciones.index')
            ->with('success', $total > 1
                ? "Planificación eliminada en {$total} paralelo(s)."
                : 'Planificación eliminada.');
    }

    public function intervencion(Request $request, Unidad $unidad)
    {
        $usuario = $request->user();

        abort_unless($this->esAutoridad($usuario), 403);

        $validated = $request->validate([
            'motivo' => ['required', 'string', 'min:10', 'max:1000'],
        ], [
            'motivo.required' => 'Debe especificar el motivo de la intervención.',
            'motivo.min' => 'El motivo debe tener al menos 10 caracteres.',
            'motivo.max' => 'El motivo no puede superar los 1000 caracteres.',
        ]);

        PlanificacionAuditoria::create([
            'unidad_id' => $unidad->id,
            'grupo_id' => $unidad->grupo_id,
            'unidad_titulo' => $unidad->titulo,
            'usuario_id' => $usuario->id,
            'usuario_nombre' => $usuario->nombreCompleto(),
            'usuario_rol' => $usuario->rol,
            'accion' => PlanificacionAuditoria::ACCION_AUTORIZACION,
            'motivo' => $validated['motivo'],
            'datos_anteriores' => $this->snapshot($unidad),
            'datos_nuevos' => null,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        $request->session()->put($this->claveIntervencion($unidad), [
            'motivo' => $validated['motivo'],
            'usuario_id' => $usuario->id,
            'autorizada_en' => now()->timestamp,
            'expira_en' => now()->addMinutes(self::MINUTOS_AUTORIZACION)->timestamp,
        ]);

        return redirect()->route('planificaciones.edit', $unidad)
            ->with('success', 'Intervención administrativa autorizada. Ya puede editar la planificación.');
    }

    public function print(Request $request, Unidad $unidad)
    {
        abort_unless(in_array($request->user()->rol, self::ROLES_EDICION, true), 403);

        if ($request->user()->rol === 'DOCENTE') {
            $this->autorizarDocentePropietario($request, $unidad);
        }

        $unidad->load([
            'materia',
            'claseDocente.curso',
            'claseDocente.paralelo',
            'claseDocente.docente',
            'periodoLectivo',
            'creador',
        ]);

        $grupo = $unidad->grupo_id
            ? Unidad::where('grupo_id', $unidad->grupo_id)
                ->with(['claseDocente.curso', 'claseDocente.paralelo', 'claseDocente.docente'])
                ->get()
            : collect([$unidad]);

        $auditorias = $this->historialAuditoria($unidad);
        $institucion = Institucion::first();

        return view('planificaciones.print', compact('unidad', 'grupo', 'auditorias', 'institucion'));
    }

    public function bitacora(Request $request)
    {
        abort_unless($this->esAutoridad($request->user()), 403);

        $acciones = [
            PlanificacionAuditoria::ACCION_AUTORIZACION => 'Intervención autorizada',
            PlanificacionAuditoria::ACCION_ACTUALIZACION => 'Actualización administrativa',
        ];

        $filtros = $request->validate([
            'usuario_id' => ['nullable', 'integer', 'exists:users,id'],
            'accion' => ['nullable', 'in:'.implode(',', array_keys($acciones))],
            'desde' => ['nullable', 'date'],
            'hasta' => ['nullable', 'date'],
            'q' => ['nullable', 'string', 'max:255'],
        ]);

        if (filled($filtros['desde'] ?? null) && filled($filtros['hasta'] ?? null)
            && $filtros['hasta'] < $filtros['desde']) {
            throw ValidationException::withMessages([
                'hasta' => 'La fecha hasta debe ser posterior o igual a la fecha desde.',
            ]);
        }

        $query = PlanificacionAuditoria::query()->with('usuario')->latest();

        $query->when(
            filled($filtros['usuario_id'] ?? null),
            fn (Builder $consulta) => $consulta->where('usuario_id', $filtros['usuario_id'])
        )->when(
            filled($filtros['accion'] ?? null),
            fn (Builder $consulta) => $consulta->where('accion', $filtros['accion'])
        )->when(
            filled($filtros['desde'] ?? null),
            fn (Builder $consulta) => $consulta->whereDate('created_at', '>=', $filtros['desde'])
        )->when(
            filled($filtros['hasta'] ?? null),
            fn (Builder $consulta) => $consulta->whereDate('created_at', '<=', $filtros['hasta'])
        )->when(
            filled($filtros['q'] ?? null),
            function (Builder $consulta) use ($filtros) {
                $texto = '%'.trim($filtros['q']).'%';

                $consulta->where(function (Builder $subconsulta) use ($texto) {
                    $subconsulta->where('unidad_titulo', 'like', $texto)
                        ->orWhere('motivo', 'like', $texto);
                });
            }
        );

        $auditorias = $query->paginate(25)->withQueryString();

        $usuarios = User::query()
            ->whereIn('id', PlanificacionAuditoria::query()->distinct()->pluck('usuario_id')->filter())
            ->orderBy('apellidos')
            ->orderBy('name')
            ->get();

        return view('planificaciones.bitacora', compact('auditorias', 'usuarios', 'acciones'));
    }

    private function aplicarFiltros(Builder $query, array $filtros): void
    {
        $query->when(
            filled($filtros['materia_id'] ?? null),
            fn (Builder $consulta) => $consulta->where('materia_id', $filtros['materia_id'])
        )->when(
            filled($filtros['curso_id'] ?? null),
            fn (Builder $consulta) => $consulta->whereHas(
                'claseDocente',
                fn (Builder $subconsulta) => $subconsulta->where('curso_id', $filtros['curso_id'])
            )
        )->when(
            filled($filtros['paralelo_id'] ?? null),
            fn (Builder $consulta) => $consulta->whereHas(
                'claseDocente',
                fn (Builder $subconsulta) => $subconsulta->where('paralelo_id', $filtros['paralelo_id'])
            )
        )->when(
            filled($filtros['docente_id'] ?? null),
            fn (Builder $consulta) => $consulta->whereHas(
                'claseDocente',
                fn (Builder $subconsulta) => $subconsulta->where('user_id', $filtros['docente_id'])
            )
        );
    }

    /**
     * Devuelve la autorizacion vigente para una autoridad o lanza 403.
     * Un docente solo puede editar sus propias planificaciones.
     */
    private function resolverAccesoEdicion(Request $request, Unidad $unidad): ?array
    {
        if ($this->esAutoridad($request->user())) {
            $autorizacion = $this->autorizacionVigente($request, $unidad);

            abort_unless($autorizacion, 403, 'Debe autorizar una intervención administrativa con un motivo.');

            return $autorizacion;
        }

        $this->autorizarDocentePropietario($request, $unidad);

        return null;
    }

    private function autorizarDocente(Request $request): void
    {
        abort_unless($request->user()->rol === 'DOCENTE', 403);
    }

    private function autorizarDocentePropietario(Request $request, Unidad $unidad): void
    {
        abort_unless($request->user()->rol === 'DOCENTE', 403);

        $esPropietario = $unidad->creado_por === $request->user()->id
            || $unidad->claseDocente?->user_id === $request->user()->id;

        abort_unless($esPropietario, 403, 'Solo el docente titular puede modificar esta planificación.');
    }

    private function esAutoridad(User $usuario): bool
    {
        return in_array($usuario->rol, self::ROLES_AUTORIDAD, true);
    }

    private function autorizacionVigente(Request $request, Unidad $unidad): ?array
    {
        $clave = $this->claveIntervencion($unidad);
        $autorizacion = $request->session()->get($clave);

        if (! is_array($autorizacion)) {
            return null;
        }

        if (($autorizacion['usuario_id'] ?? null) !== $request->user()->id) {
            return null;
        }

        if (($autorizacion['expira_en'] ?? 0) < now()->timestamp) {
            $request->session()->forget($clave);

            return null;
        }

        return $autorizacion;
    }

    private function claveIntervencion(Unidad $unidad): string
    {
        return 'intervencion_planificacion_'.($unidad->grupo_id ?: 'unidad_'.$unidad->id);
    }

    private function snapshot(Unidad $unidad): array
    {
        return [
            'titulo' => $unidad->titulo,
            'descripcion' => $unidad->descripcion,
            'fecha_inicio' => $unidad->fecha_inicio?->toDateString(),
            'fecha_fin' => $unidad->fecha_fin?->toDateString(),
            'quimestre' => $unidad->quimestre,
            'parcial' => $unidad->parcial,
            'materia_id' => $unidad->materia_id,
            'clase_docente_id' => $unidad->clase_docente_id,
            'grupo_id' => $unidad->grupo_id,
        ];
    }

    private function historialAuditoria(Unidad $unidad)
    {
        return PlanificacionAuditoria::query()
            ->where(function (Builder $consulta) use ($unidad) {
                $consulta->where('unidad_id', $unidad->id);

                if ($unidad->grupo_id) {
                    $consulta->orWhere('grupo_id', $unidad->grupo_id);
                }
            })
            ->latest()
            ->get();
    }
}
