<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Asistencia extends Model
{
    protected $table = 'asistencia';

    protected $fillable = [
        'curso_id',
        'paralelo_id',
        'alumno_id',
        'fecha',
        'estado',
        'observacion',
        'clase_docente_id',
        'registrado_por',
        'editado_por',
        'editado_en',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'editado_en' => 'datetime',
        ];
    }

    public function curso()
    {
        return $this->belongsTo(Curso::class);
    }

    public function paralelo()
    {
        return $this->belongsTo(Paralelo::class);
    }

    public function alumno()
    {
        return $this->belongsTo(Alumno::class);
    }

    public function claseDocente()
    {
        return $this->belongsTo(ClaseDocente::class);
    }

    public function registradoPor()
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }

    public function editadoPor()
    {
        return $this->belongsTo(User::class, 'editado_por');
    }
}