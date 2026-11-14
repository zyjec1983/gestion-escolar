<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Autoriza una ruta según el perfil activo del usuario.
 *
 * Qué hace: compara los roles exigidos por la ruta con `perfilActivo()` en
 * lugar de `users.rol`. Así un usuario multirol solo entra a los módulos del
 * perfil que eligió, sin tocar las rutas existentes.
 * Conecta con: App\Models\User::perfiles()/perfilActivo() y routes/web.php.
 */
class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (! Auth::check()) {
            return redirect('/login');
        }

        $perfilActivo = Auth::user()->perfilActivo();

        foreach ($roles as $rol) {
            if ($perfilActivo === $rol) {
                return $next($request);
            }
        }

        abort(403, 'No tienes permisos para acceder a esta sección.');
    }
}
