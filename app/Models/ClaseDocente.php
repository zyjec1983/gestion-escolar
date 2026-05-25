<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClaseDocente extends Model
{
    protected $table = 'clases_docentes';

    protected $fillable = ['user_id', 'materia_id', 'curso_id', 'paralelo_id', 'color_tarjeta'];

    public function docente()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function materia()
    {
        return $this->belongsTo(Materia::class);
    }

    public function curso()
    {
        return $this->belongsTo(Curso::class);
    }

    public function paralelo()
    {
        return $this->belongsTo(Paralelo::class);
    }

    public function agendas()
    {
        return $this->hasMany(Agenda::class);
    }

    public function insumosEvaluacion()
    {
        return $this->hasMany(InsumoEvaluacion::class);
    }

    public function permisosNotas()
    {
        return $this->hasMany(PermisoNota::class);
    }
}
