<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Materia extends Model
{
    protected $fillable = ['nombre', 'codigo', 'color_tarjeta'];

    public function clasesDocentes()
    {
        return $this->hasMany(ClaseDocente::class);
    }

    public function unidades()
    {
        return $this->hasMany(Unidad::class);
    }
}
