<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Ventana de subida de notas.
 *
 * Qué hace: define fechas de apertura y cierre por parcial. El administrador
 * o la secretaría pueden activarla o desactivarla; el docente solo puede
 * guardar notas dentro de una ventana activa.
 * Conecta con: App\Services\ConfiguracionNotas, Docente\NotaController y
 * Admin\VentanaNotaController.
 * Rutas: CRUD /admin/ventanas-notas y POST /admin/ventanas-notas/{id}/estado.
 */
class VentanaNota extends Model
{
    protected $table = 'ventanas_notas';

    protected $fillable = [
        'parcial', 'fecha_inicio', 'fecha_fin', 'activo', 'user_id', 'descripcion',
    ];

    protected function casts(): array
    {
        return [
            'fecha_inicio' => 'datetime',
            'fecha_fin' => 'datetime',
            'activo' => 'boolean',
            'parcial' => 'integer',
        ];
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** Ventanas que están activas y dentro de su rango de fechas. */
    public function scopeVigente($query)
    {
        return $query->where('activo', true)
            ->where('fecha_inicio', '<=', now())
            ->where('fecha_fin', '>=', now());
    }

    /** Ventanas que aplican a un parcial (incluye la ventana general). */
    public function scopeParaParcial($query, int $parcial)
    {
        return $query->where(function ($q) use ($parcial) {
            $q->where('parcial', $parcial)->orWhereNull('parcial');
        });
    }
}
