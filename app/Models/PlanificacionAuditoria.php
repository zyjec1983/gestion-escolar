<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use RuntimeException;

/**
 * Registro inmutable de las intervenciones administrativas sobre una
 * planificacion. No tiene endpoints de edicion ni borrado y el propio modelo
 * bloquea cualquier intento de actualizar o eliminar un asiento existente.
 */
class PlanificacionAuditoria extends Model
{
    public const ACCION_AUTORIZACION = 'intervencion_autorizada';

    public const ACCION_ACTUALIZACION = 'actualizacion_administrativa';

    protected $table = 'planificacion_auditorias';

    protected $fillable = [
        'unidad_id',
        'grupo_id',
        'unidad_titulo',
        'usuario_id',
        'usuario_nombre',
        'usuario_rol',
        'accion',
        'motivo',
        'datos_anteriores',
        'datos_nuevos',
        'ip',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'datos_anteriores' => 'array',
            'datos_nuevos' => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function () {
            throw new RuntimeException('La bitácora de auditoría es inalterable.');
        });

        static::deleting(function () {
            throw new RuntimeException('La bitácora de auditoría es inalterable.');
        });
    }

    public function unidad()
    {
        return $this->belongsTo(Unidad::class);
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function accionLabel(): string
    {
        return match ($this->accion) {
            self::ACCION_AUTORIZACION => 'Intervención autorizada',
            self::ACCION_ACTUALIZACION => 'Actualización administrativa',
            default => $this->accion,
        };
    }
}
