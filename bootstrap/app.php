<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => \App\Http\Middleware\RoleMiddleware::class,
        ]);

        // `AuthenticateSession` cierra las demás sesiones cuando la contraseña
        // cambia; `EnsurePasswordIsChanged` exige abandonar la clave temporal.
        $middleware->web(append: [
            \Illuminate\Session\Middleware\AuthenticateSession::class,
            \App\Http\Middleware\EnsurePasswordIsChanged::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // El límite de intentos de login se define en AppServiceProvider.
        // En vez de mostrar la página 429 de Laravel, se vuelve al formulario
        // con un mensaje claro en español y se deja registro del bloqueo.
        $exceptions->render(function (ThrottleRequestsException $e, Request $request) {
            if (! $request->is('login') || ! $request->isMethod('post')) {
                return null;
            }

            Log::warning('Límite de intentos de inicio de sesión alcanzado.', [
                'identificador' => mb_substr(trim((string) $request->input('email')), 0, 255),
                'ip' => $request->ip(),
                'user_agent' => mb_substr((string) $request->userAgent(), 0, 500),
            ]);

            return redirect()
                ->route('login')
                ->withErrors([
                    'email' => 'Demasiados intentos de inicio de sesión. Espere un momento e intente de nuevo.',
                ])
                ->withInput($request->only('email'));
        });
    })->create();
