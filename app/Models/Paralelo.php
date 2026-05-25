<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Paralelo extends Model
{
    use HasFactory;

    protected $fillable = ['nombre'];

    public function clasesDocentes()
    {
        return $this->hasMany(ClaseDocente::class);
    }

    public function matriculas()
    {
        return $this->hasMany(Matricula::class);
    }
}
