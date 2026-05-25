<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AgendaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'control_agenda' => [
                'semana_seleccionada' => $this->semanaLabel,
                'fecha_inicio_semana' => $this->semanaStart?->format('Y-m-d'),
                'fecha_fin_semana' => $this->semanaEnd?->format('Y-m-d'),
                'total_horas_disponibles' => $this->citas?->count() ?? 0,
            ],
            'dias_agenda' => $this->dias->map(function ($dia) {
                return [
                    'clave' => $dia['clave'],
                    'nombre' => $dia['nombre'],
                    'fecha' => $dia['fecha'],
                    'bloques_horarios' => $dia['bloques'] ?? [],
                ];
            }),
        ];
    }
}
