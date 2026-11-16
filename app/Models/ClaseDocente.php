<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClaseDocente extends Model
{
    protected $table = 'clases_docentes';

    protected $fillable = ['user_id', 'materia_id', 'curso_id', 'paralelo_id', 'color_tarjeta'];

    /**
     * Una clase con notas, asistencias o materiales es historial institucional:
     * no se puede borrar. El controlador muestra el aviso y este evento evita
     * que cualquier otro código la elimine en cascada.
     */
    protected static function booted(): void
    {
        static::deleting(function (ClaseDocente $clase) {
            return ! $clase->tieneDependencias();
        });
    }

    public function docente()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function materia()
    {
        return $this->belongsTo(Materia::class);
    }

    public function curso()
    {
        return $this->belongsTo(Curso::class);
    }

    public function paralelo()
    {
        return $this->belongsTo(Paralelo::class);
    }

    public function agendas()
    {
        return $this->hasMany(Agenda::class);
    }

    public function materiales()
    {
        return $this->hasMany(Material::class);
    }

    public function unidades()
    {
        return $this->hasMany(Unidad::class);
    }

    public function insumosEvaluacion()
    {
        return $this->hasMany(InsumoEvaluacion::class);
    }

    public function permisosNotas()
    {
        return $this->hasMany(PermisoNota::class);
    }

    public function asignacionGrupos()
    {
        return $this->hasMany(AsignacionGrupo::class);
    }

    public function foros()
    {
        return $this->hasMany(Foro::class);
    }

    public function notas()
    {
        return $this->hasMany(Nota::class, 'clase_docente_id');
    }

    public function asistencias()
    {
        return $this->hasMany(Asistencia::class, 'clase_docente_id');
    }

    public function notaAuditorias()
    {
        return $this->hasMany(NotaAuditoria::class, 'clase_docente_id');
    }

    /**
     * Dependencias que impiden borrar la asignación, con su cantidad.
     *
     * @return array<string, int>
     */
    public function dependencias(): array
    {
        return array_filter([
            'notas' => $this->notas()->count(),
            'asistencias' => $this->asistencias()->count(),
            'agendas' => $this->agendas()->count(),
            'materiales' => $this->materiales()->count(),
            'unidades' => $this->unidades()->count(),
            'insumos de evaluación' => $this->insumosEvaluacion()->count(),
            'permisos de notas' => $this->permisosNotas()->count(),
            'grupos de trabajo' => $this->asignacionGrupos()->count(),
            'foros' => $this->foros()->count(),
            'auditorías de notas' => $this->notaAuditorias()->count(),
        ], fn (int $total) => $total > 0);
    }

    public function tieneDependencias(): bool
    {
        return $this->dependencias() !== [];
    }
}
