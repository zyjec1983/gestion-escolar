<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Obliga a cambiar la contraseña inicial antes de usar el sistema.
 *
 * Qué hace: si la cuenta trae `debe_cambiar_password`, cualquier petición web
 * se redirige al formulario de cambio obligatorio. Se exceptúan el propio
 * formulario y el cierre de sesión, para que la persona pueda completar el
 * trámite o salir sin quedar atrapada.
 *
 * Conecta con: App\Models\User, ProfileController y routes/web.php.
 * Se registra en bootstrap/app.php dentro del grupo `web`.
 */
class EnsurePasswordIsChanged
{
    /** Rutas que siguen disponibles mientras la contraseña es temporal. */
    private const RUTAS_PERMITIDAS = [
        'perfil.password.obligatorio',
        'perfil.password.obligatorio.update',
        'logout',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->debe_cambiar_password) {
            return $next($request);
        }

        if ($request->routeIs(...self::RUTAS_PERMITIDAS)) {
            return $next($request);
        }

        return redirect()
            ->route('perfil.password.obligatorio')
            ->with('warning', 'Por seguridad, debe cambiar la contraseña inicial antes de continuar.');
    }
}
