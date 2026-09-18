<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_usuario_accede_a_su_perfil(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('perfil.index'))
            ->assertOk()
            ->assertSee('Datos de la cuenta')
            ->assertSee('Cambiar contraseña');
    }

    public function test_usuario_actualiza_correo_y_celular(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('perfil.update'), [
                'name' => 'Carlos',
                'apellidos' => 'Mendoza',
                'email' => 'nuevo@correo.com',
                'telefono' => '0987654321',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'email' => 'nuevo@correo.com',
            'telefono' => '0987654321',
        ]);
    }

    public function test_usuario_no_puede_usar_correo_de_otro_usuario(): void
    {
        $otro = User::factory()->create(['email' => 'ocupado@correo.com']);
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('perfil.update'), [
                'name' => 'Carlos',
                'apellidos' => 'Mendoza',
                'email' => 'ocupado@correo.com',
            ])
            ->assertSessionHasErrors('email');
    }

    public function test_usuario_cambia_contraseña_con_actual_correcta(): void
    {
        $user = User::factory()->create(['password' => Hash::make('clave12345')]);

        $this->actingAs($user)
            ->post(route('perfil.password'), [
                'current_password' => 'clave12345',
                'password' => 'nuevaClave99',
                'password_confirmation' => 'nuevaClave99',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertTrue(Hash::check('nuevaClave99', $user->fresh()->password));
    }

    public function test_usuario_no_cambia_contraseña_con_actual_incorrecta(): void
    {
        $user = User::factory()->create(['password' => Hash::make('clave12345')]);

        $this->actingAs($user)
            ->post(route('perfil.password'), [
                'current_password' => 'incorrecta',
                'password' => 'nuevaClave99',
                'password_confirmation' => 'nuevaClave99',
            ])
            ->assertSessionHasErrors('current_password');

        $this->assertTrue(Hash::check('clave12345', $user->fresh()->password));
    }
}