<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ObservacionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'contexto_clase' => [
                'id' => $this->claseDocente->id,
                'label' => "{$this->claseDocente->curso->nombre} {$this->claseDocente->paralelo->nombre} - {$this->claseDocente->materia->nombre}",
            ],
            'listado_alumnos' => $this->alumnos->map(function ($alumno) {
                return [
                    'codigo' => $alumno->codigo,
                    'apellidos_nombres' => "{$alumno->apellidos} {$alumno->nombres}",
                    'genero' => $alumno->genero,
                ];
            }),
            'tabla_historial_detalle' => [
                'alumno_seleccionado_codigo' => $this->alumnoSeleccionado?->codigo,
                'registros' => $this->observaciones->map(function ($obs) {
                    return [
                        'id' => $obs->id,
                        'tipo_observacion' => $obs->tipoObservacion->nombre,
                        'icono_tipo' => $obs->tipoObservacion->icono,
                        'descripcion_texto' => $obs->descripcion_texto,
                        'profesor' => $obs->profesor->name,
                        'fecha' => $obs->created_at?->format('Y-m-d'),
                    ];
                }),
            ],
        ];
    }
}
