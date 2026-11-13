<?php

namespace App\Http\Controllers\Dece\Concerns;

/**
 * Reglas compartidas por los listados del DECE que pueden filtrarse por un
 * estudiante elegido en el autocompletado o por texto libre. Cuando llega un
 * alumno_id se ignora el texto para no mezclar ambos criterios.
 */
trait FiltraPorAlumno
{
    /**
     * @param  array<string, mixed>  $filtros
     */
    protected function textoBusqueda(array $filtros): ?string
    {
        if ($filtros['alumno_id'] ?? null) {
            return null;
        }

        return $filtros['buscar'] ?? null;
    }
}
