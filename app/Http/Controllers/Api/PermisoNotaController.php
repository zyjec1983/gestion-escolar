<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ClaseDocente;
use Illuminate\Http\Request;

class PermisoNotaController extends Controller
{
    public function index($claseId)
    {
        $clase = ClaseDocente::with([
            'curso', 'paralelo', 'materia',
            'permisosNotas.unidad', 'permisosNotas.userAsigno',
        ])->findOrFail($claseId);

        return response()->json([
            'contexto_clase' => [
                'id' => $clase->id,
                'label' => "{$clase->curso->nombre} {$clase->paralelo->nombre} - {$clase->materia->nombre}",
            ],
            'tabla_permisos' => [
                'configuracion' => [
                    'columnas' => ['#', 'COD. PERMISO', 'UNIDAD', 'FECHA INICIO', 'FECHA TERMINACION', 'USUARIO ASIGNO', 'OPCIONES'],
                ],
                'datos' => $clase->permisosNotas->map(function ($permiso, $index) {
                    return [
                        '#' => $index + 1,
                        'codigo_permiso' => $permiso->codigo_permiso,
                        'unidad' => $permiso->unidad?->titulo ?? '-',
                        'fecha_inicio' => $permiso->fecha_inicio?->format('Y-m-d H:i'),
                        'fecha_terminacion' => $permiso->fecha_fin?->format('Y-m-d H:i'),
                        'usuario_asigno' => $permiso->userAsigno->name,
                        'opciones' => [],
                    ];
                }),
            ],
        ]);
    }
}
