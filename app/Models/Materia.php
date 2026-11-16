<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Materia extends Model
{
    use HasFactory;

    protected $fillable = ['nombre', 'area', 'codigo', 'color_tarjeta', 'activo'];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }

    public function clasesDocentes()
    {
        return $this->hasMany(ClaseDocente::class);
    }

    public function unidades()
    {
        return $this->hasMany(Unidad::class);
    }

    /** Años de educación donde se dicta la materia. */
    public function cursos()
    {
        return $this->belongsToMany(Curso::class, 'curso_materias')
            ->withPivot(['periodos_semanales', 'orden'])
            ->withTimestamps();
    }

    public function malla()
    {
        return $this->hasMany(CursoMateria::class);
    }
}
