<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Cita del DECE con el representante legal. Es independiente del modulo de
 * citas de inspeccion para no mezclar la agenda disciplinaria con la agenda
 * de consejeria.
 */
class DeceCita extends Model
{
    public const ESTADO_PROGRAMADA = 'programada';

    public const ESTADO_ASISTIO = 'asistio';

    public const ESTADO_NO_ASISTIO = 'no_asistio';

    public const ESTADO_CANCELADA = 'cancelada';

    public const ESTADOS = [
        self::ESTADO_PROGRAMADA => 'Programada',
        self::ESTADO_ASISTIO => 'Asistió',
        self::ESTADO_NO_ASISTIO => 'No asistió',
        self::ESTADO_CANCELADA => 'Cancelada',
    ];

    protected $table = 'dece_citas';

    protected $fillable = [
        'alumno_id', 'representante_user_id', 'profesional_id', 'fecha_hora',
        'duracion_minutos', 'motivo', 'estado', 'observaciones', 'notificado_en',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'fecha_hora' => 'datetime',
            'duracion_minutos' => 'integer',
            'notificado_en' => 'datetime',
        ];
    }

    public function alumno()
    {
        return $this->belongsTo(Alumno::class);
    }

    public function representante()
    {
        return $this->belongsTo(User::class, 'representante_user_id');
    }

    public function profesional()
    {
        return $this->belongsTo(User::class, 'profesional_id');
    }

    public function creador()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function atenciones()
    {
        return $this->hasMany(DeceAtencion::class, 'cita_id');
    }

    public function scopeProximas(Builder $query): Builder
    {
        return $query->where('estado', self::ESTADO_PROGRAMADA)
            ->where('fecha_hora', '>=', now());
    }

    public function scopeProgramadas(Builder $query): Builder
    {
        return $query->where('estado', self::ESTADO_PROGRAMADA);
    }

    public function scopeDelDia(Builder $query): Builder
    {
        return $query->whereDate('fecha_hora', now()->toDateString());
    }

    public function estaProgramada(): bool
    {
        return $this->estado === self::ESTADO_PROGRAMADA;
    }

    public function puedeRegistrar(): bool
    {
        return $this->estaProgramada() && $this->fecha_hora->lte(now());
    }

    public function estadoLabel(): string
    {
        return self::ESTADOS[$this->estado] ?? $this->estado;
    }

    public function horaFin(): string
    {
        return $this->fecha_hora->copy()->addMinutes($this->duracion_minutos)->format('H:i');
    }
}
