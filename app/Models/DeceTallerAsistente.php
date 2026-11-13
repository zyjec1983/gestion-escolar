<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Asistencia de una persona a un taller del DECE. Se guarda el alumno o el
 * usuario cuando existe; si es un invitado externo queda solo el nombre.
 */
class DeceTallerAsistente extends Model
{
    protected $table = 'dece_taller_asistentes';

    protected $fillable = ['taller_id', 'alumno_id', 'user_id', 'nombre', 'asistio'];

    protected function casts(): array
    {
        return [
            'asistio' => 'boolean',
        ];
    }

    public function taller()
    {
        return $this->belongsTo(DeceTaller::class, 'taller_id');
    }

    public function alumno()
    {
        return $this->belongsTo(Alumno::class);
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function nombreCompleto(): string
    {
        return $this->alumno?->nombreCompleto()
            ?: $this->usuario?->nombreCompleto()
            ?: ($this->nombre ?: 'Asistente sin nombre');
    }
}
