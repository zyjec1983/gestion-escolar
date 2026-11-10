<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Nota extends Model
{
    protected $table = 'notas';

    protected $fillable = [
        'matricula_id', 'alumno_id', 'clase_docente_id', 'parcial', 'tipo_nota',
        'fii', 'ffi', 'fig', 'ffg',
        'proyecto_integrador', 'evaluacion_parcial', 'nota_examen', 'estado',
    ];

    protected function casts(): array
    {
        return [
            'parcial' => 'integer',
            'fii' => 'decimal:4',
            'ffi' => 'decimal:4',
            'fig' => 'decimal:4',
            'ffg' => 'decimal:4',
            'proyecto_integrador' => 'decimal:4',
            'evaluacion_parcial' => 'decimal:4',
            'nota_examen' => 'decimal:4',
        ];
    }

    public function matricula()
    {
        return $this->belongsTo(Matricula::class);
    }

    public function alumno()
    {
        return $this->belongsTo(Alumno::class);
    }

    public function claseDocente()
    {
        return $this->belongsTo(ClaseDocente::class);
    }

    public function auditorias()
    {
        return $this->hasMany(NotaAuditoria::class)->latest();
    }
}
