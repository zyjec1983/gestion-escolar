<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InicioClaseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $unidad = $this->materia->unidades()->first();

        return [
            'clase_actual' => [
                'id' => $this->id,
                'curso_paralelo' => "{$this->curso->nombre} {$this->paralelo->nombre}",
                'materia' => $this->materia->nombre,
                'unidad_actual' => $unidad ? [
                    'titulo' => $unidad->titulo,
                    'fecha_inicio' => $unidad->fecha_inicio?->format('Y-m-d'),
                    'fecha_fin' => $unidad->fecha_fin?->format('Y-m-d'),
                ] : null,
            ],
            'menu_aula_virtual' => [
                'Inicio', 'Control del Aula', 'Asistencia', 'Agenda',
                'Foros', 'Grupos de Clase', 'Materiales', 'Evaluaciones', 'Calificación',
            ],
            'estudiantes_inscritos' => $this->curso->matriculas()
                ->where('paralelo_id', $this->paralelo_id)
                ->with('alumno')
                ->get()
                ->map(function ($matricula) {
                    return [
                        'matricula_id' => $matricula->codigo_matricula ?? "MAT-{$matricula->id}",
                        'apellidos_nombres' => "{$matricula->alumno->apellidos} {$matricula->alumno->nombres}",
                    ];
                }),
        ];
    }
}
