<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipoObservacion extends Model
{
    protected $table = 'tipos_observacion';

    protected $fillable = ['codigo', 'nombre', 'icono'];

    public function observaciones()
    {
        return $this->hasMany(Observacion::class);
    }
}
