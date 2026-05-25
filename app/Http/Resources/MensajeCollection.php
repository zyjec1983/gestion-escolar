<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class MensajeCollection extends ResourceCollection
{
    public $collects = MensajeResource::class;

    public function toArray(Request $request): array
    {
        return [
            'carpeta_actual' => $request->query('carpeta', 'no_leidos'),
            'mensajes' => $this->collection,
        ];
    }
}
