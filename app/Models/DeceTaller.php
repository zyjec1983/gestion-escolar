<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Taller o actividad colectiva de prevencion y orientacion. Puede dirigirse a
 * estudiantes de un curso, a representantes o a docentes.
 */
class DeceTaller extends Model
{
    public const TIPOS = [
        'prevencion' => 'Prevención (drogas, bullying, etc.)',
        'orientacion_vocacional' => 'Orientación vocacional',
        'educacion_afectivo_sexual' => 'Educación afectivo-sexual',
        'escuela_de_padres' => 'Escuela de padres',
        'convivencia' => 'Convivencia y resolución de conflictos',
        'otro' => 'Otro',
    ];

    public const DIRIGIDOS = [
        'estudiantes' => 'Estudiantes',
        'representantes' => 'Representantes',
        'docentes' => 'Docentes',
    ];

    protected $table = 'dece_talleres';

    protected $fillable = [
        'titulo', 'tipo', 'descripcion', 'fecha', 'dirigido_a', 'curso_id',
        'facilitador', 'lugar', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
        ];
    }

    public function curso()
    {
        return $this->belongsTo(Curso::class);
    }

    public function creador()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function asistentes()
    {
        return $this->hasMany(DeceTallerAsistente::class, 'taller_id');
    }

    public function archivos()
    {
        return $this->morphMany(DeceArchivo::class, 'archivoable');
    }

    public function scopeBuscar(Builder $query, ?string $termino): Builder
    {
        if (! $termino) {
            return $query;
        }

        return $query->where(function (Builder $sub) use ($termino) {
            $sub->where('titulo', 'like', "%{$termino}%")
                ->orWhere('facilitador', 'like', "%{$termino}%")
                ->orWhere('lugar', 'like', "%{$termino}%");
        });
    }

    public function tipoLabel(): string
    {
        return self::TIPOS[$this->tipo] ?? $this->tipo;
    }

    public function dirigidoLabel(): string
    {
        return self::DIRIGIDOS[$this->dirigido_a] ?? $this->dirigido_a;
    }
}
