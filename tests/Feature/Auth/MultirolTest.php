<?php

namespace Tests\Feature\Auth;

use App\Models\ClaseDocente;
use App\Models\Curso;
use App\Models\Materia;
use App\Models\Paralelo;
use App\Models\User;
use App\Models\UserRole;
use Tests\TestCase;

/**
 * Multirol y perfil activo.
 *
 * Qué hace: verifica que una cuenta pueda tener varios perfiles, que el login
 * pida elegir uno y que el middleware use el perfil activo y no `users.rol`.
 * Conecta con: LoginController, User::perfiles()/perfilActivo(), RoleMiddleware.
 * Rutas: POST /login, GET /perfil/seleccionar, POST /perfil/activo.
 */
class MultirolTest extends TestCase
{
    /** Crea una cuenta con rol principal RECTORA y los adicionales indicados. */
    private function usuarioMultirol(array $adicionales = ['SECRETARIA', 'DECE'], string $password = 'password'): User
    {
        $user = User::factory()->rectora()->create([
            'email' => 'multirol@test.com',
            'password' => bcrypt($password),
        ]);

        foreach ($adicionales as $rol) {
            UserRole::create(['user_id' => $user->id, 'rol' => $rol]);
        }

        return $user;
    }

    /** @test */
    public function el_login_pide_elegir_perfil_cuando_la_cuenta_es_multirol()
    {
        $user = $this->usuarioMultirol();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('perfil.seleccionar'));
        $this->assertAuthenticatedAs($user);
    }

    /** @test */
    public function el_perfil_elegido_se_guarda_en_la_sesion_y_redirige_al_panel()
    {
        $user = $this->usuarioMultirol();

        $response = $this->actingAs($user)->post(route('perfil.activo'), [
            'rol' => 'DECE',
        ]);

        $response->assertRedirect('/dashboard');
        $response->assertSessionHas('perfil_activo', 'DECE');
    }

    /** @test */
    public function no_se_puede_activar_un_perfil_que_la_cuenta_no_tiene()
    {
        $user = $this->usuarioMultirol();

        $this->actingAs($user)
            ->post(route('perfil.activo'), ['rol' => 'ADMIN'])
            ->assertForbidden();
    }

    /** @test */
    public function el_middleware_usa_el_perfil_activo_y_no_el_rol_principal()
    {
        // Rol principal ADMIN, pero navegando como Secretaría: no debe entrar
        // a las rutas exclusivas de Administración.
        $admin = User::factory()->admin()->create();
        UserRole::create(['user_id' => $admin->id, 'rol' => 'SECRETARIA']);

        $this->actingAs($admin);
        session(['perfil_activo' => 'SECRETARIA']);

        $this->get(route('admin.configuracion.index'))->assertForbidden();
    }

    /** @test */
    public function un_docente_con_clases_conserva_el_perfil_de_docencia()
    {
        $user = User::factory()->secretaria()->create();

        $materia = Materia::create(['nombre' => 'Matemática', 'codigo' => 'MAT', 'color_tarjeta' => '#000000']);

        ClaseDocente::create([
            'user_id' => $user->id,
            'materia_id' => $materia->id,
            'curso_id' => Curso::factory()->create()->id,
            'paralelo_id' => Paralelo::factory()->create()->id,
        ]);

        $user->limpiarPerfilesMemo();

        $this->assertContains('DOCENTE', $user->perfiles());
    }

    /** @test */
    public function la_pantalla_de_perfiles_muestra_los_roles_disponibles()
    {
        $user = $this->usuarioMultirol();

        $this->actingAs($user)
            ->get(route('perfil.seleccionar'))
            ->assertOk()
            ->assertViewIs('auth.seleccionar-perfil')
            ->assertSee('Seleccione su perfil')
            ->assertSee('Rectorado')
            ->assertSee('Consejería estudiantil (DECE)');
    }

    /** @test */
    public function una_cuenta_de_un_solo_perfil_entra_directo_sin_pantalla_intermedia()
    {
        $user = User::factory()->secretaria()->create();

        $this->actingAs($user)
            ->get(route('perfil.seleccionar'))
            ->assertRedirect(route('dashboard'));

        $this->assertSame('SECRETARIA', session('perfil_activo'));
    }

    /** @test */
    public function el_perfil_activo_se_descarta_al_cerrar_sesion()
    {
        $user = $this->usuarioMultirol();

        $this->actingAs($user);
        session(['perfil_activo' => 'DECE']);

        $this->post(route('logout'))->assertRedirect('/');

        $this->assertGuest();
    }
}
