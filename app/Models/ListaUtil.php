<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Lista de útiles de un nivel o curso.
 *
 * Qué hace: agrupa los artículos registrados para un curso (por ejemplo
 * "Octavo" o "1ro Ciencias") y guarda si la lista ya fue terminada o sigue
 * en edición.
 * Conecta con: App\Models\UtilItem, Admin\SitioController y
 * PortalController@utiles.
 * Rutas: CRUD /admin/sitio/utiles/listas.
 */
class ListaUtil extends Model
{
    protected $table = 'listas_utiles';

    protected $fillable = ['nombre', 'finalizada', 'orden'];

    protected function casts(): array
    {
        return [
            'finalizada' => 'boolean',
            'orden' => 'integer',
        ];
    }

    /** Artículos de la lista, en el orden en que se capturaron. */
    public function items(): HasMany
    {
        return $this->hasMany(UtilItem::class, 'lista_id')->orderBy('orden')->orderBy('id');
    }

    /** Suma las cantidades numéricas de los artículos. */
    public function totalUnidades(): int
    {
        return $this->items->sum(function (UtilItem $item) {
            return (int) preg_replace('/\D.*$/', '', (string) $item->cantidad);
        });
    }
}
