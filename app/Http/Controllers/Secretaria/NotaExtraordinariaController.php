<?php

namespace App\Http\Controllers\Secretaria;

use App\Http\Controllers\Controller;
use App\Models\Alumno;
use App\Models\ClaseDocente;
use App\Models\Matricula;
use App\Models\Nota;
use App\Models\NotaAuditoria;
use App\Models\PeriodoLectivo;
use App\Services\ConfiguracionNotas;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Correccion extraordinaria de notas por Secretaria / ADMIN. Cada cambio
 * exige un motivo y deja un asiento inmutable con el antes, el despues, el
 * usuario, la fecha exacta y la trazabilidad de red.
 */
class NotaExtraordinariaController extends Controller
{
    public function __construct(private readonly ConfiguracionNotas $configuracion) {}

    public function index(Request $request)
    {
        $sistema = $this->configuracion->sistema();
        $opciones = $this->configuracion->opciones($sistema);
        $grupos = collect($opciones)->groupBy('grupo');

        $clases = ClaseDocente::with(['curso', 'paralelo', 'materia', 'docente'])
            ->get()
            ->sortBy(fn (ClaseDocente $clase) => mb_strtoupper(
                ($clase->materia?->nombre ?? '').' '.($clase->curso?->nombre ?? '').' '.($clase->paralelo?->nombre ?? ''),
                'UTF-8'
            ))
            ->values();

        $filtros = $request->validate([
            'clase_docente_id' => ['nullable', 'integer', 'exists:clases_docentes,id'],
            'parcial' => ['nullable', 'integer', 'in:'.implode(',', array_column($opciones, 'parcial'))],
        ]);

        $clase = $clases->firstWhere('id', $filtros['clase_docente_id'] ?? null) ?? $clases->first();
        $parcial = (int) ($filtros['parcial'] ?? $opciones[0]['parcial'] ?? 1);
        $tipo = $this->configuracion->tipoDeParcial($parcial, $sistema);

        $periodo = PeriodoLectivo::where('activo', true)->first();
        $alumnos = collect();
        $notasExistentes = collect();

        if ($clase) {
            $matriculas = Matricula::query()
                ->where('curso_id', $clase->curso_id)
                ->where('paralelo_id', $clase->paralelo_id)
                ->when($periodo, fn ($query) => $query->where('periodo_id', $periodo->id))
                ->get();

            $notasExistentes = Nota::with('alumno')
                ->withCount('auditorias')
                ->whereIn('matricula_id', $matriculas->pluck('id'))
                ->where('clase_docente_id', $clase->id)
                ->where('parcial', $parcial)
                ->get()
                ->keyBy('alumno_id');

            $alumnos = Alumno::whereIn('id', $matriculas->pluck('alumno_id'))
                ->orderBy('apellidos')
                ->orderBy('nombres')
                ->get();
        }

        return view('secretaria.notas-extraordinarias.index', compact(
            'clases', 'clase', 'parcial', 'tipo', 'sistema', 'opciones', 'grupos',
            'alumnos', 'notasExistentes', 'filtros'
        ));
    }

    public function store(Request $request)
    {
        $nota = Nota::with([
            'matricula',
            'alumno',
            'claseDocente.materia',
            'claseDocente.curso',
            'claseDocente.paralelo',
        ])->findOrFail($request->input('nota_id'));

        $campos = $this->configuracion->camposParaTipo($nota->tipo_nota);

        $datos = [];
        foreach ($campos as $campo) {
            $datos[$campo] = $this->configuracion->decimal($request->input($campo));
        }
        $request->merge($datos);

        $reglas = [
            'motivo' => ['required', 'string', 'min:10', 'max:1000'],
        ];

        foreach ($campos as $campo) {
            $reglas[$campo] = ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:10'];
        }

        $validated = $request->validate($reglas);

        $anteriores = [];
        $nuevos = [];

        foreach ($campos as $campo) {
            $anteriores[$campo] = $nota->{$campo} !== null ? round((float) $nota->{$campo}, 4) : null;
            $nuevos[$campo] = round((float) $validated[$campo], 4);
        }

        if ($anteriores === $nuevos && ! $request->boolean('marcar_final')) {
            throw ValidationException::withMessages([
                'motivo' => 'No se detectaron cambios en la nota. Modifique al menos un valor para registrar la corrección.',
            ]);
        }

        $estadoAnterior = $nota->estado;
        $estadoNuevo = $request->boolean('marcar_final') ? 'final' : $estadoAnterior;
        $usuario = $request->user();

        $datosAnteriores = array_merge($anteriores, ['estado' => $estadoAnterior]);
        $datosNuevos = array_merge($nuevos, ['estado' => $estadoNuevo]);

        DB::transaction(function () use ($nota, $nuevos, $estadoNuevo, $usuario, $datosAnteriores, $datosNuevos, $validated, $request) {
            $nota->fill($nuevos);
            $nota->estado = $estadoNuevo;
            $nota->save();

            NotaAuditoria::create([
                'nota_id' => $nota->id,
                'matricula_id' => $nota->matricula_id,
                'alumno_id' => $nota->alumno_id,
                'clase_docente_id' => $nota->clase_docente_id,
                'alumno_nombre' => $nota->alumno?->nombreCompleto() ?? 'Estudiante sin nombre',
                'materia' => $nota->claseDocente?->materia?->nombre,
                'curso' => $nota->claseDocente?->curso?->nombre,
                'paralelo' => $nota->claseDocente?->paralelo?->nombre,
                'parcial' => $nota->parcial,
                'tipo_nota' => $nota->tipo_nota,
                'usuario_id' => $usuario->id,
                'usuario_nombre' => $usuario->nombreCompleto(),
                'usuario_rol' => $usuario->rol,
                'accion' => NotaAuditoria::ACCION_CORRECCION,
                'motivo' => $validated['motivo'],
                'datos_anteriores' => $datosAnteriores,
                'datos_nuevos' => $datosNuevos,
                'ip' => $request->ip(),
                'user_agent' => mb_substr((string) $request->userAgent(), 0, 1000, 'UTF-8'),
            ]);
        });

        return redirect()
            ->route('notas-extraordinarias.index', [
                'clase_docente_id' => $nota->clase_docente_id,
                'parcial' => $nota->parcial,
            ])
            ->with('success', 'Nota corregida. El cambio quedó registrado en la bitácora inalterable.');
    }

    public function bitacora(Request $request)
    {
        $filtros = $request->validate([
            'buscar' => ['nullable', 'string', 'max:100'],
        ]);

        $auditorias = NotaAuditoria::with('usuario')
            ->when($filtros['buscar'] ?? null, function ($query, string $buscar) {
                $query->where(function ($sub) use ($buscar) {
                    $sub->where('alumno_nombre', 'like', "%{$buscar}%")
                        ->orWhere('materia', 'like', "%{$buscar}%")
                        ->orWhere('usuario_nombre', 'like', "%{$buscar}%");
                });
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('secretaria.notas-extraordinarias.bitacora', [
            'auditorias' => $auditorias,
            'filtros' => $filtros,
            'opciones' => $this->configuracion->opciones(),
        ]);
    }
}
