<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Registro de un estudiante con necesidades educativas especificas (NEE).
 * Puede ser un estudiante del padron (interno) o un aspirante externo que
 * todavia no tiene matricula en la institucion.
 */
class DeceNee extends Model
{
    public const PROCEDENCIA_INTERNO = 'interno';

    public const PROCEDENCIA_EXTERNO = 'externo';

    public const PROCEDENCIAS = [
        self::PROCEDENCIA_INTERNO => 'Estudiante del plantel',
        self::PROCEDENCIA_EXTERNO => 'Institución anterior / externo',
    ];

    public const GRADOS_ADAPTACION = [
        1 => 'Grado 1 — Adaptación de acceso',
        2 => 'Grado 2 — Adaptación significativa',
        3 => 'Grado 3 — Adaptación muy significativa',
    ];

    protected $table = 'dece_nee';

    protected $fillable = [
        'alumno_id', 'nombre_externo', 'curso_externo', 'procedencia',
        'diagnostico_id', 'diagnostico_detalle', 'profesional_evaluador',
        'fecha_evaluacion', 'grado_adaptacion', 'requiere_informe',
        'observaciones', 'activo', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'fecha_evaluacion' => 'date',
            'grado_adaptacion' => 'integer',
            'requiere_informe' => 'boolean',
            'activo' => 'boolean',
        ];
    }

    public function alumno()
    {
        return $this->belongsTo(Alumno::class);
    }

    public function diagnostico()
    {
        return $this->belongsTo(DeceDiagnostico::class, 'diagnostico_id');
    }

    public function creador()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function archivos()
    {
        return $this->morphMany(DeceArchivo::class, 'archivoable');
    }

    public function scopeActivos(Builder $query): Builder
    {
        return $query->where('activo', true);
    }

    public function scopeBuscar(Builder $query, ?string $termino): Builder
    {
        if (! $termino) {
            return $query;
        }

        return $query->where(function (Builder $sub) use ($termino) {
            $sub->where('nombre_externo', 'like', "%{$termino}%")
                ->orWhere('curso_externo', 'like', "%{$termino}%")
                ->orWhere('diagnostico_detalle', 'like', "%{$termino}%")
                ->orWhereHas('alumno', function (Builder $alumno) use ($termino) {
                    $alumno->where('nombres', 'like', "%{$termino}%")
                        ->orWhere('apellidos', 'like', "%{$termino}%")
                        ->orWhere('numero_cedula', 'like', "%{$termino}%");
                });
        });
    }

    public function nombreEstudiante(): string
    {
        return $this->alumno?->nombreCompleto() ?: ($this->nombre_externo ?: 'Estudiante sin nombre');
    }

    public function procedenciaLabel(): string
    {
        return self::PROCEDENCIAS[$this->procedencia] ?? $this->procedencia;
    }

    public function gradoLabel(): string
    {
        return $this->grado_adaptacion
            ? (self::GRADOS_ADAPTACION[$this->grado_adaptacion] ?? 'Grado '.$this->grado_adaptacion)
            : 'Sin adaptación registrada';
    }
}
