<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClaseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'curso' => $this->curso->nombre,
            'paralelo' => "Paralelo {$this->paralelo->nombre}",
            'materia' => $this->materia->nombre,
            'configuracion_visual' => [
                'color_tarjeta' => $this->color_tarjeta ?? $this->materia->color_tarjeta ?? '#E63946',
            ],
        ];
    }
}
