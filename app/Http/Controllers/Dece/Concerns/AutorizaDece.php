<?php

namespace App\Http\Controllers\Dece\Concerns;

use Illuminate\Http\Request;

/**
 * Reglas de autorizacion del modulo DECE. Las rutas ya llevan middleware de
 * rol; estas guardas protegen ademas cada accion sensible para que un cambio
 * futuro de rutas no abra datos confidenciales por accidente.
 */
trait AutorizaDece
{
    protected function puedeGestionar(Request $request): bool
    {
        return in_array($request->user()?->rol, ['DECE', 'ADMIN'], true);
    }

    protected function soloGestion(Request $request): void
    {
        abort_unless($this->puedeGestionar($request), 403, 'Solo el DECE y la administración pueden modificar este registro.');
    }

    protected function puedeVerConfidencial(Request $request): bool
    {
        return $this->puedeGestionar($request);
    }
}
