<?php

namespace App\Http\Controllers;

use App\Http\Resources\ClaseCollection;
use App\Http\Resources\InicioClaseResource;
use App\Models\Agenda;
use App\Models\Alumno;
use App\Models\ArchivoAdjunto;
use App\Models\AsignacionGrupo;
use App\Models\AsignacionGrupoAlumno;
use App\Models\ClaseDocente;
use App\Models\Material;
use App\Models\TipoActividad;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class AulaVirtualController extends Controller
{
    private const PALETA_GRUPOS = [
        '#FFADAD', '#FFC9B6', '#FFD6A5', '#FDFFB6', '#CAFFBF', '#B5E48C',
        '#9BF6FF', '#A0C4FF', '#BDB2FF', '#FFC6FF', '#FFD97D', '#FAD2E1',
        '#CDB4DB', '#A8DADC', '#DDBEA9', '#C9ADA7',
    ];

    public function index(Request $request)
    {
        $this->verificarRolAula($request->user());

        $clases = $this->clasesParaUsuario($request->user());

        return view('clases.index', (new ClaseCollection($clases))->resolve($request));
    }

    public function show($id, Request $request)
    {
        $this->verificarRolAula($request->user());

        $clase = ClaseDocente::with(['curso', 'paralelo', 'materia.unidades'])->findOrFail($id);

        $this->verificarAcceso($clase, $request->user());

        $data = $this->dataAula($clase, $request, 'inicio');

        return view('aula.virtual', $data);
    }

    public function agenda($id, Request $request)
    {
        $this->verificarRolAula($request->user());

        $clase = ClaseDocente::with(['curso', 'paralelo', 'materia'])->findOrFail($id);

        $this->verificarAcceso($clase, $request->user());

        $data = $this->dataAula($clase, $request, 'agenda');

        $data['clase'] = $clase;
        $data['agendas'] = $clase->agendas()
            ->with(['tipoActividad', 'insumoEvaluacion', 'unidad', 'archivosAdjuntos', 'alumnos'])
            ->orderByDesc('fecha_inicio')
            ->get();
        $data['unidades'] = InicioClaseResource::unidadesDeClase($clase);
        $data['tipo_actividades'] = TipoActividad::orderBy('nombre')->get();
        $data['insumos'] = $clase->insumosEvaluacion()->orderBy('nombre')->get();
        $data['alumnos_inscritos'] = $this->alumnosInscritos($clase);
        $data['es_docente'] = $request->user()->rol === 'DOCENTE';
        $data['checkbox_estudiantes'] = $request->user()->rol === 'DOCENTE';

        return view('aula.agenda', $data);
    }

    public function storeAgenda($id, Request $request)
    {
        $clase = ClaseDocente::findOrFail($id);

        $this->verificarAcceso($clase, $request->user());
        $this->verificarDocente($request->user(), $clase);

        $validated = $request->validate([
            'tipo_actividad_id' => 'required|exists:tipo_actividades,codigo',
            'unidad_id' => 'nullable|exists:unidades,id',
            'insumo_evaluacion_id' => 'nullable|exists:insumos_evaluacion,id',
            'titulo_agenda' => 'required|string|max:255',
            'descripcion_instrucciones_html' => 'nullable|string',
            'enlace' => 'nullable|url|max:2048',
            'fecha_inicio' => 'required|date',
            'fecha_entrega_limite' => 'nullable|date|after:fecha_inicio',
            'adjunto' => 'nullable|file|max:5120',
            'alumnos' => 'required|array|min:1',
            'alumnos.*' => 'integer',
            'agenda_id' => 'nullable|integer',
        ]);

        if (($validated['unidad_id'] ?? null) && ! InicioClaseResource::unidadesDeClase($clase)->contains('id', (int) $validated['unidad_id'])) {
            abort(403, 'Unidad de planificación inválida para esta clase.');
        }

        $inscritos = $this->alumnosInscritos($clase)->pluck('id')->map(fn ($mId) => (int) $mId)->all();
        $destinos = array_values(array_unique(array_map('intval', $validated['alumnos'])));
        abort_unless($destinos === array_intersect($destinos, $inscritos), 403, 'Estudiantes inválidos para esta clase.');

        $tipoActividad = TipoActividad::where('codigo', $validated['tipo_actividad_id'])->firstOrFail();

        $agendaId = $request->integer('agenda_id');
        if ($agendaId) {
            $agenda = Agenda::where('clase_docente_id', $clase->id)->findOrFail($agendaId);
        } else {
            $agenda = new Agenda(['clase_docente_id' => $clase->id]);
        }

        $agenda->fill([
            'tipo_actividad_id' => $tipoActividad->id,
            'insumo_evaluacion_id' => $validated['insumo_evaluacion_id'] ?? null,
            'unidad_id' => $validated['unidad_id'] ?? null,
            'titulo_agenda' => $validated['titulo_agenda'],
            'descripcion_instrucciones_html' => $validated['descripcion_instrucciones_html'] ?? null,
            'enlace' => $validated['enlace'] ?? null,
            'fecha_inicio' => $validated['fecha_inicio'],
            'fecha_entrega_limite' => $validated['fecha_entrega_limite'] ?? null,
            'enviar_a_todos' => count($destinos) === count($inscritos),
        ])->save();

        $agenda->alumnos()->sync($destinos);

        if ($request->hasFile('adjunto')) {
            foreach ($agenda->archivosAdjuntos as $viejo) {
                Storage::disk('public')->delete(str_replace('/storage/', '', $viejo->url));
                $viejo->delete();
            }

            $archivo = $request->file('adjunto');
            $ruta = $archivo->store('agendas/'.$clase->id, 'public');

            ArchivoAdjunto::create([
                'attachable_type' => Agenda::class,
                'attachable_id' => $agenda->id,
                'nombre' => $archivo->getClientOriginalName(),
                'url' => Storage::url($ruta),
            ]);
        }

        return redirect()->route('clases.agenda', $clase->id)
            ->with('success', $agendaId ? 'Actividad de agenda actualizada correctamente.' : 'Actividad de agenda enviada correctamente.');
    }

    public function editAgenda($id, Agenda $agenda, Request $request)
    {
        $clase = ClaseDocente::findOrFail($id);

        $this->verificarAcceso($clase, $request->user());
        $this->verificarDocente($request->user(), $clase);
        abort_if($agenda->clase_docente_id !== $clase->id, 403, 'Actividad no pertenece a esta clase.');

        return response()->json([
            'id' => $agenda->id,
            'titulo_agenda' => $agenda->titulo_agenda,
            'tipo_actividad_id' => $agenda->tipoActividad?->codigo,
            'unidad_id' => $agenda->unidad_id,
            'insumo_evaluacion_id' => $agenda->insumo_evaluacion_id,
            'fecha_inicio' => $agenda->fecha_inicio?->format('Y-m-d\TH:i'),
            'fecha_entrega_limite' => $agenda->fecha_entrega_limite?->format('Y-m-d\TH:i'),
            'descripcion_instrucciones_html' => $agenda->descripcion_instrucciones_html,
            'enlace' => $agenda->enlace,
            'tiene_adjunto' => $agenda->archivosAdjuntos->isNotEmpty(),
            'alumnos' => $agenda->alumnos->pluck('id')->all(),
        ]);
    }

    public function destroyAgenda($id, Agenda $agenda, Request $request)
    {
        $clase = ClaseDocente::findOrFail($id);

        $this->verificarAcceso($clase, $request->user());
        $this->verificarDocente($request->user(), $clase);
        abort_if($agenda->clase_docente_id !== $clase->id, 403, 'Actividad no pertenece a esta clase.');

        foreach ($agenda->archivosAdjuntos as $adjunto) {
            Storage::disk('public')->delete(str_replace('/storage/', '', $adjunto->url));
            $adjunto->delete();
        }
        $agenda->delete();

        return redirect()->route('clases.agenda', $clase->id)->with('success', 'Actividad de agenda eliminada.');
    }

    public function materiales($id, Request $request)
    {
        $clase = ClaseDocente::with(['curso', 'paralelo', 'materia'])->findOrFail($id);

        $this->verificarAcceso($clase, $request->user());

        $data = $this->dataAula($clase, $request, 'materiales');

        $data['clase'] = $clase;
        $data['materiales'] = $clase->materiales()
            ->with('subidor')
            ->latest()
            ->get();

        return view('aula.materiales', $data);
    }

    public function storeMaterial($id, Request $request)
    {
        $clase = ClaseDocente::findOrFail($id);

        $this->verificarAcceso($clase, $request->user());
        $this->verificarDocente($request->user(), $clase);

        $validated = $request->validate([
            'titulo' => 'required|string|max:255',
            'descripcion' => 'nullable|string|max:1000',
            'archivo' => 'required|file|max:20480',
        ]);

        $archivo = $request->file('archivo');
        $ruta = $archivo->store('materiales/'.$clase->id, 'public');

        Material::create([
            'clase_docente_id' => $clase->id,
            'titulo' => $validated['titulo'],
            'descripcion' => $validated['descripcion'] ?? null,
            'nombre_archivo' => $archivo->getClientOriginalName(),
            'url' => Storage::url($ruta),
            'subido_por' => $request->user()->id,
        ]);

        return redirect()->route('clases.materiales', $clase->id)->with('success', 'Material subido correctamente.');
    }

    public function destroyMaterial($id, Material $material, Request $request)
    {
        $clase = ClaseDocente::findOrFail($id);

        $this->verificarAcceso($clase, $request->user());
        $this->verificarDocente($request->user(), $clase);
        abort_if($material->clase_docente_id !== $clase->id, 403, 'Material no pertenece a esta clase.');

        Storage::disk('public')->delete(str_replace('/storage/', '', $material->url));
        $material->delete();

        return redirect()->route('clases.materiales', $clase->id)->with('success', 'Material eliminado.');
    }

    public function grupos($id, Request $request)
    {
        $clase = ClaseDocente::with(['curso', 'paralelo', 'materia'])->findOrFail($id);

        $this->verificarAcceso($clase, $request->user());

        $data = $this->dataAula($clase, $request, 'grupos');

        $data['clase'] = $clase;
        $data['paleta'] = self::PALETA_GRUPOS;
        $data['asignacion'] = $clase->asignacionGrupos()
            ->with(['integrantes.alumno'])
            ->latest()
            ->first();

        if ($data['asignacion']) {
            $integrantes = $data['asignacion']->integrantes->sortBy(
                fn ($i) => [$i->grupo, mb_strtolower(trim((string) $i->alumno->apellidos.' '.$i->alumno->nombres))]
            )->map(function ($integrante) {
                $integrante->nombre_corto = $this->nombreCorto($integrante->alumno->apellidos, $integrante->alumno->nombres);

                return $integrante;
            });
            $data['grupos'] = $integrantes->groupBy('grupo')->sortKeys();
            $data['total_estudiantes'] = $integrantes->count();
        } else {
            $data['grupos'] = collect();
            $data['total_estudiantes'] = 0;
        }

        return view('aula.grupos', $data);
    }

    public function storeGrupos($id, Request $request)
    {
        $clase = ClaseDocente::findOrFail($id);

        $this->verificarAcceso($clase, $request->user());
        $this->verificarDocente($request->user(), $clase);

        $inscritos = $this->alumnosInscritos($clase)->pluck('id')->map(fn ($mId) => (int) $mId)->values()->all();
        $total = count($inscritos);
        abort_unless($total > 0, 422, 'No hay estudiantes inscritos para dividir en grupos.');

        $validated = $request->validate([
            'modo' => 'required|in:grupos,tamano',
            'numero_grupos' => 'required_if:modo,grupos|nullable|integer|min:1',
            'integrantes_por_grupo' => 'required_if:modo,tamano|nullable|integer|min:1',
        ]);

        if ($validated['modo'] === 'grupos') {
            $numeroGrupos = (int) $validated['numero_grupos'];
            abort_unless($numeroGrupos <= $total, 422, 'El número de grupos no puede superar la cantidad de estudiantes inscritos.');
        } else {
            $numeroGrupos = (int) ceil($total / max(1, (int) $validated['integrantes_por_grupo']));
        }

        $reparto = $this->repartirEnGrupos($inscritos, $numeroGrupos);

        $clase->asignacionGrupos()->delete();

        $asignacion = AsignacionGrupo::create(['clase_docente_id' => $clase->id]);

        $filas = [];
        foreach ($reparto as $numero => $miembros) {
            foreach ($miembros as $alumnoId) {
                $filas[] = ['asignacion_id' => $asignacion->id, 'alumno_id' => $alumnoId, 'grupo' => $numero];
            }
        }
        AsignacionGrupoAlumno::insert($filas);

        return redirect()->route('clases.grupos', $clase->id)
            ->with('success', 'Distribución de grupos generada correctamente.');
    }

    public function destroyGrupos($id, Request $request)
    {
        $clase = ClaseDocente::findOrFail($id);

        $this->verificarAcceso($clase, $request->user());
        $this->verificarDocente($request->user(), $clase);

        $clase->asignacionGrupos()->delete();

        return redirect()->route('clases.grupos', $clase->id)
            ->with('success', 'Distribución de grupos eliminada.');
    }

    private function nombreCorto(string $apellidos, string $nombres): string
    {
        $primerApellido = trim((string) (explode(' ', trim($apellidos))[0] ?? ''));
        $primerNombre = trim((string) (explode(' ', trim($nombres))[0] ?? ''));

        if ($primerApellido === '' || $primerNombre === '') {
            return trim($primerApellido.' '.$primerNombre);
        }

        return $primerApellido.' '.$primerNombre;
    }

    private function repartirEnGrupos(array $estudiantes, int $numeroGrupos): array
    {
        $ids = collect($estudiantes)->shuffle()->values();
        $total = $ids->count();
        $base = intdiv($total, $numeroGrupos);
        $resto = $total % $numeroGrupos;

        $reparto = [];
        $pos = 0;
        for ($i = 1; $i <= $numeroGrupos; $i++) {
            $cantidad = $base + ($i <= $resto ? 1 : 0);
            $reparto[$i] = $ids->slice($pos, $cantidad)->all();
            $pos += $cantidad;
        }

        return $reparto;
    }

    private function dataAula(ClaseDocente $clase, Request $request, string $activo): array
    {
        $data = (new InicioClaseResource($clase))->resolve($request);

        $data['menu_aula_virtual'] = collect($data['menu_aula_virtual'])
            ->map(fn ($item) => $item + ['active' => ($item['key'] ?? '') === $activo])
            ->all();

        if ($request->user()->rol === 'REPRESENTANTE') {
            $data['menu_aula_virtual'] = collect($data['menu_aula_virtual'])
                ->filter(fn ($item) => in_array($item['key'] ?? '', ['grupos', 'materiales', 'mensajes', 'citas']))
                ->values()
                ->all();
        }

        $data['layout'] = 'aula';
        $data['es_docente'] = $request->user()->rol === 'DOCENTE';
        $data['url_volver'] = $request->user()->rol === 'REPRESENTANTE'
            ? route('representante.agendas')
            : '/clases';

        return $data;
    }

    private function alumnosInscritos(ClaseDocente $clase): Collection
    {
        return $clase->curso->matriculas()
            ->where('paralelo_id', $clase->paralelo_id)
            ->with('alumno')
            ->get()
            ->map(fn ($m) => [
                'id' => $m->alumno->id,
                'nombre' => "{$m->alumno->apellidos} {$m->alumno->nombres}",
            ])
            ->values();
    }

    private function verificarRolAula(User $user): void
    {
        abort_unless(in_array($user->rol, ['ADMIN', 'DOCENTE', 'ESTUDIANTE'], true), 403, 'No tienes acceso a esta sección.');
    }

    private function verificarDocente(User $user, ClaseDocente $clase): void
    {
        abort_if($user->rol !== 'DOCENTE', 403, 'Solo el docente puede realizar esta acción.');
        abort_if($clase->user_id !== $user->id, 403, 'No tienes acceso a esta clase.');
    }

    public function clasesParausuario(User $user): Collection
    {
        if ($user->rol === 'DOCENTE') {
            return ClaseDocente::with(['curso', 'paralelo', 'materia'])
                ->where('user_id', $user->id)
                ->get();
        }

        $clases = collect();
        $this->alumnosDelUsuario($user)->each(function (Alumno $alumno) use (&$clases) {
            $pares = $alumno->matriculas->map(fn ($m) => [$m->curso_id, $m->paralelo_id])->unique();
            foreach ($pares as [$cursoId, $paraleloId]) {
                $clases = $clases->merge(
                    ClaseDocente::with(['curso', 'paralelo', 'materia'])
                        ->where('curso_id', $cursoId)
                        ->where('paralelo_id', $paraleloId)
                        ->get()
                );
            }
        });

        return $clases->unique('id')->values();
    }

    public function alumnosDelUsuario(User $user): Collection
    {
        return match ($user->rol) {
            'ESTUDIANTE' => Alumno::with('matriculas')->where('user_id', $user->id)->get(),
            'REPRESENTANTE' => $user->representados()->with('matriculas')->get(),
            default => collect(),
        };
    }

    private function verificarAcceso(ClaseDocente $clase, User $user): void
    {
        if ($user->rol === 'DOCENTE') {
            abort_if($clase->user_id !== $user->id, 403, 'No tienes acceso a esta clase.');

            return;
        }
        if ($user->rol === 'ADMIN') {
            return;
        }

        $tieneAcceso = $this->alumnosDelUsuario($user)->contains(function (Alumno $alumno) use ($clase) {
            return $alumno->matriculas->contains(
                fn ($m) => $m->curso_id === $clase->curso_id && $m->paralelo_id === $clase->paralelo_id
            );
        });

        abort_unless($tieneAcceso, 403, 'No tienes acceso a esta clase.');
    }
}
