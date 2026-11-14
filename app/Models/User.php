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
