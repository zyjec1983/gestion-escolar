<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Catalogo oficial de diagnosticos del DECE (CIE-10 / MSP / MinEduc).
 * Solo los registros activos se ofrecen en los formularios.
 */
class DeceDiagnostico extends Model
{
    public const CATEGORIAS = [
        'Trastornos del Neurodesarrollo',
        'Trastornos del Aprendizaje',
        'Discapacidad Intelectual / Sensorial',
        'Factores Psicosociales',
        'Trastornos del Estado de Ánimo',
        'Otros',
    ];

    protected $table = 'dece_diagnosticos';

    protected $fillable = ['codigo', 'nombre', 'categoria', 'activo'];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }

    public function nee()
    {
        return $this->hasMany(DeceNee::class, 'diagnostico_id');
    }

    public function scopeActivos(Builder $query): Builder
    {
        return $query->where('activo', true);
    }

    public function scopeBuscar(Builder $query, ?string $termino): Builder
    {
        if (! $termino) {
            return $query;
        }

        return $query->where(function (Builder $sub) use ($termino) {
            $sub->where('codigo', 'like', "%{$termino}%")
                ->orWhere('nombre', 'like', "%{$termino}%")
                ->orWhere('categoria', 'like', "%{$termino}%");
        });
    }

    public function etiqueta(): string
    {
        return trim($this->codigo.' — '.$this->nombre);
    }
}
