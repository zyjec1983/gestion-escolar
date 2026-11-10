<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use RuntimeException;

/**
 * Bitacora inmutable de correcciones extraordinarias de notas. No tiene
 * endpoints de edicion ni borrado y el modelo bloquea ambos a nivel de ORM.
 */
class NotaAuditoria extends Model
{
    public const ACCION_CORRECCION = 'correccion_extraordinaria';

    protected $table = 'nota_auditorias';

    protected $fillable = [
        'nota_id', 'matricula_id', 'alumno_id', 'clase_docente_id',
        'alumno_nombre', 'materia', 'curso', 'paralelo', 'parcial', 'tipo_nota',
        'usuario_id', 'usuario_nombre', 'usuario_rol',
        'accion', 'motivo', 'datos_anteriores', 'datos_nuevos', 'ip', 'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'parcial' => 'integer',
            'datos_anteriores' => 'array',
            'datos_nuevos' => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function () {
            throw new RuntimeException('La bitacora de notas es inalterable.');
        });

        static::deleting(function () {
            throw new RuntimeException('La bitacora de notas es inalterable.');
        });
    }

    public function nota()
    {
        return $this->belongsTo(Nota::class);
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function accionLabel(): string
    {
        return match ($this->accion) {
            self::ACCION_CORRECCION => 'Correccion extraordinaria',
            default => $this->accion,
        };
    }
}
