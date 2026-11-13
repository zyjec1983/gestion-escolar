<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Derivacion formal de un caso. Las internas nacen de docentes o inspeccion;
 * las externas se remiten a la red de proteccion con oficio y plazo.
 */
class DeceDerivacion extends Model
{
    public const TIPO_INTERNA = 'interna';

    public const TIPO_EXTERNA = 'externa';

    public const TIPOS = [
        self::TIPO_INTERNA => 'Interna (docente / inspección)',
        self::TIPO_EXTERNA => 'Externa (red de protección)',
    ];

    public const ESTADO_PENDIENTE = 'pendiente';

    public const ESTADO_EN_PROCESO = 'en_proceso';

    public const ESTADO_ATENDIDA = 'atendida';

    public const ESTADO_CERRADA = 'cerrada';

    public const ESTADOS = [
        self::ESTADO_PENDIENTE => 'Pendiente',
        self::ESTADO_EN_PROCESO => 'En proceso',
        self::ESTADO_ATENDIDA => 'Atendida',
        self::ESTADO_CERRADA => 'Cerrada',
    ];

    protected $table = 'dece_derivaciones';

    protected $fillable = [
        'alumno_id', 'tipo', 'motivo', 'descripcion', 'derivado_por',
        'entidad_externa', 'oficio', 'fecha_derivacion', 'fecha_limite',
        'estado', 'respuesta',
    ];

    protected function casts(): array
    {
        return [
            'fecha_derivacion' => 'date',
            'fecha_limite' => 'date',
        ];
    }

    public function alumno()
    {
        return $this->belongsTo(Alumno::class);
    }

    public function derivador()
    {
        return $this->belongsTo(User::class, 'derivado_por');
    }

    public function scopeAbiertas(Builder $query): Builder
    {
        return $query->whereIn('estado', [
            self::ESTADO_PENDIENTE,
            self::ESTADO_EN_PROCESO,
        ]);
    }

    public function scopeBuscar(Builder $query, ?string $termino): Builder
    {
        if (! $termino) {
            return $query;
        }

        return $query->where(function (Builder $sub) use ($termino) {
            $sub->where('motivo', 'like', "%{$termino}%")
                ->orWhere('entidad_externa', 'like', "%{$termino}%")
                ->orWhere('oficio', 'like', "%{$termino}%")
                ->orWhereHas('alumno', function (Builder $alumno) use ($termino) {
                    $alumno->where('nombres', 'like', "%{$termino}%")
                        ->orWhere('apellidos', 'like', "%{$termino}%")
                        ->orWhere('numero_cedula', 'like', "%{$termino}%");
                });
        });
    }

    public function estaVencida(): bool
    {
        return $this->fecha_limite !== null
            && $this->fecha_limite->lt(now()->startOfDay())
            && in_array($this->estado, [self::ESTADO_PENDIENTE, self::ESTADO_EN_PROCESO], true);
    }

    public function diasRestantes(): ?int
    {
        if (! $this->fecha_limite) {
            return null;
        }

        return (int) now()->startOfDay()->diffInDays($this->fecha_limite, false);
    }

    public function tipoLabel(): string
    {
        return self::TIPOS[$this->tipo] ?? $this->tipo;
    }

    public function estadoLabel(): string
    {
        return self::ESTADOS[$this->estado] ?? $this->estado;
    }
}
