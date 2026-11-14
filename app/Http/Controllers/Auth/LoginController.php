<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\HeroSlide;
use App\Models\Institucion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Inicio y cierre de sesión, más la selección del perfil activo.
 *
 * Qué hace:
 * - Carga el carrusel de login y los datos de la institución.
 * - Autentica y, si la cuenta es multirol, pide elegir con qué perfil entrar.
 * - Guarda `perfil_activo` en la sesión, validado contra User::perfiles().
 *
 * Conecta con: App\Models\User, App\Models\HeroSlide, RoleMiddleware y
 * layouts/app (selector de perfil de la barra superior).
 *
 * Rutas:
 * - GET  /login                (login)
 * - POST /login
 * - POST /logout               (logout)
 * - GET  /perfil/seleccionar   (perfil.seleccionar)
 * - POST /perfil/activo        (perfil.activo)
 */
class LoginController extends Controller
{
    /** Muestra el login con el carrusel configurado para esa ubicación. */
    public function showLoginForm()
    {
        return view('auth.login', [
            'slides' => HeroSlide::where('activo', true)
                ->paraUbicacion('login')
                ->orderBy('orden')
                ->orderBy('id')
                ->get(),
            'institucion' => Institucion::first(),
        ]);
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            $user = Auth::user();
            $perfiles = $user->perfiles();

            // Multirol: primero se elige el perfil y luego se redirige.
            if (count($perfiles) > 1) {
                return redirect()->route('perfil.seleccionar');
            }

            $request->session()->put('perfil_activo', $perfiles[0] ?? $user->rol);

            return redirect()->intended($this->redirectTo());
        }

        return back()->withErrors([
            'email' => 'Las credenciales no coinciden con nuestros registros.',
        ])->onlyInput('email');
    }

    /**
     * Pantalla "Seleccione su perfil" posterior al login.
     *
     * Si la cuenta tiene un solo perfil se entra directo, igual que antes.
     */
    public function showPerfilForm(Request $request)
    {
        $user = $request->user();
        $perfiles = $user->perfiles();

        if (count($perfiles) <= 1) {
            $request->session()->put('perfil_activo', $perfiles[0] ?? $user->rol);

            return redirect()->intended(route('dashboard'));
        }

        return view('auth.seleccionar-perfil', [
            'perfiles' => $perfiles,
            'institucion' => Institucion::first(),
        ]);
    }

    /**
     * Guarda el perfil elegido y redirige al panel correspondiente.
     *
     * Se valida contra `perfiles()` para que la sesión no se pueda falsificar
     * con un rol que la cuenta no tiene.
     */
    public function seleccionarPerfil(Request $request)
    {
        $validated = $request->validate([
            'rol' => 'required|string',
        ]);

        $user = $request->user();

        abort_unless(
            in_array($validated['rol'], $user->perfiles(), true),
            403,
            'El perfil seleccionado no está disponible para esta cuenta.'
        );

        $request->session()->put('perfil_activo', $validated['rol']);
        $user->limpiarPerfilesMemo();

        return redirect()->intended($this->redirectTo());
    }

    public function logout(Request $request)
    {
        $request->session()->forget('perfil_activo');

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    /** Destino inicial según el perfil activo. */
    protected function redirectTo(): string
    {
        return match (Auth::user()->perfilActivo()) {
            'ADMIN', 'DOCENTE', 'REPRESENTANTE' => '/dashboard',
            'ESTUDIANTE' => '/mensajes',
            default => '/dashboard',
        };
    }
}
