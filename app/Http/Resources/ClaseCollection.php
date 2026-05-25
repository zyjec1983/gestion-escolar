<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ClaseCollection extends ResourceCollection
{
    public $collects = ClaseResource::class;

    public function toArray(Request $request): array
    {
        $categorias = $this->collection->pluck('curso.nombre')->unique()->values()->map(function ($curso) {
            return [
                'id' => strtolower(str_replace(' ', '_', $curso)),
                'label' => $curso,
                'seleccionado' => false,
            ];
        })->prepend([
            'id' => 'todos',
            'label' => 'TODOS',
            'seleccionado' => true,
        ]);

        return [
            'filtros' => [
                'categorias' => $categorias,
                'busqueda' => [
                    'placeholder' => 'Buscar (min 3 char.)',
                    'valor_actual' => '',
                ],
            ],
            'clases_asignadas' => $this->collection,
        ];
    }
}
