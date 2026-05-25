<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MensajeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id_mensaje' => $this->id,
            'asunto' => $this->asunto,
            'cuerpo_contenido' => $this->cuerpo_contenido,
            'fecha_envio' => $this->fecha_envio?->format('Y-m-d\TH:i:s\Z'),
            'emisor' => [
                'id_usuario' => $this->emisor?->id,
                'nombre_completo' => $this->emisor?->name,
                'rol' => $this->emisor?->rol,
                'contexto_estudiante_asociado' => $this->when($this->contextoEstudiante, function () {
                    return [
                        'codigo_alumno' => $this->contextoEstudiante->codigo,
                        'nombre_alumno' => "{$this->contextoEstudiante->apellidos} {$this->contextoEstudiante->nombres}",
                    ];
                }),
            ],
        ];
    }
}
