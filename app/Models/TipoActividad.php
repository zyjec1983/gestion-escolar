<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipoActividad extends Model
{
    protected $table = 'tipo_actividades';

    protected $fillable = ['codigo', 'nombre'];

    public function agendas()
    {
        return $this->hasMany(Agenda::class);
    }
}
