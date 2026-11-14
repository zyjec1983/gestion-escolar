<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Alumno;
use App\Models\HistorialCambio;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Administración de usuarios y multirol.
 *
 * Qué hace:
 * - Lista y busca cuentas por nombre, apellido o correo.
 * - Permite cambiar el rol principal y marcar roles adicionales.
 * - Restablece la contraseña cuando el administrador lo indica.
 *
 * Conecta con: App\Models\User, App\Models\UserRole, RoleMiddleware y el
 * selector de perfil de layouts/app.
 *
 * Rutas:
 * - GET  /admin/usuarios                  (admin.usuarios.index)
 * - GET  /admin/usuarios/crear            (admin.usuarios.create)
 * - POST /admin/usuarios                  (admin.usuarios.store)
 * - GET  /admin/usuarios/{user}/editar    (admin.usuarios.edit)
 * - PUT  /admin/usuarios/{user}           (admin.usuarios.update)
 * - GET  /admin/usuarios/{user}/contrasena (admin.usuarios.password)
 * - POST /admin/usuarios/{user}/contrasena (admin.usuarios.password.update)
 */
class UserController extends Controller
{
    public function index(Request $request)
    {
        $buscar = trim((string) $request->query('buscar'));
        $rol = (string) $request->query('rol');

        $usuarios = User::query()
            ->with('rolesAdicionales')
            ->withCount('clasesDocentes')
            ->when($buscar !== '', function ($query) use ($buscar) {
                $query->where(function ($q) use ($buscar) {
                    $q->where('name', 'like', "%{$buscar}%")
                        ->orWhere('apellidos', 'like', "%{$buscar}%")
                        ->orWhere('email', 'like', "%{$buscar}%");
                });
            })
            ->when(in_array($rol, User::ROLES, true), fn ($query) => $query->conRol($rol))
            ->orderBy('apellidos')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('admin.usuarios.index', [
            'usuarios' => $usuarios,
            'buscar' => $buscar,
            'rol' => $rol,
            'roles' => User::ROLES,
        ]);
    }

    /**
     * Genera las cuentas familiares de los estudiantes ya registrados.
     *
     * Regla institucional: el usuario es la cédula del alumno y la cuenta
     * tiene los perfiles REPRESENTANTE (principal) y ESTUDIANTE (adicional).
     * La contraseña inicial es la misma cédula; se recomienda cambiarla al
     * primer ingreso. Los alumnos que ya tienen cuenta se omiten.
     *
     * Conecta con: App\Models\Alumno, User, UserRole e HistorialCambio.
     * Ruta: POST /admin/usuarios/generar-cuentas-estudiantes.
     */
    public function generarCuentasEstudiantes(Request $request)
    {
        $creadas = 0;
        $omitidas = 0;
        $errores = 0;

        Alumno::query()
            ->whereNull('user_id')
            ->whereNotNull('numero_cedula')
            ->orderBy('id')
            ->chunkById(100, function ($alumnos) use (&$creadas, &$omitidas, &$errores) {
                foreach ($alumnos as $alumno) {
                    $cedula = trim((string) $alumno->numero_cedula);

                    if ($cedula === '' || User::where('numero_cedula', $cedula)->exists()) {
                        $omitidas++;

                        continue;
                    }

                    $email = trim((string) $alumno->email_activo);
                    if ($email === '' || User::where('email', $email)->exists()) {
                        $email = $cedula.'@estudiante.local';
                    }

                    if (User::where('email', $email)->exists()) {
                        $omitidas++;

                        continue;
                    }

                    try {
                        DB::transaction(function () use ($alumno, $cedula, $email) {
                            $user = User::create([
                                'name' => $alumno->nombres,
                                'apellidos' => $alumno->apellidos,
                                'email' => $email,
                                'numero_cedula' => $cedula,
                                'telefono' => $alumno->celular,
                                'fecha_nacimiento' => $alumno->fecha_nacimiento,
                                'rol' => 'REPRESENTANTE',
                                'password' => $cedula,
                            ]);

                            $user->rolesAdicionales()->create(['rol' => 'ESTUDIANTE']);

                            $alumno->update([
                                'user_id' => $user->id,
                                'representante_user_id' => $alumno->representante_user_id ?: $user->id,
                            ]);
                        });

                        $creadas++;
                    } catch (\Throwable) {
                        $errores++;
                    }
                }
            });

        HistorialCambio::registrar(
            'Usuarios',
            'generar cuentas de estudiantes',
            "Cuentas familiares generadas: {$creadas}; omitidas: {$omitidas}; errores: {$errores}",
            ['creadas' => $creadas, 'omitidas' => $omitidas, 'errores' => $errores]
        );

        return back()->with(
            'success',
            "Se generaron {$creadas} cuenta(s). Omitidas: {$omitidas}. Errores: {$errores}. "
            .'El usuario y la contraseña inicial son la cédula del estudiante.'
        );
    }

    public function edit(User $user)
    {
        $user->load('rolesAdicionales');

        return view('admin.usuarios.edit', [
            'usuario' => $user,
            'roles' => User::ROLES,
            'rolesAdicionales' => User::ROLES_ADICIONALES,
            'adicionalesActuales' => $user->rolesAdicionales->pluck('rol')->all(),
        ]);
    }

    /** Formulario para crear una cuenta nueva. */
    public function create()
    {
        return view('admin.usuarios.create', [
            'roles' => User::ROLES,
            'rolesAdicionales' => User::ROLES_ADICIONALES,
        ]);
    }

    /**
     * Crea una cuenta con su rol principal, roles adicionales y contraseña.
     * Ruta: POST /admin/usuarios (admin.usuarios.store).
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'apellidos' => 'nullable|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'numero_cedula' => 'nullable|string|max:20|unique:users,numero_cedula',
            'telefono' => 'nullable|string|max:30',
            'rol' => ['required', Rule::in(User::ROLES)],
            'roles_adicionales' => 'array',
            'roles_adicionales.*' => [Rule::in(User::ROLES_ADICIONALES)],
            'password' => 'required|string|min:8|confirmed',
        ], [
            'name.required' => 'El nombre es obligatorio.',
            'email.unique' => 'Ya existe otra cuenta con ese correo.',
            'rol.in' => 'El rol principal seleccionado no es válido.',
            'password.required' => 'Defina una contraseña inicial.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'password.confirmed' => 'La confirmación de la contraseña no coincide.',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'apellidos' => $validated['apellidos'] ?? null,
            'email' => $validated['email'],
            'telefono' => $validated['telefono'] ?? null,
            'numero_cedula' => $validated['numero_cedula'] ?? null,
            'rol' => $validated['rol'],
            'password' => $validated['password'],
        ]);

        $adicionales = collect($validated['roles_adicionales'] ?? [])
            ->filter(fn ($rol) => in_array($rol, User::ROLES_ADICIONALES, true))
            ->reject(fn ($rol) => $rol === $validated['rol'])
            ->unique()
            ->values();

        foreach ($adicionales as $rol) {
            $user->rolesAdicionales()->create(['rol' => $rol]);
        }

        HistorialCambio::registrar(
            'Usuarios',
            'crear',
            "Cuenta creada: {$user->nombreCompleto()} ({$user->email})",
            ['id' => $user->id, 'rol' => $user->rol, 'adicionales' => $adicionales->all()]
        );

        return redirect()->route('admin.usuarios.index')
            ->with('success', 'Cuenta creada correctamente. Comparta las credenciales con la persona.');
    }

    /** Formulario para restablecer la contraseña de una cuenta olvidada. */
    public function password(User $user)
    {
        return view('admin.usuarios.password', ['usuario' => $user]);
    }

    /**
     * Restablece la contraseña sin tocar el resto de los datos.
     * Ruta: POST /admin/usuarios/{user}/contrasena.
     */
    public function updatePassword(Request $request, User $user)
    {
        $validated = $request->validate([
            'password' => 'required|string|min:8|confirmed',
        ], [
            'password.required' => 'Escriba la nueva contraseña.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'password.confirmed' => 'La confirmación de la contraseña no coincide.',
        ]);

        $user->update(['password' => $validated['password']]);

        HistorialCambio::registrar(
            'Usuarios',
            'restablecer contraseña',
            "Contraseña restablecida para {$user->nombreCompleto()} ({$user->email})",
            ['id' => $user->id]
        );

        return redirect()->route('admin.usuarios.index')
            ->with('success', 'Contraseña restablecida. Comparta la nueva clave con la persona.');
    }

    /**
     * Levanta el bloqueo de una cuenta.
     *
     * Qué hace: limpia intentos fallidos, el bloqueo temporal y la marca de
     * desbloqueo manual. Solo Administración puede ejecutarlo.
     * Conecta con: App\Models\User@desbloquear e HistorialCambio.
     * Ruta: POST /admin/usuarios/{user}/desbloquear (admin.usuarios.desbloquear).
     */
    public function desbloquear(Request $request, User $user)
    {
        $user->desbloquear();

        HistorialCambio::registrar(
            'Usuarios',
            'desbloquear',
            "Cuenta desbloqueada: {$user->nombreCompleto()} ({$user->email})",
            ['id' => $user->id, 'por' => $request->user()->id]
        );

        return back()->with('success', 'Cuenta desbloqueada. La persona puede volver a iniciar sesión.');
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'apellidos' => 'nullable|string|max:255',
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'numero_cedula' => [
                'nullable',
                'string',
                'max:20',
                Rule::unique('users', 'numero_cedula')->ignore($user->id),
            ],
            'telefono' => 'nullable|string|max:30',
            'rol' => ['required', Rule::in(User::ROLES)],
            'roles_adicionales' => 'array',
            'roles_adicionales.*' => [Rule::in(User::ROLES_ADICIONALES)],
            'password' => 'nullable|string|min:8|confirmed',
        ], [
            'name.required' => 'El nombre es obligatorio.',
            'email.unique' => 'Ya existe otra cuenta con ese correo.',
            'rol.in' => 'El rol principal seleccionado no es válido.',
            'roles_adicionales.*.in' => 'Uno de los roles adicionales no es válido.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'password.confirmed' => 'La confirmación de la contraseña no coincide.',
        ]);

        $eraAdmin = $user->rol === 'ADMIN';
        $sigueAdmin = $validated['rol'] === 'ADMIN';

        // Protecciones: no dejar al sistema sin ADMIN ni quitarse el propio.
        if ($eraAdmin && ! $sigueAdmin) {
            if ($request->user()->id === $user->id) {
                return back()
                    ->withErrors(['rol' => 'No puedes quitarte a ti mismo el rol de Administración.'])
                    ->withInput();
            }

            $otrosAdmins = User::where('rol', 'ADMIN')->whereKeyNot($user->id)->count();

            if ($otrosAdmins === 0) {
                return back()
                    ->withErrors(['rol' => 'Debe existir al menos una cuenta con rol de Administración.'])
                    ->withInput();
            }
        }

        // Los adicionales nunca duplican el rol principal ni incluyen ADMIN.
        $adicionales = collect($validated['roles_adicionales'] ?? [])
            ->filter(fn ($rol) => in_array($rol, User::ROLES_ADICIONALES, true))
            ->reject(fn ($rol) => $rol === $validated['rol'])
            ->unique()
            ->values();

        $user->fill([
            'name' => $validated['name'],
            'apellidos' => $validated['apellidos'] ?? null,
            'email' => $validated['email'],
            'telefono' => $validated['telefono'] ?? null,
            'numero_cedula' => $validated['numero_cedula'] ?? null,
            'rol' => $validated['rol'],
        ]);

        if (! empty($validated['password'])) {
            $user->password = $validated['password'];
        }

        $user->save();

        $user->rolesAdicionales()->delete();
        foreach ($adicionales as $rol) {
            $user->rolesAdicionales()->create(['rol' => $rol]);
        }

        $user->limpiarPerfilesMemo();

        HistorialCambio::registrar(
            'Usuarios',
            'actualizar',
            "Cuenta actualizada: {$user->nombreCompleto()} ({$user->email})",
            ['id' => $user->id, 'rol' => $user->rol]
        );

        return redirect()
            ->route('admin.usuarios.index')
            ->with('success', 'Usuario actualizado. Los perfiles se aplican en el próximo inicio de sesión o cambio de perfil.');
    }
}
