<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PermisoNota extends Model
{
    protected $table = 'permisos_notas';

    protected $fillable = [
        'codigo_permiso', 'clase_docente_id', 'unidad_id',
        'fecha_inicio', 'fecha_fin', 'user_asigno_id',
    ];

    protected function casts(): array
    {
        return [
            'fecha_inicio' => 'datetime',
            'fecha_fin' => 'datetime',
        ];
    }

    public function claseDocente()
    {
        return $this->belongsTo(ClaseDocente::class);
    }

    public function unidad()
    {
        return $this->belongsTo(Unidad::class);
    }

    public function userAsigno()
    {
        return $this->belongsTo(User::class, 'user_asigno_id');
    }
}
