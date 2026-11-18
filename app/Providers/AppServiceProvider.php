<?php

namespace App\Providers;

use App\Models\Institucion;
use App\Services\Sms\LogSmsSender;
use App\Services\Sms\SmsSender;
use App\Services\Sms\WebhookSmsSender;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Throwable;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Canal SMS desacoplado: `log` para desarrollo y `webhook` para una
        // pasarela externa. Lo usa CircularRecibida al enviar circulares.
        $this->app->singleton(SmsSender::class, function () {
            return match (config('services.sms.driver', 'log')) {
                'webhook' => new WebhookSmsSender,
                default => new LogSmsSender,
            };
        });
    }

    public function boot(): void
    {
        // El proyecto usa Bootstrap 5.3.3: la vista de paginación por defecto
        // de Laravel es Tailwind y sus iconos SVG se veían gigantes al no
        // existir Tailwind en el layout. La vista en español vive en
        // resources/views/vendor/pagination/bootstrap-5.blade.php.
        Paginator::useBootstrapFive();

        static::configurarLimiteDeIntentosDeLogin();
        static::aplicarZonaHorariaDelCentro();
        static::compartirInstitucion();
    }

    /**
     * Limita los intentos de inicio de sesión antes de llegar al controlador.
     *
     * Qué hace: además del bloqueo por cuenta que ya vive en `users`, frena
     * el *password spraying* (muchas cuentas desde una misma IP) y los ataques
     * de fuerza bruta contra un identificador concreto. La clave combina el
     * correo/cédula escrito con la IP, y se agrega un límite global por IP.
     *
     * Conecta con: routes/web.php (`throttle:login`) y
     * bootstrap/app.php (respuesta amable al superar el límite).
     */
    private static function configurarLimiteDeIntentosDeLogin(): void
    {
        RateLimiter::for('login', function (Request $request) {
            $identificador = Str::lower(trim((string) $request->input('email')));
            $ip = $request->ip();

            // Cada ventana usa una clave distinta. Si comparten clave, cada
            // petición suma dos intentos y el tiempo de espera del límite por
            // hora termina aplicándose al límite por minuto.
            return [
                Limit::perMinute(5)->by('minuto|'.$identificador.'|'.$ip),
                Limit::perHour(20)->by('hora|'.$identificador.'|'.$ip),
                Limit::perMinute(30)->by('ip|'.$ip),
            ];
        });
    }

    /**
     * Comparte nombre, subtítulo y logo con todas las vistas.
     *
     * Qué hace: evita repetir `Institucion::first()` en cada controlador y
     * permite que layouts, login, inicio y favicon usen los datos del admin.
     * Conecta con: layouts/app, layouts/public, auth/login y welcome.
     * Ruta: POST /admin/configuracion/institucion.
     */
    private static function compartirInstitucion(): void
    {
        try {
            View::share('institucion', Institucion::first());
        } catch (Throwable) {
            // La tabla aún no existe (por ejemplo durante `artisan migrate`).
            View::share('institucion', null);
        }
    }

    /**
     * El desfase se elige en Configuración (tabla instituciones) para que una
     * instalación en cualquier país no dependa de recompilar ni de tocar el
     * .env. Si no hay valor guardado se respeta APP_TIMEZONE.
     *
     * Va en boot() porque Laravel fija date_default_timezone_set() al cargar la
     * configuración, antes de los providers: hay que volver a fijarla aquí para
     * que now() ya devuelva la hora local del centro.
     */
    public static function aplicarZonaHorariaDelCentro(): void
    {
        $zona = null;

        try {
            $zona = Institucion::first()?->zona_horaria;
        } catch (Throwable) {
            // La tabla aún no existe (instalación nueva, artisan migrate...).
            $zona = null;
        }

        $zona = $zona ?: Config::get('app.timezone');

        if (! esDesfaseHorarioValido($zona)) {
            // Sin valor en la BD (instalación nueva) se toma APP_TIMEZONE y se
            // traduce a desfase, venga como nombre de zona o como desfase.
            $minutos = minutosDesfaseZona($zona);
            $zona = valorDesfaseHorario((int) (round($minutos / 60) * 60));
        }

        // PHP no acepta un desfase suelto ("-05:00") en date_default_timezone_set(),
        // así que se traduce a su zona Etc/GMT equivalente.
        $zonaPhp = zonaPhpDesdeDesfase($zona);

        Config::set('app.timezone', $zona);

        date_default_timezone_set($zonaPhp);
    }
}
