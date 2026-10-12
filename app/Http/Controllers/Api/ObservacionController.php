<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\NotificaObservacionAlRepresentante;
use App\Http\Controllers\Controller;
use App\Http\Resources\ObservacionResource;
use App\Models\Alumno;
use App\Models\ClaseDocente;
use App\Models\Observacion;
use App\Models\TipoObservacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ObservacionController extends Controller
{
    use NotificaObservacionAlRepresentante;

    /** Autoridades con alcance global sobre cualquier alumno. */
    private const ROLES_GLOBALES = ['INSPECCION', 'RECTORA', 'VICERRECTORA', 'DECE'];

    public function index($claseId, Request $request)
    {
        $clase = ClaseDocente::with(['curso', 'paralelo', 'materia'])->findOrFail($claseId);

        $this->autorizarConsulta($request, $clase);

        $alumnos = Alumno::whereHas('matriculas', function ($q) use ($clase) {
            $q->where('curso_id', $clase->curso_id)
              ->where('paralelo_id', $clase->paralelo_id);
        })->get();

        $alumnoSeleccionado = $request->query('alumno_codigo')
            ? Alumno::where('codigo', $request->query('alumno_codigo'))->first()
            : $alumnos->first();

        $observaciones = collect();
        if ($alumnoSeleccionado) {
            $observaciones = Observacion::with(['tipoObservacion', 'profesor'])
                ->where('alumno_id', $alumnoSeleccionado->id)
                ->latest()
                ->paginate(15);
        }

        return response()->json([
            'contexto_clase' => [
                'id' => $clase->id,
                'label' => "{$clase->curso->nombre} {$clase->paralelo->nombre} - {$clase->materia->nombre}",
            ],
            'listado_alumnos' => $alumnos->map(function ($alumno) {
                return [
                    'codigo' => $alumno->codigo,
                    'apellidos_nombres' => "{$alumno->apellidos} {$alumno->nombres}",
                    'genero' => $alumno->genero ?? 'femenino',
                ];
            }),
            'tabla_historial_detalle' => [
                'alumno_seleccionado_codigo' => $alumnoSeleccionado?->codigo,
                'registros' => $observaciones instanceof \Illuminate\Pagination\LengthAwarePaginator
                    ? $observaciones->items()
                    : $observaciones,
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'alumno_codigo' => 'required|exists:alumnos,codigo',
            'tipo_observacion_id' => 'required|exists:tipos_observacion,codigo',
            'cuerpo_observacion' => 'required|string',
            'notificar_representante' => 'boolean',
        ]);

        $alumno = Alumno::with('representante')->where('codigo', $validated['alumno_codigo'])->firstOrFail();
        $tipo = TipoObservacion::where('codigo', $validated['tipo_observacion_id'])->firstOrFail();

        $this->autorizarRegistro($request, $alumno);

        // Las autoridades dejan constancia de que el representante fue notificado,
        // igual que en la pantalla: el aviso no es opcional para ellas.
        $notificar = in_array($request->user()->rol, self::ROLES_GLOBALES, true)
            || $request->boolean('notificar_representante');

        [$observacion, $aviso] = DB::transaction(function () use ($request, $validated, $alumno, $tipo, $notificar) {
            $observacion = Observacion::create([
                'alumno_id' => $alumno->id,
                'tipo_observacion_id' => $tipo->id,
                'user_id' => $request->user()->id,
                'descripcion_texto' => $validated['cuerpo_observacion'],
                'notificar_representante' => $notificar,
            ]);

            $aviso = $notificar
                ? $this->avisarAlRepresentante($request->user(), $alumno, $tipo, $observacion)
                : null;

            return [$observacion, $aviso];
        });

        $mensaje = $notificar
            ? ($aviso
                ? 'Observación creada exitosamente. Se notificó al representante.'
                : 'Observación creada exitosamente, pero el alumno no tiene representante asociado, así que no se pudo enviar el aviso.')
            : 'Observación creada exitosamente';

        return response()->json([
            'message' => $mensaje,
            'notificado' => (bool) $aviso,
            'observacion' => $observacion,
        ], 201);
    }

    /**
     * Las autoridades revisan el historial de cualquier clase; el resto solo
     * puede ver la clase que le corresponde.
     */
    private function autorizarConsulta(Request $request, ClaseDocente $clase): void
    {
        $rol = $request->user()->rol;

        if ($rol === 'DOCENTE' && $clase->user_id === $request->user()->id) {
            return;
        }

        if (in_array($rol, self::ROLES_GLOBALES, true)) {
            return;
        }

        abort(403, 'No tienes permisos para consultar estas observaciones.');
    }

    /**
     * El docente solo registra sobre alumnos de sus clases. Las autoridades
     * tienen alcance global. Cualquier otro rol queda fuera.
     */
    private function autorizarRegistro(Request $request, Alumno $alumno): void
    {
        $usuario = $request->user();

        if (in_array($usuario->rol, self::ROLES_GLOBALES, true)) {
            return;
        }

        if ($usuario->rol !== 'DOCENTE') {
            abort(403, 'No tienes permisos para registrar observaciones.');
        }

        $matriculas = $alumno->matriculas()->get(['curso_id', 'paralelo_id']);

        $clasesDelDocente = ClaseDocente::where('user_id', $usuario->id)
            ->get(['curso_id', 'paralelo_id']);

        $pertenece = $clasesDelDocente->contains(fn ($clase) => $matriculas->contains(
            fn ($matricula) => $matricula->curso_id == $clase->curso_id
                && $matricula->paralelo_id == $clase->paralelo_id
        ));

        if (!$pertenece) {
            abort(403, 'Ese alumno no pertenece a ninguna de tus clases.');
        }
    }
}
