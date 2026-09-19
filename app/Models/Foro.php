<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Foro extends Model
{
    /** @use HasFactory<\Database\Factories\ForoFactory> */
    use HasFactory;

    protected $fillable = [
        'clase_docente_id',
        'titulo',
        'pregunta',
        'links',
        'fecha_inicio',
        'fecha_cierre',
    ];

    protected $casts = [
        'links' => 'array',
        'fecha_inicio' => 'datetime',
        'fecha_cierre' => 'datetime',
    ];

    public function claseDocente()
    {
        return $this->belongsTo(ClaseDocente::class);
    }

    public function intervenciones(): HasMany
    {
        return $this->hasMany(ForoIntervencion::class);
    }

    public function estado(): string
    {
        $now = now();

        if ($this->fecha_inicio && $now->lt($this->fecha_inicio)) {
            return 'no_iniciado';
        }

        if ($this->fecha_cierre && $now->gt($this->fecha_cierre)) {
            return 'cerrado';
        }

        return 'activo';
    }
}