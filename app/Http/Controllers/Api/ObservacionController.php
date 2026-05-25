<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ObservacionResource;
use App\Models\Alumno;
use App\Models\ClaseDocente;
use App\Models\Observacion;
use Illuminate\Http\Request;

class ObservacionController extends Controller
{
    public function index($claseId, Request $request)
    {
        $clase = ClaseDocente::with(['curso', 'paralelo', 'materia'])->findOrFail($claseId);

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

        $alumno = Alumno::where('codigo', $validated['alumno_codigo'])->first();
        $tipo = \App\Models\TipoObservacion::where('codigo', $validated['tipo_observacion_id'])->first();

        $observacion = Observacion::create([
            'alumno_id' => $alumno->id,
            'tipo_observacion_id' => $tipo->id,
            'user_id' => $request->user()->id,
            'descripcion_texto' => $validated['cuerpo_observacion'],
            'notificar_representante' => $validated['notificar_representante'] ?? false,
        ]);

        return response()->json([
            'message' => 'Observación creada exitosamente',
            'observacion' => $observacion,
        ], 201);
    }
}
