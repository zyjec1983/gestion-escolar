<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Hito de la línea de tiempo histórica.
 *
 * Qué hace: representa un año, título y descripción de "Nuestra historia".
 * Conecta con: Admin\SitioController y welcome.blade.php.
 * Rutas: POST/PUT/DELETE /admin/sitio/historia.
 */
class HistoriaHito extends Model
{
    protected $table = 'historia_hitos';

    protected $fillable = ['anio', 'titulo', 'descripcion', 'orden'];

    protected function casts(): array
    {
        return ['orden' => 'integer'];
    }
}
