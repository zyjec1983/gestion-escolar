<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class TipoObservacion extends Model
{
    public const CLASIFICACIONES = [
        'positiva' => 'Disciplinaria positiva',
        'leve' => 'Falta leve',
        'grave' => 'Falta grave',
        'muy_grave' => 'Falta muy grave',
    ];

    protected $table = 'tipos_observacion';

    protected $fillable = ['codigo', 'nombre', 'icono', 'clasificacion', 'articulo_ley', 'activo'];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }

    public function observaciones()
    {
        return $this->hasMany(Observacion::class);
    }

    /** Solo los tipos habilitados, usados por los selects de docente e inspeccion. */
    public function scopeActivos(Builder $query): Builder
    {
        return $query->where('activo', true);
    }

    /** Etiqueta legible de la clasificacion. */
    public function etiquetaClasificacion(): string
    {
        return self::CLASIFICACIONES[$this->clasificacion] ?? $this->clasificacion;
    }
}
