<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Agenda;
use App\Models\Cita;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AgendaController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $semanaOffset = (int) $request->query('semana', 0);
        $now = Carbon::now()->startOfWeek()->addWeeks($semanaOffset);

        $semanaStart = $now->copy()->startOfWeek(Carbon::MONDAY);
        $semanaEnd = $now->copy()->endOfWeek(Carbon::SUNDAY);

        $citas = Cita::where('docente_id', $user->id)
            ->whereBetween('fecha_inicio', [$semanaStart, $semanaEnd])
            ->get();

        $dias = [];
        $diasSemana = [
            'L' => 'Lunes', 'M' => 'Martes', 'MI' => 'Miércoles',
            'J' => 'Jueves', 'V' => 'Viernes', 'S' => 'Sábado', 'D' => 'Domingo',
        ];

        foreach ($diasSemana as $clave => $nombre) {
            $fecha = $semanaStart->copy()->addDays(array_search($clave, array_keys($diasSemana)));
            $dias[] = [
                'clave' => $clave,
                'nombre' => $nombre,
                'fecha' => $fecha->format('Y-m-d'),
                'bloques_horarios' => $citas->filter(function ($cita) use ($fecha) {
                    return $cita->fecha_inicio->format('Y-m-d') === $fecha->format('Y-m-d');
                })->values(),
            ];
        }

        return response()->json([
            'control_agenda' => [
                'semana_seleccionada' => "Semana {$now->weekOfYear}, {$now->year}",
                'fecha_inicio_semana' => $semanaStart->format('Y-m-d'),
                'fecha_fin_semana' => $semanaEnd->format('Y-m-d'),
                'total_horas_disponibles' => $citas->count(),
            ],
            'dias_agenda' => $dias,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'clase_docente_id' => 'required|exists:clases_docentes,id',
            'tipo_actividad_id' => 'required|exists:tipo_actividades,codigo',
            'insumo_evaluacion_id' => 'nullable|exists:insumos_evaluacion,id',
            'titulo_agenda' => 'required|string|max:255',
            'descripcion_instrucciones_html' => 'nullable|string',
            'fecha_inicio' => 'required|date',
            'fecha_entrega_limite' => 'nullable|date|after:fecha_inicio',
            'enviar_a_todos' => 'boolean',
        ]);

        $tipoActividad = \App\Models\TipoActividad::where('codigo', $validated['tipo_actividad_id'])->first();

        $agenda = Agenda::create([
            'clase_docente_id' => $validated['clase_docente_id'],
            'tipo_actividad_id' => $tipoActividad->id,
            'insumo_evaluacion_id' => $validated['insumo_evaluacion_id'] ?? null,
            'titulo_agenda' => $validated['titulo_agenda'],
            'descripcion_instrucciones_html' => $validated['descripcion_instrucciones_html'],
            'fecha_inicio' => $validated['fecha_inicio'],
            'fecha_entrega_limite' => $validated['fecha_entrega_limite'] ?? null,
            'enviar_a_todos' => $validated['enviar_a_todos'] ?? true,
        ]);

        return response()->json($agenda, 201);
    }
}
