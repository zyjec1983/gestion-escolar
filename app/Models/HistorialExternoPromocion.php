<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use RuntimeException;

class HistorialExternoPromocion extends Model
{
    protected $table = 'historial_externo_promociones';

    protected $fillable = [
        'expediente_id', 'anio_lectivo', 'nivel', 'promedio', 'escala', 'observacion',
    ];

    protected function casts(): array
    {
        return [
            'promedio' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (self $modelo) {
            if ($modelo->expediente?->estaCerrado()) {
                throw new RuntimeException('El historial externo es inmutable tras el cierre del expediente.');
            }
        });

        static::deleting(function (self $modelo) {
            if ($modelo->expediente?->estaCerrado()) {
                throw new RuntimeException('El historial externo es inmutable tras el cierre del expediente.');
            }
        });
    }

    public function expediente()
    {
        return $this->belongsTo(ExpedienteTraslado::class, 'expediente_id');
    }
}
