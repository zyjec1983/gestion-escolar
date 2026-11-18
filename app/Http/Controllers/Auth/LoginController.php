<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\HeroSlide;
use App\Models\Institucion;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

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

    /**
     * Autentica con correo electrónico o cédula.
     *
     * Reglas:
     * - El identificador puede ser el correo o la cédula registrada.
     * - Al tercer intento fallido la cuenta se bloquea 1 hora.
     * - Si se repite el bloqueo, solo Administración puede desbloquearla.
     *
     * Conecta con: App\Models\User (estaBloqueado, registrarIntentoFallido,
     * limpiarIntentosFallidos), RoleMiddleware y layouts/app.
     * Ruta: POST /login.
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|string|max:255',
            'password' => 'required',
        ]);

        $identificador = trim($credentials['email']);
        $campo = filter_var($identificador, FILTER_VALIDATE_EMAIL) ? 'email' : 'numero_cedula';

        $user = User::where($campo, $identificador)->first();

        if (! $user) {
            // Se calcula un hash con el mismo costo configurado para que el
            // tiempo de respuesta se parezca al de una cuenta existente. Sin
            // esto se podría deducir qué correos están registrados midiendo
            // cuánto tarda el servidor.
            Hash::make(Str::random(32));

            $this->auditar('warning', 'Intento de inicio de sesión con una cuenta inexistente.', $request);

            return back()->withErrors([
                'email' => 'Las credenciales no coinciden con nuestros registros.',
            ])->onlyInput('email');
        }

        if ($user->estaBloqueado()) {
            $this->auditar('warning', 'Intento de inicio de sesión en una cuenta bloqueada.', $request, $user);

            return back()->withErrors([
                'email' => $user->mensajeBloqueo(),
            ])->onlyInput('email');
        }

        if (Hash::check($credentials['password'], $user->password)) {
            // Si el costo del algoritmo cambió, se actualiza el hash sin
            // obligar a la persona a cambiar de contraseña.
            if (Hash::needsRehash($user->password)) {
                $user->forceFill(['password' => $credentials['password']])->save();
            }

            Auth::login($user, $request->boolean('remember'));
            $request->session()->regenerate();

            $user->limpiarIntentosFallidos();
            $this->auditar('info', 'Inicio de sesión exitoso.', $request, $user);

            // Las cuentas con clave temporal solo pueden ir a cambiarla.
            if ($user->debe_cambiar_password) {
                return redirect()->route('perfil.password.obligatorio');
            }

            $perfiles = $user->perfiles();

            // Multirol: primero se elige el perfil y luego se redirige.
            if (count($perfiles) > 1) {
                return redirect()->route('perfil.seleccionar');
            }

            $request->session()->put('perfil_activo', $perfiles[0] ?? $user->rol);

            return redirect()->intended($this->redirectTo());
        }

        // Solo se cuentan los intentos de cuentas existentes: así no se puede
        // descubrir ni bloquear cuentas ajenas escribiendo correos al azar.
        $user->registrarIntentoFallido();

        $this->auditar('warning', 'Intento de inicio de sesión fallido.', $request, $user);

        if ($user->estaBloqueado()) {
            $this->auditar('warning', 'Cuenta bloqueada tras varios intentos fallidos.', $request, $user);

            return back()->withErrors([
                'email' => $user->mensajeBloqueo(),
            ])->onlyInput('email');
        }

        return back()->withErrors([
            'email' => 'Las credenciales no coinciden con nuestros registros.',
        ])->onlyInput('email');
    }

    /**
     * Deja un rastro de auditoría sin guardar nunca la contraseña.
     *
     * Conecta con: storage/logs/laravel.log (canal `stack`/`single`).
     */
    private function auditar(string $nivel, string $mensaje, Request $request, ?User $user = null): void
    {
        Log::log($nivel, $mensaje, [
            'user_id' => $user?->id,
            'identificador' => mb_substr(trim((string) $request->input('email')), 0, 255),
            'ip' => $request->ip(),
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 500),
        ]);
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
