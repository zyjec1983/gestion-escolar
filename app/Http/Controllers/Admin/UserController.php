<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
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
 * - GET  /admin/usuarios/{user}/editar    (admin.usuarios.edit)
 * - PUT  /admin/usuarios/{user}           (admin.usuarios.update)
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

        return redirect()
            ->route('admin.usuarios.index')
            ->with('success', 'Usuario actualizado. Los perfiles se aplican en el próximo inicio de sesión o cambio de perfil.');
    }
}
