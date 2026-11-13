<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Ficha de atencion individual del DECE. La relatoria es confidencial y solo
 * la consultan los profesionales del departamento y la administracion.
 */
class DeceAtencion extends Model
{
    public const MOTIVOS = [
        'Bajo rendimiento académico',
        'Conflicto entre pares / convivencia',
        'Soporte emocional / familiar',
        'Alertas de conducta',
        'Riesgo psicosocial',
        'Orientación vocacional',
        'Seguimiento NEE',
        'Otro',
    ];

    public const ESTADO_ABIERTA = 'abierta';

    public const ESTADO_CERRADA = 'cerrada';

    protected $table = 'dece_atenciones';

    protected $fillable = [
        'alumno_id', 'profesional_id', 'cita_id', 'fecha_atencion', 'motivo',
        'historia_narrada', 'acuerdos', 'estado', 'confidencial',
    ];

    protected function casts(): array
    {
        return [
            'fecha_atencion' => 'datetime',
            'confidencial' => 'boolean',
        ];
    }

    public function alumno()
    {
        return $this->belongsTo(Alumno::class);
    }

    public function profesional()
    {
        return $this->belongsTo(User::class, 'profesional_id');
    }

    public function cita()
    {
        return $this->belongsTo(DeceCita::class, 'cita_id');
    }

    public function archivos()
    {
        return $this->morphMany(DeceArchivo::class, 'archivoable');
    }

    /**
     * Acuerdos formales vinculados a la ficha. El nombre evita chocar con la
     * columna de texto "acuerdos" (recomendaciones de la entrevista).
     */
    public function acuerdosFormales()
    {
        return $this->hasMany(DeceAcuerdo::class, 'atencion_id');
    }

    public function scopeBuscar(Builder $query, ?string $termino): Builder
    {
        if (! $termino) {
            return $query;
        }

        return $query->where(function (Builder $sub) use ($termino) {
            $sub->where('motivo', 'like', "%{$termino}%")
                ->orWhere('historia_narrada', 'like', "%{$termino}%")
                ->orWhereHas('alumno', function (Builder $alumno) use ($termino) {
                    $alumno->where('nombres', 'like', "%{$termino}%")
                        ->orWhere('apellidos', 'like', "%{$termino}%")
                        ->orWhere('numero_cedula', 'like', "%{$termino}%")
                        ->orWhere('codigo', 'like', "%{$termino}%");
                });
        });
    }

    public function estaCerrada(): bool
    {
        return $this->estado === self::ESTADO_CERRADA;
    }

    public function estadoLabel(): string
    {
        return $this->estaCerrada() ? 'Cerrada' : 'Abierta';
    }
}
