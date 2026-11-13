<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use RuntimeException;

/**
 * Bitacora inalterable del DECE. Registra consultas y cambios sensibles con
 * usuario, fecha, IP y user-agent. No tiene endpoints de edicion ni borrado y
 * el ORM bloquea ambas operaciones.
 */
class DeceAuditoria extends Model
{
    public const ACCION_CONSULTA = 'consulta';

    public const ACCION_CREACION = 'creacion';

    public const ACCION_ACTUALIZACION = 'actualizacion';

    public const ACCION_CIERRE = 'cierre';

    public const ACCION_FIRMA = 'firma';

    public const ACCION_SEGUIMIENTO = 'seguimiento';

    public const ACCION_ARCHIVO = 'archivo';

    public const ACCION_NOTIFICACION = 'notificacion';

    public const ACCIONES = [
        self::ACCION_CONSULTA => 'Consulta',
        self::ACCION_CREACION => 'Creación',
        self::ACCION_ACTUALIZACION => 'Actualización',
        self::ACCION_CIERRE => 'Cierre',
        self::ACCION_FIRMA => 'Firma digital',
        self::ACCION_SEGUIMIENTO => 'Seguimiento',
        self::ACCION_ARCHIVO => 'Evidencia adjunta',
        self::ACCION_NOTIFICACION => 'Notificación enviada',
    ];

    protected $table = 'dece_auditorias';

    protected $fillable = [
        'auditable_type', 'auditable_id', 'alumno_id', 'usuario_id',
        'usuario_nombre', 'usuario_rol', 'accion', 'detalle',
        'datos_anteriores', 'datos_nuevos', 'ip', 'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'datos_anteriores' => 'array',
            'datos_nuevos' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function () {
            throw new RuntimeException('La bitácora del DECE es inalterable.');
        });

        static::deleting(function () {
            throw new RuntimeException('La bitácora del DECE es inalterable.');
        });
    }

    public function auditable()
    {
        return $this->morphTo();
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function alumno()
    {
        return $this->belongsTo(Alumno::class);
    }

    public function accionLabel(): string
    {
        return self::ACCIONES[$this->accion] ?? $this->accion;
    }
}
