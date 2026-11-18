<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Endurecimiento del inicio de sesión.
 *
 * Qué hace: comprueba el límite de intentos, el cambio obligatorio de la
 * contraseña temporal y el índice único de la cédula.
 * Conecta con: App\Http\Controllers\Auth\LoginController,
 * App\Http\Middleware\EnsurePasswordIsChanged, ProfileController y
 * App\Providers\AppServiceProvider.
 */
class LoginHardeningTest extends TestCase
{
    /** @test */
    public function el_login_limita_los_intentos_por_identificador_e_ip()
    {
        // Los primeros cinco intentos sí llegan al controlador.
        for ($intento = 0; $intento < 5; $intento++) {
            $this->post('/login', [
                'email' => 'nadie@test.com',
                'password' => 'incorrecta',
            ])->assertSessionHasErrors([
                'email' => 'Las credenciales no coinciden con nuestros registros.',
            ]);
        }

        // El sexto intento ya no llega al controlador: lo frena el limitador.
        $this->post('/login', [
            'email' => 'nadie@test.com',
            'password' => 'incorrecta',
        ])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors([
                'email' => 'Demasiados intentos de inicio de sesión. Espere un momento e intente de nuevo.',
            ]);

        $this->assertGuest();
    }

    /** @test */
    public function el_limite_cuenta_por_identificador_y_no_solo_por_ip()
    {
        for ($intento = 0; $intento < 5; $intento++) {
            $this->post('/login', [
                'email' => 'bloqueada@test.com',
                'password' => 'incorrecta',
            ]);
        }

        $this->post('/login', [
            'email' => 'bloqueada@test.com',
            'password' => 'incorrecta',
        ])->assertSessionHasErrors([
            'email' => 'Demasiados intentos de inicio de sesión. Espere un momento e intente de nuevo.',
        ]);

        // Otra cuenta desde la misma IP todavía llega al controlador: el
        // bloqueo es por identificador + IP, no solo por IP.
        $this->post('/login', [
            'email' => 'otra@test.com',
            'password' => 'incorrecta',
        ])->assertSessionHasErrors([
            'email' => 'Las credenciales no coinciden con nuestros registros.',
        ]);
    }

    /** @test */
    public function la_cuenta_con_clave_temporal_debe_cambiarla_antes_de_entrar()
    {
        $user = User::factory()->docente()->create([
            'email' => 'temporal@test.com',
            'password' => 'Temporal123',
            'debe_cambiar_password' => true,
        ]);

        $this->post('/login', [
            'email' => 'temporal@test.com',
            'password' => 'Temporal123',
        ])->assertRedirect(route('perfil.password.obligatorio'));

        $this->assertAuthenticatedAs($user);

        // Cualquier módulo redirige al cambio obligatorio.
        $this->get('/dashboard')->assertRedirect(route('perfil.password.obligatorio'));

        $this->get(route('perfil.password.obligatorio'))
            ->assertOk()
            ->assertSee('Cambiar contraseña')
            ->assertSee('contraseña temporal');

        // No se pide la clave anterior: la persona acaba de autenticarse.
        $this->post(route('perfil.password.obligatorio.update'), [
            'password' => 'NuevaClave123',
            'password_confirmation' => 'NuevaClave123',
        ])
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('success');

        $user->refresh();

        $this->assertFalse($user->debe_cambiar_password);
        $this->assertTrue(Hash::check('NuevaClave123', $user->password));

        // Ya puede navegar con normalidad.
        $this->get('/dashboard')->assertOk();
    }

    /** @test */
    public function el_cambio_obligatorio_exige_letras_y_numeros()
    {
        $user = User::factory()->docente()->create([
            'password' => 'Temporal123',
            'debe_cambiar_password' => true,
        ]);

        $this->actingAs($user)
            ->post(route('perfil.password.obligatorio.update'), [
                'password' => 'sololetras',
                'password_confirmation' => 'sololetras',
            ])
            ->assertSessionHasErrors('password');

        $this->assertTrue($user->fresh()->debe_cambiar_password);
    }

    /** @test */
    public function cambiar_la_contrasena_invalida_las_demas_sesiones()
    {
        $user = User::factory()->docente()->create([
            'password' => 'ClaveVieja123',
        ]);

        // Sesión abierta antes del cambio: guarda el hash de la clave vieja.
        $this->actingAs($user)
            ->withSession(['password_hash_web' => $user->password])
            ->get('/dashboard')
            ->assertOk();

        // El cambio se hace desde otro dispositivo.
        $user->forceFill(['password' => 'ClaveNueva123'])->save();

        // La sesión anterior queda cerrada en su siguiente petición.
        $this->get('/dashboard')->assertRedirect('/login');
    }

    /** @test */
    public function la_cedula_tiene_indice_unico_en_la_base_de_datos()
    {
        $indice = collect(Schema::getIndexes('users'))
            ->firstWhere('name', 'users_numero_cedula_unique');

        $this->assertNotNull($indice, 'Falta el índice único de users.numero_cedula.');
        $this->assertTrue((bool) $indice['unique']);
    }
}
