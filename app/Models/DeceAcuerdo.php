<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Acuerdo de compromiso firmado por estudiante, representante y profesional.
 * La firma digital guarda el sello de tiempo y se complementa con la bitacora
 * de red (IP y user-agent) para dejar constancia de quien la registro.
 */
class DeceAcuerdo extends Model
{
    public const TIPO_CONDUCTUAL = 'conductual';

    public const TIPO_ACADEMICO = 'academico';

    public const TIPO_CONVIVENCIA = 'convivencia';

    public const TIPOS = [
        self::TIPO_CONDUCTUAL => 'Conductual',
        self::TIPO_ACADEMICO => 'Académico',
        self::TIPO_CONVIVENCIA => 'Convivencia',
    ];

    public const ESTADO_ACTIVO = 'activo';

    public const ESTADO_CUMPLIDO = 'cumplido';

    public const ESTADO_INCUMPLIDO = 'incumplido';

    public const ESTADO_VENCIDO = 'vencido';

    public const ESTADOS = [
        self::ESTADO_ACTIVO => 'Activo',
        self::ESTADO_CUMPLIDO => 'Cumplido',
        self::ESTADO_INCUMPLIDO => 'Incumplido',
        self::ESTADO_VENCIDO => 'Vencido',
    ];

    protected $table = 'dece_acuerdos';

    protected $fillable = [
        'alumno_id', 'atencion_id', 'profesional_id', 'tipo', 'descripcion',
        'compromiso_estudiante', 'compromiso_representante', 'fecha_inicio',
        'fecha_compromiso', 'estado', 'firmado_estudiante_en',
        'firmado_representante_en', 'firmado_profesional_en',
    ];

    protected function casts(): array
    {
        return [
            'fecha_inicio' => 'date',
            'fecha_compromiso' => 'date',
            'firmado_estudiante_en' => 'datetime',
            'firmado_representante_en' => 'datetime',
            'firmado_profesional_en' => 'datetime',
        ];
    }

    public function alumno()
    {
        return $this->belongsTo(Alumno::class);
    }

    public function atencion()
    {
        return $this->belongsTo(DeceAtencion::class, 'atencion_id');
    }

    public function profesional()
    {
        return $this->belongsTo(User::class, 'profesional_id');
    }

    public function seguimientos()
    {
        return $this->hasMany(DeceAcuerdoSeguimiento::class, 'acuerdo_id')->latest('fecha');
    }

    public function archivos()
    {
        return $this->morphMany(DeceArchivo::class, 'archivoable');
    }

    public function scopeActivos(Builder $query): Builder
    {
        return $query->where('estado', self::ESTADO_ACTIVO);
    }

    public function scopeBuscar(Builder $query, ?string $termino): Builder
    {
        if (! $termino) {
            return $query;
        }

        return $query->where(function (Builder $sub) use ($termino) {
            $sub->where('descripcion', 'like', "%{$termino}%")
                ->orWhereHas('alumno', function (Builder $alumno) use ($termino) {
                    $alumno->where('nombres', 'like', "%{$termino}%")
                        ->orWhere('apellidos', 'like', "%{$termino}%")
                        ->orWhere('numero_cedula', 'like', "%{$termino}%");
                });
        });
    }

    public function firmadoCompleto(): bool
    {
        return $this->firmado_estudiante_en !== null
            && $this->firmado_representante_en !== null
            && $this->firmado_profesional_en !== null;
    }

    public function firmasRegistradas(): int
    {
        return collect([
            $this->firmado_estudiante_en,
            $this->firmado_representante_en,
            $this->firmado_profesional_en,
        ])->filter()->count();
    }

    public function estaVencido(): bool
    {
        return $this->estado === self::ESTADO_ACTIVO
            && $this->fecha_compromiso->lt(now()->startOfDay());
    }

    /**
     * Pasa a "vencido" todo acuerdo activo cuya fecha de compromiso ya paso.
     * Se ejecuta al abrir el dashboard para que los indicadores no dependan de
     * que alguien entre a editar cada registro.
     */
    public static function marcarVencidos(): int
    {
        return static::query()
            ->where('estado', self::ESTADO_ACTIVO)
            ->whereDate('fecha_compromiso', '<', now()->toDateString())
            ->update(['estado' => self::ESTADO_VENCIDO]);
    }

    public function tipoLabel(): string
    {
        return self::TIPOS[$this->tipo] ?? $this->tipo;
    }

    public function estadoLabel(): string
    {
        return self::ESTADOS[$this->estado] ?? $this->estado;
    }
}
