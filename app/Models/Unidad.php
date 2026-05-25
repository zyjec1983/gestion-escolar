<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Unidad extends Model
{
    protected $table = 'unidades';

    protected $fillable = ['materia_id', 'titulo', 'fecha_inicio', 'fecha_fin'];

    protected function casts(): array
    {
        return [
            'fecha_inicio' => 'date',
            'fecha_fin' => 'date',
        ];
    }

    public function materia()
    {
        return $this->belongsTo(Materia::class);
    }

    public function permisosNotas()
    {
        return $this->hasMany(PermisoNota::class);
    }
}
