<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use RuntimeException;

/**
 * Bitacora append-only del expediente de traslado. Guarda usuario, fecha,
 * IP y agente de usuario de cada accion sensible.
 */
class ExpedienteAuditoria extends Model
{
    public const ACCION_CREACION = 'creacion';

    public const ACCION_ACTUALIZACION = 'actualizacion';

    public const ACCION_DOCUMENTO_SUBIDO = 'documento_subido';

    public const ACCION_DOCUMENTO_ELIMINADO = 'documento_eliminado';

    public const ACCION_PROMOCION_AGREGADA = 'promocion_agregada';

    public const ACCION_PROMOCION_ELIMINADA = 'promocion_eliminada';

    public const ACCION_NOTA_AGREGADA = 'nota_parcial_agregada';

    public const ACCION_NOTA_ELIMINADA = 'nota_parcial_eliminada';

    public const ACCION_PAZ_Y_SALVO = 'paz_y_salvo_actualizado';

    public const ACCION_CIERRE = 'expediente_cerrado';

    protected $table = 'expediente_auditorias';

    protected $fillable = [
        'expediente_id', 'alumno_nombre',
        'usuario_id', 'usuario_nombre', 'usuario_rol',
        'accion', 'detalle', 'datos_anteriores', 'datos_nuevos', 'ip', 'user_agent',
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
            throw new RuntimeException('La bitacora del expediente es inalterable.');
        });

        static::deleting(function () {
            throw new RuntimeException('La bitacora del expediente es inalterable.');
        });
    }

    public function expediente()
    {
        return $this->belongsTo(ExpedienteTraslado::class, 'expediente_id');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function accionLabel(): string
    {
        return match ($this->accion) {
            self::ACCION_CREACION => 'Expediente creado',
            self::ACCION_ACTUALIZACION => 'Datos actualizados',
            self::ACCION_DOCUMENTO_SUBIDO => 'Documento adjuntado',
            self::ACCION_DOCUMENTO_ELIMINADO => 'Documento eliminado',
            self::ACCION_PROMOCION_AGREGADA => 'Promocion homologada',
            self::ACCION_PROMOCION_ELIMINADA => 'Promocion eliminada',
            self::ACCION_NOTA_AGREGADA => 'Nota parcial homologada',
            self::ACCION_NOTA_ELIMINADA => 'Nota parcial eliminada',
            self::ACCION_PAZ_Y_SALVO => 'Paz y salvo actualizado',
            self::ACCION_CIERRE => 'Expediente validado y cerrado',
            default => $this->accion,
        };
    }
}
