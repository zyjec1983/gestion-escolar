<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PermisoNotaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'contexto_clase' => [
                'id' => $this->claseDocente->id,
                'label' => "{$this->claseDocente->curso->nombre} {$this->claseDocente->paralelo->nombre} - {$this->claseDocente->materia->nombre}",
            ],
            'tabla_permisos' => [
                'configuracion' => [
                    'columnas' => ['#', 'COD. PERMISO', 'UNIDAD', 'FECHA INICIO', 'FECHA TERMINACION', 'USUARIO ASIGNO', 'OPCIONES'],
                ],
                'datos' => $this->whenLoaded('permisosNotas', function () {
                    return $this->claseDocente->permisosNotas->map(function ($permiso, $index) {
                        return [
                            '#' => $index + 1,
                            'codigo_permiso' => $permiso->codigo_permiso,
                            'unidad' => $permiso->unidad?->titulo ?? '-',
                            'fecha_inicio' => $permiso->fecha_inicio?->format('Y-m-d H:i'),
                            'fecha_terminacion' => $permiso->fecha_fin?->format('Y-m-d H:i'),
                            'usuario_asigno' => $permiso->userAsigno->name,
                            'opciones' => [],
                        ];
                    }) ?? [],
                }),
            ],
        ];
    }
}
