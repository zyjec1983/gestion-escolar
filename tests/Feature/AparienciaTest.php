<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Pruebas de la apariencia global.
 *
 * Verifican que el administrador pueda elegir una de las 10 paletas y un
 * tamaño de fuente, que se guarde en `ajustes` y que los layouts lo apliquen.
 * Rutas: GET/POST /admin/configuracion y /admin/configuracion/apariencia.
 */
class AparienciaTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_guarda_paleta_y_tamano_de_fuente(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('admin.configuracion.apariencia'), [
                'tema' => 'verde',
                'fuente_pt' => '14',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('ajustes', ['clave' => 'tema_aside', 'valor' => 'verde']);
        $this->assertDatabaseHas('ajustes', ['clave' => 'tema_fuente_pt', 'valor' => '14']);
    }

    public function test_el_layout_aplica_el_color_y_la_fuente_guardados(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('admin.configuracion.apariencia'), [
                'tema' => 'vino',
                'fuente_pt' => '16',
            ])
            ->assertRedirect();

        $this->actingAs($admin)
            ->get(route('admin.configuracion.index'))
            ->assertOk()
            ->assertSee('Apariencia')
            ->assertSee('Vino tinto')
            ->assertSee('--aside-bg: #7B1E2B', false)
            ->assertSee('--app-font-size: 16pt', false);
    }

    public function test_rechaza_paleta_o_fuente_fuera_del_catalogo(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('admin.configuracion.apariencia'), [
                'tema' => 'no-existe',
                'fuente_pt' => '99',
            ])
            ->assertSessionHasErrors(['tema', 'fuente_pt']);

        $this->assertDatabaseMissing('ajustes', ['clave' => 'tema_aside']);
    }

    public function test_solo_el_admin_accede_a_la_configuracion(): void
    {
        $docente = User::factory()->docente()->create();

        $this->actingAs($docente)
            ->get(route('admin.configuracion.index'))
            ->assertForbidden();
    }
}
