<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Throwable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /** Roles institucionales que puede tener una cuenta. */
    public const ROLES = [
        'ADMIN',
        'RECTORA',
        'VICERRECTORA',
        'SECRETARIA',
        'INSPECCION',
        'DECE',
        'DOCENTE',
        'REPRESENTANTE',
        'ESTUDIANTE',
    ];

    /**
     * Roles que se pueden asignar como adicionales.
     *
     * ADMIN queda fuera: la administración total solo se ejerce como rol
     * principal, para no repartir privilegios sin control.
     */
    public const ROLES_ADICIONALES = [
        'RECTORA',
        'VICERRECTORA',
        'SECRETARIA',
        'INSPECCION',
        'DECE',
        'DOCENTE',
        'REPRESENTANTE',
        'ESTUDIANTE',
    ];

    /** Etiquetas legibles para menús y pantallas. */
    public const ETIQUETAS_ROLES = [
        'ADMIN' => 'Administración',
        'RECTORA' => 'Rectorado',
        'VICERRECTORA' => 'Vicerrectorado',
        'SECRETARIA' => 'Secretaría',
        'INSPECCION' => 'Inspección',
        'DECE' => 'Consejería estudiantil (DECE)',
        'DOCENTE' => 'Docencia',
        'REPRESENTANTE' => 'Representante legal',
        'ESTUDIANTE' => 'Estudiante',
    ];

    /** Caché por instancia para no repetir consultas en la misma petición. */
    private ?array $perfilesMemo = null;

    protected $fillable = [
        'name', 'apellidos', 'email', 'password',
        'rol', 'institucion_id', 'telefono', 'numero_cedula', 'fecha_nacimiento',
        'intentos_fallidos', 'bloqueado_hasta', 'bloqueos_consecutivos', 'requiere_desbloqueo',
        'titulo', 'horas_contrato', 'periodos_atencion_padres',
        'periodos_reunion_area', 'periodos_tutoria', 'es_tutor',
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'fecha_nacimiento' => 'date',
            'bloqueado_hasta' => 'datetime',
            'requiere_desbloqueo' => 'boolean',
            'password' => 'hashed',
            'horas_contrato' => 'integer',
            'periodos_atencion_padres' => 'integer',
            'periodos_reunion_area' => 'integer',
            'periodos_tutoria' => 'integer',
            'es_tutor' => 'boolean',
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

    /** Roles adicionales asignados desde Administración → Usuarios. */
    public function rolesAdicionales()
    {
        return $this->hasMany(UserRole::class);
    }

    /**
     * Perfiles que puede usar esta cuenta.
     *
     * Qué hace: une el rol principal (`users.rol`) con los roles adicionales
     * de `user_roles` y agrega DOCENTE si la persona tiene clases asignadas.
     * Conecta con: RoleMiddleware, LoginController, DashboardController,
     * layouts/principal y layouts/app.
     * Rutas: POST /login, GET /perfil/seleccionar, POST /perfil/activo.
     *
     * @return list<string>
     */
    public function perfiles(): array
    {
        if ($this->perfilesMemo !== null) {
            return $this->perfilesMemo;
        }

        $perfiles = [];

        if (in_array($this->rol, self::ROLES, true)) {
            $perfiles[] = $this->rol;
        }

        $this->rolesAdicionales()->pluck('rol')->each(function ($rol) use (&$perfiles) {
            if (in_array($rol, self::ROLES_ADICIONALES, true) && ! in_array($rol, $perfiles, true)) {
                $perfiles[] = $rol;
            }
        });

        // Un docente con clases siempre conserva su perfil de Docencia.
        $tieneClases = array_key_exists('clases_docentes_count', $this->attributes)
            ? (int) $this->attributes['clases_docentes_count'] > 0
            : $this->clasesDocentes()->exists();

        if (! in_array('DOCENTE', $perfiles, true) && $tieneClases) {
            $perfiles[] = 'DOCENTE';
        }

        if ($perfiles === [] && $this->rol) {
            $perfiles[] = $this->rol;
        }

        return $this->perfilesMemo = array_values($perfiles);
    }

    /**
     * Perfil con el que la persona está navegando.
     *
     * Qué hace: lee `perfil_activo` de la sesión y lo valida contra `perfiles()`.
     * Si no existe o fue manipulado, vuelve al rol principal.
     * Conecta con: RoleMiddleware, LoginController y layouts.
     */
    public function perfilActivo(): string
    {
        $perfiles = $this->perfiles();
        $activo = null;

        if (app()->bound('session')) {
            try {
                $activo = session('perfil_activo');
            } catch (Throwable) {
                $activo = null;
            }
        }

        return in_array($activo, $perfiles, true)
            ? $activo
            : ($perfiles[0] ?? (string) $this->rol);
    }

    /** ¿La cuenta puede usar este perfil? */
    public function tienePerfil(string $rol): bool
    {
        return in_array($rol, $this->perfiles(), true);
    }

    /** Invalida la caché cuando se acaban de cambiar los roles. */
    public function limpiarPerfilesMemo(): void
    {
        $this->perfilesMemo = null;
    }

    /**
     * ¿La cuenta está bloqueada en este momento?
     *
     * Qué hace: considera bloqueo temporal (1 hora) y bloqueo que solo
     * Administración puede levantar (`requiere_desbloqueo`).
     * Conecta con: LoginController@login, Admin\UserController@desbloquear
     * y layouts/principal (aviso en el menú de usuarios).
     * Rutas: POST /login, POST /admin/usuarios/{user}/desbloquear.
     */
    public function estaBloqueado(): bool
    {
        if ($this->requiere_desbloqueo) {
            return true;
        }

        return $this->bloqueado_hasta !== null && $this->bloqueado_hasta->isFuture();
    }

    /** Minutos que faltan para que termine el bloqueo temporal. */
    public function minutosDeBloqueo(): int
    {
        if ($this->bloqueado_hasta === null || ! $this->bloqueado_hasta->isFuture()) {
            return 0;
        }

        return max(1, (int) ceil(now()->diffInSeconds($this->bloqueado_hasta) / 60));
    }

    /** Mensaje que se muestra al intentar entrar con una cuenta bloqueada. */
    public function mensajeBloqueo(): string
    {
        if ($this->requiere_desbloqueo) {
            return 'La cuenta está bloqueada. Solicite a Administración que la desbloquee.';
        }

        return 'La cuenta está bloqueada temporalmente. Intente de nuevo en '
            .$this->minutosDeBloqueo().' minuto(s).';
    }

    /**
     * Registra un intento fallido y aplica la política institucional:
     * 3 intentos → bloqueo de 1 hora; un segundo bloqueo → solo Administración.
     *
     * Conecta con: LoginController@login.
     * Ruta: POST /login.
     */
    public function registrarIntentoFallido(): void
    {
        if ($this->requiere_desbloqueo) {
            return;
        }

        $this->intentos_fallidos = (int) $this->intentos_fallidos + 1;

        if ($this->intentos_fallidos >= 3) {
            $this->intentos_fallidos = 0;

            if ((int) $this->bloqueos_consecutivos >= 1) {
                // Segundo bloqueo: ya no se libera solo con el paso del tiempo.
                $this->requiere_desbloqueo = true;
                $this->bloqueado_hasta = null;
            } else {
                $this->bloqueos_consecutivos = 1;
                $this->bloqueado_hasta = now()->addHour();
            }
        }

        $this->save();
    }

    /** Deja la cuenta limpia después de un inicio de sesión correcto. */
    public function limpiarIntentosFallidos(): void
    {
        if (! $this->intentos_fallidos && ! $this->bloqueado_hasta
            && ! $this->bloqueos_consecutivos && ! $this->requiere_desbloqueo) {
            return;
        }

        $this->forceFill([
            'intentos_fallidos' => 0,
            'bloqueado_hasta' => null,
            'bloqueos_consecutivos' => 0,
            'requiere_desbloqueo' => false,
        ])->save();
    }

    /** Desbloqueo manual desde Administración. */
    public function desbloquear(): void
    {
        $this->limpiarIntentosFallidos();
    }

    /** Etiqueta legible de un rol. */
    public static function etiquetaRol(?string $rol): string
    {
        return self::ETIQUETAS_ROLES[$rol] ?? (string) $rol;
    }

    /**
     * Filtra usuarios por rol principal o adicional.
     *
     * Conecta con: Admin\UserController (filtro del listado).
     * Ruta: GET /admin/usuarios.
     */
    public function scopeConRol($query, string $rol)
    {
        return $query->where(function ($q) use ($rol) {
            $q->where('rol', $rol)
                ->orWhereHas('rolesAdicionales', fn ($qq) => $qq->where('rol', $rol));
        });
    }

    /**
     * Usuarios que ejercen docencia: rol principal, rol adicional o clases
     * asignadas. Se usa en los contadores de los paneles institucionales.
     */
    public function scopeDocentes($query)
    {
        return $query->where(function ($q) {
            $q->where('rol', 'DOCENTE')
                ->orWhereHas('rolesAdicionales', fn ($qq) => $qq->where('rol', 'DOCENTE'))
                ->orWhereHas('clasesDocentes');
        });
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
