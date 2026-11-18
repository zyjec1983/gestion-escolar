<?php

namespace App\Http\Controllers;

use App\Models\Institucion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    public function index()
    {
        return view('perfil.index');
    }

    public function update(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'primer_nombre' => 'required|string|max:255',
            'segundo_nombre' => 'nullable|string|max:255',
            'apellido_paterno' => 'required|string|max:255',
            'apellido_materno' => 'nullable|string|max:255',
            'email' => ['required', 'email', 'max:255', "unique:users,email,{$user->id}"],
            'telefono' => 'nullable|string|max:20',
        ]);

        $user->update([
            'name' => trim($validated['primer_nombre'] . ' ' . ($validated['segundo_nombre'] ?? '')),
            'apellidos' => trim($validated['apellido_paterno'] . ' ' . ($validated['apellido_materno'] ?? '')),
            'email' => $validated['email'],
            'telefono' => $validated['telefono'] ?? null,
        ]);

        return back()->with('success', 'Tus datos se actualizaron correctamente.');
    }

    /**
     * Formulario de cambio obligatorio de la contraseña inicial.
     *
     * Qué hace: se muestra cuando `users.debe_cambiar_password` está activo
     * (cuentas creadas por Administración, generación masiva o aprobación de
     * pre-registro). El middleware EnsurePasswordIsChanged solo deja navegar
     * a esta pantalla y al cierre de sesión.
     *
     * Conecta con: App\Http\Middleware\EnsurePasswordIsChanged y
     * resources/views/auth/cambiar-password.blade.php.
     * Ruta: GET /perfil/cambiar-contrasena (perfil.password.obligatorio).
     */
    public function showCambioObligatorio(Request $request)
    {
        if (! $request->user()->debe_cambiar_password) {
            return redirect()->route('perfil.index');
        }

        return view('auth.cambiar-password', [
            'institucion' => Institucion::first(),
        ]);
    }

    /**
     * Cambia la contraseña propia y cierra las demás sesiones abiertas.
     *
     * Reglas:
     * - En el perfil normal se exige la contraseña actual.
     * - En el cambio obligatorio no se vuelve a pedir: la persona acaba de
     *   autenticarse con la clave temporal.
     * - La nueva clave debe tener al menos 8 caracteres, una letra y un número.
     * - `Auth::logoutOtherDevices()` invalida las demás sesiones junto con
     *   `AuthenticateSession` (bootstrap/app.php).
     *
     * Conecta con: App\Models\User, AuthenticateSession y perfil/index.
     * Rutas: POST /perfil/contraseña (perfil.password) y
     *        POST /perfil/cambiar-contrasena (perfil.password.obligatorio.update).
     */
    public function updatePassword(Request $request)
    {
        $obligatorio = $request->routeIs('perfil.password.obligatorio.update');

        $reglas = [
            'password' => ['required', 'string', 'confirmed', Password::min(8)->letters()->numbers()],
        ];

        if (! $obligatorio) {
            $reglas['current_password'] = ['required', 'current_password'];
        }

        $validated = $request->validate($reglas, [
            'current_password.required' => 'Escriba su contraseña actual.',
            'current_password.current_password' => 'La contraseña actual no es correcta.',
            'password.required' => 'Escriba la nueva contraseña.',
            'password.confirmed' => 'La confirmación de la contraseña no coincide.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'password.letters' => 'La contraseña debe incluir al menos una letra.',
            'password.numbers' => 'La contraseña debe incluir al menos un número.',
        ]);

        $user = $request->user();
        $nuevaPassword = $validated['password'];

        $user->forceFill([
            'password' => $nuevaPassword,
            'debe_cambiar_password' => false,
        ])->save();

        // Invalida las demás sesiones y actualiza el hash de esta sesión.
        Auth::logoutOtherDevices($nuevaPassword);

        if (! $obligatorio) {
            return back()->with('success', 'Tu contraseña se actualizó correctamente.');
        }

        $perfiles = $user->perfiles();

        if (count($perfiles) > 1 && ! $request->session()->has('perfil_activo')) {
            return redirect()
                ->route('perfil.seleccionar')
                ->with('success', 'Contraseña actualizada. Ahora elija el perfil con el que desea entrar.');
        }

        return redirect()
            ->route('dashboard')
            ->with('success', 'Contraseña actualizada. Ya puede usar el sistema con su nueva clave.');
    }
}
