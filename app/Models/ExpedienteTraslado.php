<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ExpedienteTraslado extends Model
{
    public const TIPO_INGRESO = 'ingreso';

    public const TIPO_SALIDA = 'salida';

    public const MOMENTO_INICIO = 'inicio';

    public const MOMENTO_TRANSICURSO = 'transcurso';

    public const ESTADO_POR_REGULARIZAR = 'por_regularizar';

    public const ESTADO_DEFINITIVA = 'definitiva';

    public const ESTADO_CERRADO = 'cerrado';

    protected $table = 'expedientes_traslado';

    protected $fillable = [
        'alumno_id', 'matricula_id', 'curso_id', 'paralelo_id',
        'tipo', 'momento', 'fecha_traslado', 'institucion', 'anio_lectivo', 'observaciones',
        'estado', 'documentos_completos',
        'paz_y_salvo_financiero', 'paz_y_salvo_biblioteca', 'paz_y_salvo_observacion',
        'creado_por', 'validado_por', 'validado_at', 'cerrado_at',
    ];

    protected function casts(): array
    {
        return [
            'fecha_traslado' => 'date',
            'documentos_completos' => 'boolean',
            'paz_y_salvo_financiero' => 'boolean',
            'paz_y_salvo_biblioteca' => 'boolean',
            'validado_at' => 'datetime',
            'cerrado_at' => 'datetime',
        ];
    }

    public function alumno()
    {
        return $this->belongsTo(Alumno::class);
    }

    public function matricula()
    {
        return $this->belongsTo(Matricula::class);
    }

    public function curso()
    {
        return $this->belongsTo(Curso::class);
    }

    public function paralelo()
    {
        return $this->belongsTo(Paralelo::class);
    }

    public function documentos()
    {
        return $this->hasMany(ExpedienteDocumento::class, 'expediente_id');
    }

    public function promociones()
    {
        return $this->hasMany(HistorialExternoPromocion::class, 'expediente_id');
    }

    public function notasParciales()
    {
        return $this->hasMany(HistorialExternoNota::class, 'expediente_id');
    }

    public function auditorias()
    {
        return $this->hasMany(ExpedienteAuditoria::class, 'expediente_id')->latest();
    }

    public function creador()
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    public function validador()
    {
        return $this->belongsTo(User::class, 'validado_por');
    }

    public function scopeIngresos(Builder $query): Builder
    {
        return $query->where('tipo', self::TIPO_INGRESO);
    }

    public function scopeSalidas(Builder $query): Builder
    {
        return $query->where('tipo', self::TIPO_SALIDA);
    }

    public function scopePorRegularizar(Builder $query): Builder
    {
        return $query->where('estado', self::ESTADO_POR_REGULARIZAR);
    }

    public function estaCerrado(): bool
    {
        return $this->estado === self::ESTADO_CERRADO || $this->cerrado_at !== null;
    }

    /** Un certificado de salida exige documentacion completa y paz y salvo. */
    public function puedeEmitirCertificadoSalida(): bool
    {
        return $this->tipo === self::TIPO_SALIDA
            && $this->documentos_completos
            && $this->paz_y_salvo_financiero
            && $this->paz_y_salvo_biblioteca;
    }

    public function nombreAlumno(): string
    {
        return trim(($this->alumno?->apellidos ?? '').' '.($this->alumno?->nombres ?? ''))
            ?: 'Estudiante sin nombre';
    }

    public function tipoLabel(): string
    {
        return $this->tipo === self::TIPO_INGRESO ? 'Ingreso' : 'Salida / retiro';
    }

    public function momentoLabel(): string
    {
        return match ($this->momento) {
            self::MOMENTO_INICIO => 'Antes de iniciar el año',
            self::MOMENTO_TRANSICURSO => 'En transcurso del año',
            default => 'No aplica',
        };
    }

    public function estadoLabel(): string
    {
        return match ($this->estado) {
            self::ESTADO_DEFINITIVA => 'Matricula definitiva',
            self::ESTADO_CERRADO => 'Cerrado e inmutable',
            default => 'Por regularizar',
        };
    }

    public function estadoBadge(): string
    {
        return match ($this->estado) {
            self::ESTADO_DEFINITIVA => 'success',
            self::ESTADO_CERRADO => 'dark',
            default => 'warning',
        };
    }

    public function tipoBadge(): string
    {
        return $this->tipo === self::TIPO_INGRESO ? 'success' : 'danger';
    }
}
