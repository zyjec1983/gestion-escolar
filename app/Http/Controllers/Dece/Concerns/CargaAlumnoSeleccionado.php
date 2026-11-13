<?php

namespace App\Http\Controllers\Dece\Concerns;

use App\Models\Alumno;
use Illuminate\Http\Request;

/**
 * Recupera el estudiante que llega por la URL o que quedo en el formulario
 * anterior tras un error de validacion, con las relaciones que necesitan las
 * pantallas de alta del DECE.
 */
trait CargaAlumnoSeleccionado
{
    protected function alumnoSeleccionado(Request $request): ?Alumno
    {
        $id = (int) (old('alumno_id') ?: $request->integer('alumno_id'));

        return $this->cargarAlumno($id);
    }

    protected function cargarAlumno(?int $id): ?Alumno
    {
        if (! $id) {
            return null;
        }

        return Alumno::with([
            'representante',
            'matriculas' => fn ($query) => $query->latest('id'),
            'matriculas.curso',
            'matriculas.paralelo',
        ])->find($id);
    }
}
