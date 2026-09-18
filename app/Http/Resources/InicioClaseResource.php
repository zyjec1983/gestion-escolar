<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InicioClaseResource extends JsonResource
{
    public static function unidadActual(\App\Models\ClaseDocente $clase): ?\App\Models\Unidad
    {
        $unidades = $clase->materia->unidades()
            ->where(fn ($q) => $q->whereNull('clase_docente_id')->orWhere('clase_docente_id', $clase->id))
            ->orderBy('fecha_inicio')
            ->get();

        $hoy = \Illuminate\Support\Carbon::today();

        $activa = $unidades->first(fn ($u) => $u->fecha_inicio && $u->fecha_fin
            && $u->fecha_inicio->lte($hoy) && $u->fecha_fin->gte($hoy));

        if ($activa) {
            return $activa;
        }

        $proxima = $unidades->first(fn ($u) => $u->fecha_inicio && $u->fecha_inicio->gt($hoy));

        return $proxima ?? $unidades->last();
    }

    public function toArray(Request $request): array
    {
        $unidad = self::unidadActual($this->resource);

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
                ['label' => 'Inicio', 'icon' => 'house-door', 'url' => "/clases/{$this->id}"],
                ['label' => 'Agenda', 'icon' => 'clipboard-check', 'url' => null],
                ['label' => 'Foros', 'icon' => 'chat-dots', 'url' => null],
                ['label' => 'Grupos de Clase', 'icon' => 'people-fill', 'url' => null],
                ['label' => 'Materiales', 'icon' => 'folder', 'url' => null],
                ['label' => 'Mensajes', 'icon' => 'envelope', 'url' => '/mensajes'],
                ['label' => 'Citas', 'icon' => 'calendar-event', 'url' => null],
            ],
            'estudiantes_inscritos' => $this->curso->matriculas()
                ->where('paralelo_id', $this->paralelo_id)
                ->with('alumno')
                ->get()
                ->map(function ($matricula) {
                    return [
                        'matricula_id' => $matricula->codigo_matricula ?? "MAT-{$matricula->id}",
                        'matricula_row_id' => $matricula->id,
                        'alumno_id' => $matricula->alumno->id,
                        'apellidos_nombres' => "{$matricula->alumno->apellidos} {$matricula->alumno->nombres}",
                        'nee' => (bool) $matricula->alumno->necesidades_educativas_especificas_nee,
                        'discapacidad' => (bool) $matricula->alumno->tiene_discapacidad,
                    ];
                }),
        ];
    }
}