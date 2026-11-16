<?php

namespace Tests\Feature\Admin;

use App\Models\Alumno;
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

    /** @test */
    public function la_cedula_de_otro_usuario_no_se_puede_duplicar()
    {
        $admin = User::factory()->admin()->create();
        $otro = User::factory()->docente()->create(['numero_cedula' => '1711111111']);
        $objetivo = User::factory()->docente()->create(['numero_cedula' => '1722222222']);

        $this->actingAs($admin)->put(route('admin.usuarios.update', $objetivo), [
            'name' => $objetivo->name,
            'email' => $objetivo->email,
            'numero_cedula' => $otro->numero_cedula,
            'rol' => 'DOCENTE',
        ])->assertSessionHasErrors('numero_cedula');

        $this->assertSame('1722222222', $objetivo->fresh()->numero_cedula);
    }

    /** @test */
    public function el_admin_genera_las_cuentas_de_estudiantes_con_la_cedula()
    {
        $admin = User::factory()->admin()->create();
        $alumno = Alumno::factory()->create([
            'nombres' => 'Luis',
            'apellidos' => 'Mendoza',
            'numero_cedula' => '0900000001',
            'email_activo' => 'luis@correo.test',
            'user_id' => null,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.usuarios.generar-cuentas'))
            ->assertRedirect()
            ->assertSessionHas('success');

        $user = User::where('numero_cedula', '0900000001')->first();

        $this->assertNotNull($user);
        $this->assertSame('REPRESENTANTE', $user->rol);
        $this->assertTrue(Hash::check('0900000001', $user->password));
        $this->assertTrue($user->rolesAdicionales()->where('rol', 'ESTUDIANTE')->exists());
        $this->assertSame($user->id, $alumno->fresh()->user_id);
    }

    /** @test */
    public function la_generacion_masiva_omite_las_cedulas_que_ya_tienen_cuenta()
    {
        $admin = User::factory()->admin()->create();
        $existente = User::factory()->representante()->create([
            'numero_cedula' => '0900000002',
            'email' => 'existente@correo.test',
        ]);
        $alumno = Alumno::factory()->create([
            'numero_cedula' => '0900000002',
            'user_id' => null,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.usuarios.generar-cuentas'))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame(1, User::where('numero_cedula', '0900000002')->count());
        $this->assertNull($alumno->fresh()->user_id);
        $this->assertNotNull($existente->fresh());
    }

    /** @test */
    public function el_admin_edita_el_contrato_numerico_y_la_carga_docente()
    {
        $admin = User::factory()->admin()->create();
        $objetivo = User::factory()->docente()->create();

        $this->actingAs($admin)->put(route('admin.usuarios.update', $objetivo), [
            'name' => $objetivo->name,
            'email' => $objetivo->email,
            'rol' => 'DOCENTE',
            'horas_contrato' => 30,
            'es_tutor' => '1',
            'periodos_atencion_padres' => 2,
            'periodos_reunion_area' => 3,
            'periodos_tutoria' => 1,
        ])->assertRedirect(route('admin.usuarios.index'));

        $objetivo->refresh();

        $this->assertSame(30, $objetivo->horas_contrato);
        $this->assertTrue($objetivo->es_tutor);
        $this->assertSame(2, $objetivo->periodos_atencion_padres);
        $this->assertSame(3, $objetivo->periodos_reunion_area);
        $this->assertSame(1, $objetivo->periodos_tutoria);
    }

    /** @test */
    public function el_contrato_debe_ser_un_numero_entre_1_y_60()
    {
        $admin = User::factory()->admin()->create();
        $objetivo = User::factory()->docente()->create();

        $this->actingAs($admin)->put(route('admin.usuarios.update', $objetivo), [
            'name' => $objetivo->name,
            'email' => $objetivo->email,
            'rol' => 'DOCENTE',
            'horas_contrato' => 0,
        ])->assertSessionHasErrors('horas_contrato');

        $this->actingAs($admin)->put(route('admin.usuarios.update', $objetivo), [
            'name' => $objetivo->name,
            'email' => $objetivo->email,
            'rol' => 'DOCENTE',
            'horas_contrato' => 61,
        ])->assertSessionHasErrors('horas_contrato');

        $this->assertSame(40, $objetivo->fresh()->horas_contrato);
    }

    /** @test */
    public function el_formulario_de_edicion_muestra_el_contrato_y_la_carga_academica()
    {
        $admin = User::factory()->admin()->create();
        $objetivo = User::factory()->docente()->create([
            'horas_contrato' => 30,
            'es_tutor' => true,
            'periodos_reunion_area' => 2,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.usuarios.edit', $objetivo))
            ->assertOk()
            ->assertSee('Contrato (horas reloj)')
            ->assertSee('Docente tutor')
            ->assertSee('Atención a padres')
            ->assertSee('Reunión de área')
            ->assertSee('value="30"', false)
            ->assertSee('value="2"', false);
    }
}
