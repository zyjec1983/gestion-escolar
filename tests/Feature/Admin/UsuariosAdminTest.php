<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\UserRole;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Módulo de usuarios y multirol.
 *
 * Qué hace: comprueba el listado, la búsqueda, el cambio de rol principal y la
 * asignación de roles adicionales, además de las protecciones de ADMIN.
 * Conecta con: App\Http\Controllers\Admin\UserController y App\Models\UserRole.
 * Rutas: GET /admin/usuarios, GET /admin/usuarios/{user}/editar,
 *        PUT /admin/usuarios/{user}.
 */
class UsuariosAdminTest extends TestCase
{
    /** @test */
    public function el_admin_ve_el_listado_y_puede_buscar_por_nombre()
    {
        $admin = User::factory()->admin()->create();
        $objetivo = User::factory()->docente()->create([
            'name' => 'Ana',
            'apellidos' => 'Pérez',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.usuarios.index', ['buscar' => 'Ana']));

        $response->assertOk();
        $response->assertViewIs('admin.usuarios.index');
        $response->assertSee('Ana');
        $response->assertSee($objetivo->email);
    }

    /** @test */
    public function un_usuario_sin_perfil_admin_no_entra_al_modulo()
    {
        $docente = User::factory()->docente()->create();

        $this->actingAs($docente)
            ->get(route('admin.usuarios.index'))
            ->assertForbidden();
    }

    /** @test */
    public function el_admin_actualiza_rol_principal_y_roles_adicionales()
    {
        $admin = User::factory()->admin()->create();
        $objetivo = User::factory()->docente()->create();

        $response = $this->actingAs($admin)->put(route('admin.usuarios.update', $objetivo), [
            'name' => $objetivo->name,
            'apellidos' => $objetivo->apellidos,
            'email' => $objetivo->email,
            'telefono' => '0999999999',
            'rol' => 'SECRETARIA',
            'roles_adicionales' => ['DECE', 'INSPECCION'],
        ]);

        $response->assertRedirect(route('admin.usuarios.index'));
        $response->assertSessionHas('success');

        $objetivo->refresh();

        $this->assertSame('SECRETARIA', $objetivo->rol);
        $this->assertSame('0999999999', $objetivo->telefono);
        $this->assertEqualsCanonicalizing(
            ['DECE', 'INSPECCION'],
            $objetivo->rolesAdicionales()->pluck('rol')->all()
        );
    }

    /** @test */
    public function el_rol_principal_no_se_duplica_como_rol_adicional()
    {
        $admin = User::factory()->admin()->create();
        $objetivo = User::factory()->docente()->create();

        $this->actingAs($admin)->put(route('admin.usuarios.update', $objetivo), [
            'name' => $objetivo->name,
            'email' => $objetivo->email,
            'rol' => 'SECRETARIA',
            'roles_adicionales' => ['SECRETARIA', 'DECE'],
        ])->assertRedirect(route('admin.usuarios.index'));

        $this->assertSame(
            ['DECE'],
            $objetivo->rolesAdicionales()->pluck('rol')->all()
        );
    }

    /** @test */
    public function el_admin_no_puede_quitarse_a_si_mismo_el_rol_de_administracion()
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->put(route('admin.usuarios.update', $admin), [
            'name' => $admin->name,
            'email' => $admin->email,
            'rol' => 'RECTORA',
        ]);

        $response->assertSessionHasErrors('rol');
        $this->assertSame('ADMIN', $admin->fresh()->rol);
    }

    /** @test */
    public function el_admin_puede_restablecer_la_contrasena_de_un_usuario()
    {
        $admin = User::factory()->admin()->create();
        $objetivo = User::factory()->docente()->create();

        $this->actingAs($admin)->put(route('admin.usuarios.update', $objetivo), [
            'name' => $objetivo->name,
            'email' => $objetivo->email,
            'rol' => 'DOCENTE',
            'password' => 'nueva-clave-123',
            'password_confirmation' => 'nueva-clave-123',
        ])->assertRedirect(route('admin.usuarios.index'));

        $this->assertTrue(Hash::check('nueva-clave-123', $objetivo->fresh()->password));
    }

    /** @test */
    public function el_formulario_de_edicion_muestra_los_roles_actuales()
    {
        $admin = User::factory()->admin()->create();
        $objetivo = User::factory()->docente()->create();
        UserRole::create(['user_id' => $objetivo->id, 'rol' => 'DECE']);

        $this->actingAs($admin)
            ->get(route('admin.usuarios.edit', $objetivo))
            ->assertOk()
            ->assertViewIs('admin.usuarios.edit')
            ->assertSee('Roles adicionales')
            ->assertSee('Consejería estudiantil (DECE)');
    }

    /** @test */
    public function el_correo_de_otro_usuario_no_se_puede_duplicar()
    {
        $admin = User::factory()->admin()->create();
        $otro = User::factory()->docente()->create(['email' => 'ocupado@test.com']);
        $objetivo = User::factory()->docente()->create();

        $this->actingAs($admin)->put(route('admin.usuarios.update', $objetivo), [
            'name' => $objetivo->name,
            'email' => $otro->email,
            'rol' => 'DOCENTE',
        ])->assertSessionHasErrors('email');
    }
}
