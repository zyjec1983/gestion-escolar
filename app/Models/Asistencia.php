<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Asistencia extends Model
{
    protected $table = 'asistencia';

    protected $fillable = [
        'clase_docente_id', 'alumno_id', 'fecha', 'estado', 'observacion',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
        ];
    }

    public function claseDocente()
    {
        return $this->belongsTo(ClaseDocente::class);
    }

    public function alumno()
    {
        return $this->belongsTo(Alumno::class);
    }
}
