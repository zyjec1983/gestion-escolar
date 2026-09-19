<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AsignacionGrupoAlumno extends Model
{
    protected $table = 'asignacion_grupo_alumnos';

    protected $fillable = ['asignacion_id', 'alumno_id', 'grupo'];

    public function asignacion(): BelongsTo
    {
        return $this->belongsTo(AsignacionGrupo::class);
    }

    public function alumno(): BelongsTo
    {
        return $this->belongsTo(Alumno::class);
    }
}