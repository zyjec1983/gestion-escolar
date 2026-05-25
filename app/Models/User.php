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
        'rol', 'institucion_id', 'telefono',
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
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
}
