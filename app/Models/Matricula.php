<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Matricula extends Model
{
    use HasFactory;

    public const ESTADO_PROVISIONAL = 'provisional';

    public const ESTADO_DEFINITIVA = 'definitiva';

    protected $fillable = [
        'codigo_matricula', 'alumno_id', 'curso_id', 'paralelo_id', 'periodo_id', 'especializacion', 'estado',
        'firma_rectora_completada', 'firma_secretaria_completada', 'firma_representante_completada',
    ];

    protected function casts(): array
    {
        return [
            'firma_rectora_completada' => 'boolean',
            'firma_secretaria_completada' => 'boolean',
            'firma_representante_completada' => 'boolean',
        ];
    }

    public function alumno()
    {
        return $this->belongsTo(Alumno::class);
    }

    public function curso()
    {
        return $this->belongsTo(Curso::class);
    }

    public function paralelo()
    {
        return $this->belongsTo(Paralelo::class);
    }

    public function expedientesTraslado()
    {
        return $this->hasMany(ExpedienteTraslado::class, 'matricula_id');
    }

    public function esProvisional(): bool
    {
        return $this->estado === self::ESTADO_PROVISIONAL;
    }

    public function periodo()
    {
        return $this->belongsTo(PeriodoLectivo::class, 'periodo_id');
    }
}
