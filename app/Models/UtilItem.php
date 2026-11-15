<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Material de la lista de útiles.
 *
 * Qué hace: una fila por artículo, con su lista, nivel educativo,
 * especificación, cantidad y materia de uso. Conserva `nivel` y `area` para
 * los artículos antiguos que todavía no pertenecen a una lista.
 * Conecta con: App\Models\ListaUtil, Admin\SitioController y
 * portal/utiles.blade.php.
 * Rutas: CRUD /admin/sitio/utiles/listas.
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
        'lista_id', 'nivel', 'nombre', 'especificacion', 'cantidad', 'area', 'materia', 'orden',
    ];

    protected function casts(): array
    {
        return ['orden' => 'integer'];
    }

    /** Lista a la que pertenece el artículo. */
    public function lista(): BelongsTo
    {
        return $this->belongsTo(ListaUtil::class, 'lista_id');
    }

    /** Materia visible del artículo, con respaldo en el área antigua. */
    public function materiaVisible(): string
    {
        return (string) ($this->materia ?: $this->area ?: '');
    }
}
