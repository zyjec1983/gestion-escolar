<?php

namespace App\Http\Controllers\Directivo\Concerns;

use Illuminate\Http\Request;

/**
 * Utilidades compartidas por los controladores de Rectorado y Vicerrectorado.
 *
 * Qué hace: resuelve el prefijo de rutas del panel activo y normaliza los
 * cortes de evaluación para que las vistas generen enlaces correctos.
 * Conecta con: RendimientoController, EstudiantesRiesgoController y las vistas
 * de directivo.
 */
trait UsaPanelesDirectivos
{
    /** Prefijo de rutas según el panel que atiende la petición. */
    protected function prefijo(Request $request): string
    {
        return $request->routeIs('rector.*') ? 'rector' : 'vicerrector';
    }

    /** Cortes de evaluación admitidos. */
    protected function corte(?string $corte): string
    {
        return in_array($corte, ['anual', 'q1', 'q2', 'p1', 'p2', 'p3', 'p4', 'p5', 'p6'], true)
            ? $corte
            : 'anual';
    }

    /** Etiqueta legible del corte. */
    protected function etiquetaCorte(string $corte): string
    {
        return match ($corte) {
            'q1' => 'Primer quimestre',
            'q2' => 'Segundo quimestre',
            'p1' => 'Primer parcial',
            'p2' => 'Segundo parcial',
            'p3' => 'Examen del primer quimestre',
            'p4' => 'Tercer parcial',
            'p5' => 'Cuarto parcial',
            'p6' => 'Examen del segundo quimestre',
            default => 'Promedio anual',
        };
    }
}
