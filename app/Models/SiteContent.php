<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/**
 * Contenido editable del portal institucional.
 *
 * Qué hace: almacena textos, enlaces, booleanos e imágenes en formato
 * clave/valor. Welcome y el login leen estos valores; el administrador los
 * edita en "Sitio web".
 * Conecta con: Admin\SitioController, App\Support\PortalContenido y
 * resources/views/welcome.blade.php.
 * Rutas: GET/POST /admin/sitio.
 */
class SiteContent extends Model
{
    protected $table = 'site_contents';

    protected $fillable = ['clave', 'valor', 'tipo', 'grupo', 'etiqueta', 'orden'];

    /** Caché en memoria para no repetir consultas en la misma petición. */
    private static ?array $mapa = null;

    /** Limpia la caché cuando se guarda desde el panel. */
    public static function limpiarCache(): void
    {
        self::$mapa = null;
    }

    /** Todos los valores indexados por clave. */
    public static function mapa(): array
    {
        if (self::$mapa !== null) {
            return self::$mapa;
        }

        return self::$mapa = self::query()->pluck('valor', 'clave')->all();
    }

    /** Valor de un campo, con respaldo en los valores por defecto. */
    public static function valor(string $clave, mixed $porDefecto = null): mixed
    {
        $valor = self::mapa()[$clave] ?? null;

        return ($valor === null || $valor === '') ? $porDefecto : $valor;
    }

    /** Interpreta un campo como sí/no. */
    public static function bool(string $clave, bool $porDefecto = false): bool
    {
        $valor = self::valor($clave);

        if ($valor === null) {
            return $porDefecto;
        }

        return filter_var($valor, FILTER_VALIDATE_BOOL);
    }

    /** URL pública de una imagen guardada en el disco público. */
    public static function imagen(string $clave): ?string
    {
        $valor = self::valor($clave);

        if (! $valor) {
            return null;
        }

        if (str_starts_with($valor, 'http://') || str_starts_with($valor, 'https://') || str_starts_with($valor, '/')) {
            return $valor;
        }

        return Storage::url($valor);
    }

    /**
     * Guarda un valor y refresca la caché.
     *
     * @param  array{tipo?:string, grupo?:string, etiqueta?:string, orden?:int}  $meta
     */
    public static function guardar(string $clave, mixed $valor, array $meta = []): void
    {
        $registro = self::firstOrNew(['clave' => $clave]);
        $registro->valor = is_bool($valor) ? ($valor ? '1' : '0') : $valor;
        $registro->tipo = $registro->tipo ?: ($meta['tipo'] ?? 'texto');
        $registro->grupo = $registro->grupo ?: ($meta['grupo'] ?? 'sitio');
        $registro->etiqueta = $registro->etiqueta ?: ($meta['etiqueta'] ?? $clave);
        $registro->orden = $registro->orden ?: ($meta['orden'] ?? 0);
        $registro->save();

        self::limpiarCache();
    }
}
