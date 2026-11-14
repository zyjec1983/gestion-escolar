<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/**
 * Integrante de la Comunidad Educativa.
 *
 * Qué hace: guarda cargo, nombre, responsabilidad, área y fotografía. El
 * portal agrupa las tarjetas por `area`, de modo que el administrador puede
 * crear grupos como Matemáticas, Inglés o DECE y añadir varias filas.
 * Conecta con: Admin\ComunidadController y portal/comunidad.blade.php.
 * Rutas: CRUD /admin/comunidad.
 */
class ComunidadMiembro extends Model
{
    protected $table = 'comunidad_miembros';

    protected $fillable = [
        'cargo', 'nombre', 'responsabilidad', 'area', 'foto_path', 'orden', 'activo',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
            'orden' => 'integer',
        ];
    }

    /** URL pública de la fotografía, si existe. */
    public function fotoUrl(): ?string
    {
        return $this->foto_path ? Storage::url($this->foto_path) : null;
    }
}
