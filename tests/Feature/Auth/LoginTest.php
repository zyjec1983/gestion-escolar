<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Tests\TestCase;

class LoginTest extends TestCase
{
    /** @test */
    public function show_login_form()
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertViewIs('auth.login');
        $response->assertSee('Volver al inicio');
        $response->assertSee(route('welcome'), false);
    }

    /** @test */
    public function admin_redirects_to_dashboard()
    {
        $admin = User::factory()->admin()->create([
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
        ]);

        $response = $this->post('/login', [
            'email' => 'admin@test.com',
            'password' => 'password',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticated();
    }

    /** @test */
    public function docente_redirects_to_dashboard()
    {
        $docente = User::factory()->docente()->create([
            'email' => 'docente@test.com',
            'password' => bcrypt('password'),
        ]);

        $response = $this->post('/login', [
            'email' => 'docente@test.com',
            'password' => 'password',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticated();
    }

    /** @test */
    public function representante_redirects_to_dashboard()
    {
        $rep = User::factory()->representante()->create([
            'email' => 'rep@test.com',
            'password' => bcrypt('password'),
        ]);

        $response = $this->post('/login', [
            'email' => 'rep@test.com',
            'password' => 'password',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticated();
    }

    /** @test */
    public function estudiante_redirects_to_mensajes()
    {
        $est = User::factory()->estudiante()->create([
            'email' => 'est@test.com',
            'password' => bcrypt('password'),
        ]);

        $response = $this->post('/login', [
            'email' => 'est@test.com',
            'password' => 'password',
        ]);

        $response->assertRedirect('/mensajes');
        $this->assertAuthenticated();
    }

    /** @test */
    public function login_fails_with_invalid_credentials()
    {
        $response = $this->post('/login', [
            'email' => 'noexiste@test.com',
            'password' => 'wrong',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    /** @test */
    public function login_requires_email_and_password()
    {
        $response = $this->post('/login', []);

        $response->assertSessionHasErrors(['email', 'password']);
    }

    /** @test */
    public function logout_redirects_to_home()
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $response = $this->post('/logout');

        $response->assertRedirect('/');
        $this->assertGuest();
    }

    /** @test */
    public function login_acepta_la_cedula_como_usuario()
    {
        $user = User::factory()->representante()->create([
            'email' => 'familia@test.com',
            'numero_cedula' => '0900000001',
            'password' => bcrypt('password'),
        ]);

        $response = $this->post('/login', [
            'email' => '0900000001',
            'password' => 'password',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
    }

    /** @test */
    public function al_tercer_intento_fallido_la_cuenta_se_bloquea_una_hora()
    {
        $user = User::factory()->docente()->create([
            'email' => 'docente@test.com',
            'password' => bcrypt('password'),
        ]);

        for ($intento = 0; $intento < 3; $intento++) {
            $this->post('/login', [
                'email' => 'docente@test.com',
                'password' => 'incorrecta',
            ]);
        }

        $user->refresh();

        $this->assertNotNull($user->bloqueado_hasta);
        $this->assertTrue($user->estaBloqueado());
        $this->assertGuest();

        // Aun con la contraseña correcta, el bloqueo temporal sigue vigente.
        $this->post('/login', [
            'email' => 'docente@test.com',
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    /** @test */
    public function el_segundo_bloqueo_solo_lo_levanta_el_administrador()
    {
        $user = User::factory()->docente()->create([
            'email' => 'docente@test.com',
            'password' => bcrypt('password'),
            'bloqueos_consecutivos' => 1,
        ]);

        for ($intento = 0; $intento < 3; $intento++) {
            $this->post('/login', [
                'email' => 'docente@test.com',
                'password' => 'incorrecta',
            ]);
        }

        $user->refresh();

        $this->assertTrue($user->requiere_desbloqueo);
        $this->assertNull($user->bloqueado_hasta);

        // Esperar no sirve: el bloqueo es permanente hasta que ADMIN lo levante.
        $this->travel(2)->hours();

        $this->post('/login', [
            'email' => 'docente@test.com',
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    /** @test */
    public function el_administrador_desbloquea_una_cuenta()
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->docente()->create([
            'requiere_desbloqueo' => true,
            'intentos_fallidos' => 3,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.usuarios.desbloquear', $user))
            ->assertRedirect();

        $user->refresh();

        $this->assertFalse($user->requiere_desbloqueo);
        $this->assertNull($user->bloqueado_hasta);
        $this->assertSame(0, (int) $user->intentos_fallidos);
        $this->assertFalse($user->estaBloqueado());
    }
}
