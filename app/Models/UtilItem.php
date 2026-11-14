<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Material de la lista de útiles.
 *
 * Qué hace: una fila por artículo, con nivel educativo, especificación,
 * cantidad y área de uso.
 * Conecta con: Admin\SitioController y welcome.blade.php.
 * Rutas: CRUD /admin/sitio/utiles.
 */
class UtilItem extends Model
{
    protected $table = 'utiles_items';

    /** Niveles disponibles y su etiqueta visible. */
    public const NIVELES = [
        'inicial' => 'Educación Inicial',
        'elemental' => 'Básica Elemental',
        'media' => 'Básica Media',
        'superior' => 'Básica Superior',
        'bachillerato' => 'Bachillerato (BGU)',
    ];

    protected $fillable = [
        'nivel', 'nombre', 'especificacion', 'cantidad', 'area', 'orden',
    ];

    protected function casts(): array
    {
        return ['orden' => 'integer'];
    }
}
