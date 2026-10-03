<?php

namespace App\Providers;

use App\Models\Institucion;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        static::aplicarZonaHorariaDelCentro();
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
        } catch (\Throwable) {
            // La tabla aún no existe (instalación nueva, artisan migrate...).
            $zona = null;
        }

        $zona = $zona ?: Config::get('app.timezone');

        if (!esDesfaseHorarioValido($zona)) {
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