<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CircularResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->numero ?? "CIRC-{$this->id}",
            'titulo' => $this->titulo,
            'fecha_publicacion' => $this->fecha_publicacion?->format('Y-m-d'),
            'archivo_url' => $this->archivo_url ? asset($this->archivo_url) : null,
        ];
    }
}
