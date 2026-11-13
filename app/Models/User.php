<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name', 'apellidos', 'email', 'password',
        'rol', 'institucion_id', 'telefono', 'numero_cedula', 'fecha_nacimiento',
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'fecha_nacimiento' => 'date',
            'password' => 'hashed',
        ];
    }

    /** Edad cumplida. Null si la persona no tiene fecha de nacimiento registrada. */
    public function edad(): ?int
    {
        return $this->fecha_nacimiento?->age;
    }

    /**
     * Un representante legal tiene que ser mayor de edad. Las cuentas viejas
     * pueden no tener la fecha cargada, asi que sin dato no se puede afirmar
     * que sea menor: se devuelve null y quien valida decide que hacer.
     */
    public function esAdulto(): ?bool
    {
        return $this->edad() === null ? null : $this->edad() >= 18;
    }

    public function nombreCompleto(): string
    {
        return trim("{$this->name} {$this->apellidos}");
    }

    public function institucion()
    {
        return $this->belongsTo(Institucion::class);
    }

    public function clasesDocentes()
    {
        return $this->hasMany(ClaseDocente::class, 'user_id');
    }

    public function observaciones()
    {
        return $this->hasMany(Observacion::class, 'user_id');
    }

    public function representados()
    {
        return $this->hasMany(Alumno::class, 'representante_user_id');
    }

    public function estudiantes()
    {
        return $this->hasMany(Alumno::class, 'user_id');
    }

    public function mensajesEnviados()
    {
        return $this->morphMany(Mensaje::class, 'emisor');
    }

    public function mensajesRecibidos()
    {
        return $this->hasMany(Mensaje::class, 'destinatario_id');
    }

    public function citasSolicitadas()
    {
        return $this->morphMany(Cita::class, 'solicitante');
    }

    public function citasComoDocente()
    {
        return $this->hasMany(Cita::class, 'docente_id');
    }

    public function permisosAsignados()
    {
        return $this->hasMany(PermisoNota::class, 'user_asigno_id');
    }

    public function cambiosDeRepresentante()
    {
        return $this->hasMany(RepresentanteCambio::class, 'registrado_por');
    }

    /**
     * Teléfono que usa el canal SMS de las notificaciones.
     *
     * Qué hace: Laravel busca `routeNotificationForSms` cuando una notificación
     * incluye el canal SMS. Se devuelve el teléfono registrado en el perfil.
     * Conecta con: App\Notifications\Channels\SmsChannel.
     * Ruta: POST /mensajes (mensajes.store) cuando el mensaje es circular.
     */
    public function routeNotificationForSms(): ?string
    {
        return $this->telefono ?: null;
    }
}
