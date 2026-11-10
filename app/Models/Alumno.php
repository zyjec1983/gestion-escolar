<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Alumno extends Model
{
    use HasFactory;

    /**
     * Catalogo unico de tipos de sangre. Los formularios lo muestran como
     * lista y la validacion lo exige, para que nadie escriba "O pos" o "A+1".
     */
    public const TIPOS_SANGRE = [
        'A+', 'A-',
        'B+', 'B-',
        'AB+', 'AB-',
        'O+', 'O-',
    ];

    protected $fillable = [
        'codigo', 'numero_cedula', 'nombres', 'apellidos', 'fecha_nacimiento', 'genero',
        'tipo_sangre', 'etnia', 'nacionalidad',
        'telefono_convencional', 'celular', 'email_activo',
        'numero_hermanos', 'lugar_que_ocupa_entre_hermanos', 'con_quien_vive', 'parentesco_convivencia',
        'institucion_procedencia', 'observaciones_generales',
        'padece_enfermedad', 'nombre_enfermedad', 'tiene_tratamiento_medico', 'toma_medicina', 'nombre_medicina',
        'necesidades_educativas_especificas_nee', 'nombre_nee',
        'tiene_discapacidad', 'tipo_discapacidad', 'tiene_carnet', 'numero_carnet', 'tiene_informe_psicopedagogico_actualizado',
        'direccion_domiciliaria', 'sector', 'referencia_domicilio', 'provincia', 'canton', 'parroquia', 'numero_casa',
        'casa_propia', 'tiene_agua', 'tiene_sshh_letrina', 'tiene_luz_electrica',
        'padre_nombres_apellidos', 'padre_cedula', 'padre_telefono', 'padre_profesion', 'padre_direccion', 'padre_email', 'padre_es_facturador',
        'madre_nombres_apellidos', 'madre_cedula', 'madre_telefono', 'madre_profesion', 'madre_direccion', 'madre_email', 'madre_es_facturador',
        'representante_alterno_nombre', 'representante_alterno_parentesco', 'representante_alterno_cedula',
        'representante_alterno_telefono', 'representante_alterno_email', 'representante_alterno_direccion',
        'facturador_nombre', 'facturador_cedula', 'facturador_telefono', 'facturador_direccion', 'facturador_email',
        'representante_user_id', 'user_id',
    ];

    protected function casts(): array
    {
        return [
            'fecha_nacimiento' => 'date',
            'padece_enfermedad' => 'boolean',
            'tiene_tratamiento_medico' => 'boolean',
            'toma_medicina' => 'boolean',
            'necesidades_educativas_especificas_nee' => 'boolean',
            'tiene_discapacidad' => 'boolean',
            'tiene_carnet' => 'boolean',
            'tiene_informe_psicopedagogico_actualizado' => 'boolean',
            'casa_propia' => 'boolean',
            'tiene_agua' => 'boolean',
            'tiene_sshh_letrina' => 'boolean',
            'tiene_luz_electrica' => 'boolean',
            'padre_es_facturador' => 'boolean',
            'madre_es_facturador' => 'boolean',
        ];
    }

    public function representante()
    {
        return $this->belongsTo(User::class, 'representante_user_id');
    }

    public function nombreCompleto(): string
    {
        return trim(($this->apellidos ?? '').' '.($this->nombres ?? '')) ?: 'Estudiante sin nombre';
    }

    public function usuarioEstudiante()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function matriculas()
    {
        return $this->hasMany(Matricula::class);
    }

    public function expedientesTraslado()
    {
        return $this->hasMany(ExpedienteTraslado::class);
    }

    /** Historial de representantes legales que ha tenido el alumno. */
    public function cambiosRepresentante()
    {
        return $this->hasMany(RepresentanteCambio::class);
    }

    public function observaciones()
    {
        return $this->hasMany(Observacion::class);
    }

    public function mensajesContexto()
    {
        return $this->hasMany(Mensaje::class, 'contexto_estudiante_id');
    }

    public function agendas()
    {
        return $this->belongsToMany(Agenda::class, 'agenda_alumnos');
    }

    /**
     * Fichas cuyo representante legal todavia no cumple la regla de mayoria de
     * edad: sin fecha de nacimiento, o con fecha que no llega a los 18 anos.
     */
    public function scopeRepresentantePorRegularizar(Builder $query): Builder
    {
        $mayor = now()->subYears(18)->toDateString();

        return $query->where(function ($q) use ($mayor) {
            $q->whereHas('representante', fn ($r) => $r->whereNull('fecha_nacimiento'))
                ->orWhereHas('representante', fn ($r) => $r->where('fecha_nacimiento', '>', $mayor));
        });
    }
}
