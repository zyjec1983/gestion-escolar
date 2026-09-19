<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AsignacionGrupo extends Model
{
    protected $table = 'asignaciones_grupos';

    protected $fillable = ['clase_docente_id'];

    public function claseDocente(): BelongsTo
    {
        return $this->belongsTo(ClaseDocente::class);
    }

    public function integrantes(): HasMany
    {
        return $this->hasMany(AsignacionGrupoAlumno::class, 'asignacion_id');
    }
}