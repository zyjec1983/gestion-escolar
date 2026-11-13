<?php

namespace App\Support;

use App\Models\Ajuste;
use Throwable;

/**
 * Apariencia global del sistema (aside y tamaño de fuente).
 *
 * Qué hace: centraliza las 10 paletas de color del menú lateral y los tamaños
 * de fuente tipo Microsoft Word. El administrador elige una combinación y esta
 * se aplica a TODOS los roles porque los layouts la leen de la tabla `ajustes`.
 *
 * Conecta con:
 * - App\Models\Ajuste: persistencia clave/valor.
 * - App\Http\Controllers\Admin\ConfiguracionController: guarda la selección.
 * - resources/views/layouts/app.blade.php: inyecta las variables CSS.
 * - resources/views/layouts/principal.blade.php y layouts/aula.blade.php:
 *   usan `var(--aside-bg)` en el aside.
 *
 * Rutas:
 * - GET  /admin/configuracion              (admin.configuracion.index)
 * - POST /admin/configuracion/apariencia   (admin.configuracion.apariencia)
 */
class Tema
{
    /** Clave en `ajustes` para la paleta del aside. */
    public const AJUSTE_TEMA = 'tema_aside';

    /** Clave en `ajustes` para el tamaño de fuente en puntos (pt). */
    public const AJUSTE_FUENTE = 'tema_fuente_pt';

    /** Paleta institucional por defecto: azul Microsoft Word. */
    public const TEMA_POR_DEFECTO = 'word';

    /** Word usa 11 pt por defecto. */
    public const FUENTE_POR_DEFECTO = '11';

    /**
     * Las 10 combinaciones disponibles para el aside.
     *
     * Cada paleta define:
     * - `nombre`: etiqueta visible en Configuración.
     * - `aside`: color base del menú lateral.
     * - `activo`: fondo del menú seleccionado.
     * - `hover`: fondo al pasar el cursor.
     * - `texto`: color de las letras (blanco institucional).
     *
     * @return array<string, array{nombre: string, aside: string, activo: string, hover: string, texto: string}>
     */
    public static function paletas(): array
    {
        return [
            'word' => [
                'nombre' => 'Azul Microsoft Word',
                'aside' => '#2B579A',
                'activo' => '#3E6DB5',
                'hover' => '#3E6DB5',
                'texto' => '#FFFFFF',
            ],
            'azul_noche' => [
                'nombre' => 'Azul noche',
                'aside' => '#1F3A5F',
                'activo' => '#2E5C8A',
                'hover' => '#2E5C8A',
                'texto' => '#FFFFFF',
            ],
            'verde' => [
                'nombre' => 'Verde institucional',
                'aside' => '#1E6B3A',
                'activo' => '#2E8B57',
                'hover' => '#2E8B57',
                'texto' => '#FFFFFF',
            ],
            'turquesa' => [
                'nombre' => 'Turquesa',
                'aside' => '#0F5E63',
                'activo' => '#177F86',
                'hover' => '#177F86',
                'texto' => '#FFFFFF',
            ],
            'vino' => [
                'nombre' => 'Vino tinto',
                'aside' => '#7B1E2B',
                'activo' => '#A12B3C',
                'hover' => '#A12B3C',
                'texto' => '#FFFFFF',
            ],
            'morado' => [
                'nombre' => 'Morado',
                'aside' => '#4B2A6B',
                'activo' => '#6A3E91',
                'hover' => '#6A3E91',
                'texto' => '#FFFFFF',
            ],
            'gris' => [
                'nombre' => 'Gris carbón',
                'aside' => '#2F3640',
                'activo' => '#4A5568',
                'hover' => '#4A5568',
                'texto' => '#FFFFFF',
            ],
            'naranja' => [
                'nombre' => 'Naranja tierra',
                'aside' => '#8A4B12',
                'activo' => '#B5651D',
                'hover' => '#B5651D',
                'texto' => '#FFFFFF',
            ],
            'rojo' => [
                'nombre' => 'Rojo institucional',
                'aside' => '#8B1E1E',
                'activo' => '#B52B2B',
                'hover' => '#B52B2B',
                'texto' => '#FFFFFF',
            ],
            'indigo' => [
                'nombre' => 'Índigo',
                'aside' => '#283593',
                'activo' => '#3F51B5',
                'hover' => '#3F51B5',
                'texto' => '#FFFFFF',
            ],
        ];
    }

    /**
     * Tamaños de fuente tipo Word, en puntos. La clave es el valor que se
     * guarda y el valor es la etiqueta que ve el administrador.
     *
     * @return array<string, string>
     */
    public static function fuentes(): array
    {
        return [
            '8' => '8 pt',
            '9' => '9 pt',
            '10' => '10 pt',
            '11' => '11 pt (predeterminado Word)',
            '12' => '12 pt',
            '14' => '14 pt',
            '16' => '16 pt',
            '18' => '18 pt',
            '20' => '20 pt',
            '24' => '24 pt',
            '28' => '28 pt',
            '36' => '36 pt',
        ];
    }

    /**
     * Lee la apariencia vigente. Si la tabla `ajustes` aún no existe (por
     * ejemplo durante una instalación nueva), devuelve los valores por defecto.
     *
     * @return array{clave: string, nombre: string, aside: string, activo: string, hover: string, texto: string, fuente_pt: string}
     */
    public static function actual(): array
    {
        $clave = self::TEMA_POR_DEFECTO;
        $fuente = self::FUENTE_POR_DEFECTO;

        try {
            $valores = Ajuste::query()
                ->whereIn('clave', [self::AJUSTE_TEMA, self::AJUSTE_FUENTE])
                ->pluck('valor', 'clave');

            $clave = (string) ($valores[self::AJUSTE_TEMA] ?? self::TEMA_POR_DEFECTO);
            $fuente = (string) ($valores[self::AJUSTE_FUENTE] ?? self::FUENTE_POR_DEFECTO);
        } catch (Throwable) {
            // La tabla todavía no existe: se usa la apariencia institucional.
        }

        if (! array_key_exists($clave, self::paletas())) {
            $clave = self::TEMA_POR_DEFECTO;
        }

        if (! array_key_exists($fuente, self::fuentes())) {
            $fuente = self::FUENTE_POR_DEFECTO;
        }

        return ['clave' => $clave] + self::paletas()[$clave] + ['fuente_pt' => $fuente];
    }

    /**
     * Guarda la paleta y el tamaño de fuente elegidos por el administrador.
     * Se usa `updateOrCreate` para no duplicar claves en `ajustes`.
     */
    public static function guardar(string $clave, string $fuentePt): void
    {
        Ajuste::guardar(self::AJUSTE_TEMA, $clave);
        Ajuste::guardar(self::AJUSTE_FUENTE, $fuentePt);
    }
}
