<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PreRegistro extends Model
{
    use HasFactory;

    protected $table = 'pre_registros';

    protected $fillable = [
        'rep_tipo_documento', 'rep_numero_documento', 'rep_nombres', 'rep_apellidos',
        'rep_email', 'rep_telefono', 'rep_fecha_nacimiento',
        'alumno_tipo_documento', 'alumno_numero_documento', 'alumno_nombres', 'alumno_apellidos',
        'alumno_genero', 'alumno_fecha_nacimiento',         'direccion_domiciliaria', 'alumno_numero_cedula', 'alumno_tipo_sangre',
        'alumno_etnia', 'alumno_nacionalidad', 'alumno_telefono_convencional', 'alumno_celular',
        'alumno_email_activo', 'alumno_numero_hermanos', 'alumno_lugar_entre_hermanos',
        'alumno_con_quien_vive', 'alumno_parentesco_convivencia', 'alumno_institucion_procedencia',
        'alumno_observaciones',
        'alumno_padece_enfermedad', 'alumno_nombre_enfermedad', 'alumno_tiene_tratamiento',
        'alumno_toma_medicina', 'alumno_nombre_medicina', 'alumno_tiene_nee', 'alumno_nombre_nee',
        'alumno_tipo_discapacidad', 'alumno_tiene_carnet_discapacidad', 'alumno_numero_carnet',
        'alumno_tiene_informe_psicopedagogico',
        'vivienda_sector', 'vivienda_referencia', 'vivienda_provincia', 'vivienda_canton',
        'vivienda_parroquia', 'vivienda_numero_casa', 'vivienda_casa_propia', 'vivienda_tiene_agua',
        'vivienda_tiene_sshh', 'vivienda_tiene_luz',
        'padre_nombres_apellidos', 'padre_cedula', 'padre_telefono', 'padre_profesion',
        'padre_direccion', 'padre_email', 'padre_es_facturador',
        'madre_nombres_apellidos', 'madre_cedula', 'madre_telefono', 'madre_profesion',
        'madre_direccion', 'madre_email', 'madre_es_facturador',
        'facturador_nombre', 'facturador_cedula', 'facturador_telefono', 'facturador_direccion',
        'facturador_email',
        'alterno_nombre', 'alterno_parentesco', 'alterno_cedula', 'alterno_telefono',
        'alterno_email', 'alterno_direccion',
        'curso_id', 'paralelo_id', 'periodo_id', 'especializacion',
        'estado', 'comentario_rechazo', 'pdf_path', 'codigo_solicitud',
        'fecha_aprobacion', 'aprobado_por', 'tipo_solicitud', 'registrado_por',
    ];

    protected function casts(): array
    {
        return [
            'alumno_fecha_nacimiento' => 'date',
            'rep_fecha_nacimiento' => 'date',
            'fecha_aprobacion' => 'datetime',
            'alumno_padece_enfermedad' => 'boolean',
            'alumno_tiene_tratamiento' => 'boolean',
            'alumno_toma_medicina' => 'boolean',
            'alumno_tiene_nee' => 'boolean',
            'alumno_tiene_carnet_discapacidad' => 'boolean',
            'alumno_tiene_informe_psicopedagogico' => 'boolean',
            'vivienda_casa_propia' => 'boolean',
            'vivienda_tiene_agua' => 'boolean',
            'vivienda_tiene_sshh' => 'boolean',
            'vivienda_tiene_luz' => 'boolean',
            'padre_es_facturador' => 'boolean',
            'madre_es_facturador' => 'boolean',
        ];
    }

    public function curso()
    {
        return $this->belongsTo(Curso::class);
    }

    public function paralelo()
    {
        return $this->belongsTo(Paralelo::class);
    }

    public function periodo()
    {
        return $this->belongsTo(PeriodoLectivo::class, 'periodo_id');
    }

    public function aprobadoPor()
    {
        return $this->belongsTo(User::class, 'aprobado_por');
    }

    public function registradoPor()
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }

    public function scopePendiente($q)
    {
        return $q->where('estado', 'pendiente');
    }

    public function scopeAprobado($q)
    {
        return $q->where('estado', 'aprobado');
    }

    public function scopeRechazado($q)
    {
        return $q->where('estado', 'rechazado');
    }

    public function getNombreRepresentanteAttribute(): string
    {
        return "{$this->rep_apellidos} {$this->rep_nombres}";
    }

    public function getNombreAlumnoAttribute(): string
    {
        return "{$this->alumno_apellidos} {$this->alumno_nombres}";
    }
}
