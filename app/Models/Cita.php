<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cita extends Model
{
    protected $fillable = [
        'solicitante_type', 'solicitante_id', 'docente_id',
        'fecha_inicio', 'fecha_fin', 'estado', 'motivo',
    ];

    protected function casts(): array
    {
        return [
            'fecha_inicio' => 'datetime',
            'fecha_fin' => 'datetime',
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
}
