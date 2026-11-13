<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Seguimiento periodico de un acuerdo de compromiso. Permite reconstruir la
 * evolucion del caso sin alterar el acuerdo original.
 */
class DeceAcuerdoSeguimiento extends Model
{
    public const ESTADO_EN_PROCESO = 'en_proceso';

    public const ESTADO_CUMPLIDO = 'cumplido';

    public const ESTADO_INCUMPLIDO = 'incumplido';

    public const ESTADOS = [
        self::ESTADO_EN_PROCESO => 'En proceso',
        self::ESTADO_CUMPLIDO => 'Cumplido',
        self::ESTADO_INCUMPLIDO => 'Incumplido',
    ];

    protected $table = 'dece_acuerdo_seguimientos';

    protected $fillable = ['acuerdo_id', 'fecha', 'estado', 'observacion', 'registrado_por'];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
        ];
    }

    public function acuerdo()
    {
        return $this->belongsTo(DeceAcuerdo::class, 'acuerdo_id');
    }

    public function registrador()
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }

    public function estadoLabel(): string
    {
        return self::ESTADOS[$this->estado] ?? $this->estado;
    }
}
