<?php

namespace App\Http\Controllers\Dece;

use App\Http\Controllers\Controller;
use App\Models\Alumno;
use App\Services\Dece\BuscadorAcademico;
use Illuminate\Http\Request;

/**
 * Endpoints JSON de los buscadores del DECE. Evitan cargar el padron completo
 * en los formularios y alimentan las listas relacionadas (citas y atenciones).
 */
class BuscadorController extends Controller
{
    public function __construct(private readonly BuscadorAcademico $buscador) {}

    public function alumnos(Request $request)
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
        ]);

        return response()->json([
            'data' => $this->buscador->alumnos($validated['q'] ?? null),
        ]);
    }

    public function citas(Alumno $alumno)
    {
        return response()->json([
            'data' => $this->buscador->citasProgramadas($alumno),
        ]);
    }

    public function talleres(Request $request)
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
        ]);

        return response()->json([
            'data' => $this->buscador->talleres($validated['q'] ?? null),
        ]);
    }

    public function atenciones(Alumno $alumno)
    {
        return response()->json([
            'data' => $this->buscador->atenciones($alumno),
        ]);
    }
}
