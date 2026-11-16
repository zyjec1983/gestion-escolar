<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Materia que se dicta en un año de educación.
 *
 * Qué hace: guarda la malla curricular de cada curso con sus períodos
 * semanales de 40 minutos y el orden en que se muestra.
 * Conecta con: App\Models\Curso, App\Models\Materia y
 * App\Http\Controllers\Admin\MateriaController.
 */
class CursoMateria extends Model
{
    use HasFactory;

    protected $table = 'curso_materias';

    protected $fillable = ['curso_id', 'materia_id', 'periodos_semanales', 'orden'];

    public function curso(): BelongsTo
    {
        return $this->belongsTo(Curso::class);
    }

    public function materia(): BelongsTo
    {
        return $this->belongsTo(Materia::class);
    }

    /** Horas reloj semanales equivalentes a los períodos de 40 minutos. */
    public function horasReloj(): float
    {
        return round(((int) $this->periodos_semanales * 40) / 60, 2);
    }
}
