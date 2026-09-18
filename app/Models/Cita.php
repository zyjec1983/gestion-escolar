<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Cita extends Model
{
    protected $fillable = [
        'solicitante_type', 'solicitante_id', 'docente_id', 'alumno_id',
        'fecha_inicio', 'fecha_fin', 'estado', 'motivo',
        'resultado', 'dudas_aclaradas', 'evidencia', 'registrada_por', 'registrada_en',
    ];

    protected function casts(): array
    {
        return [
            'fecha_inicio' => 'datetime',
            'fecha_fin' => 'datetime',
            'dudas_aclaradas' => 'boolean',
            'registrada_en' => 'datetime',
        ];
    }

    public function solicitante()
    {
        return $this->morphTo();
    }

    public function docente()
    {
        return $this->belongsTo(User::class, 'docente_id');
    }

    public function registrador()
    {
        return $this->belongsTo(User::class, 'registrada_por');
    }

    public function alumno()
    {
        return $this->belongsTo(Alumno::class);
    }

    public function scopePendientes(Builder $query): Builder
    {
        return $query->where('estado', 'pendiente')->where('fecha_inicio', '>', now());
    }

    public function scopePorRegistrar(Builder $query): Builder
    {
        return $query
            ->where('estado', 'pendiente')
            ->where('fecha_inicio', '<=', now())
            ->whereNull('resultado');
    }

    public function registrada(): bool
    {
        return $this->resultado !== null;
    }
}