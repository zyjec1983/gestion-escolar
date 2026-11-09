<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class Unidad extends Model
{
    protected $table = 'unidades';

    protected $fillable = [
        'materia_id', 'clase_docente_id', 'titulo', 'descripcion',
        'fecha_inicio', 'fecha_fin', 'quimestre', 'parcial',
        'periodo_lectivo_id', 'creado_por', 'grupo_id',
    ];

    protected function casts(): array
    {
        return [
            'fecha_inicio' => 'date',
            'fecha_fin' => 'date',
        ];
    }

    public function materia()
    {
        return $this->belongsTo(Materia::class);
    }

    public function claseDocente()
    {
        return $this->belongsTo(ClaseDocente::class);
    }

    public function periodoLectivo()
    {
        return $this->belongsTo(PeriodoLectivo::class);
    }

    public function creador()
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    public function auditorias()
    {
        return $this->hasMany(PlanificacionAuditoria::class, 'unidad_id');
    }

    /**
     * Docente titular de la clase. Si la unidad no tiene clase asociada se usa
     * el usuario que la registro, para no perder la referencia de autoria.
     */
    public function docenteTitular(): ?User
    {
        return $this->claseDocente?->docente ?: $this->creador;
    }

    public function nivelParalelo(): string
    {
        if (! $this->claseDocente) {
            return 'General';
        }

        $nivel = trim(implode(' ', array_filter([
            $this->claseDocente->curso?->nombre,
            $this->claseDocente->paralelo?->nombre,
        ])));

        return $nivel !== '' ? $nivel : 'General';
    }

    public function permisosNotas()
    {
        return $this->hasMany(PermisoNota::class);
    }

    public function duracionDias(): int
    {
        if (! $this->fecha_inicio || ! $this->fecha_fin) {
            return 0;
        }

        return (int) $this->fecha_inicio->startOfDay()->diffInDays($this->fecha_fin->copy()->startOfDay()) + 1;
    }

    public function activaEl(Carbon|string $fecha): bool
    {
        $f = $fecha instanceof Carbon ? $fecha->toDateString() : $fecha;

        return $this->fecha_inicio
            && $this->fecha_fin
            && $f >= $this->fecha_inicio->toDateString()
            && $f <= $this->fecha_fin->toDateString();
    }
}
