<?php

namespace Tests\Feature\Admin;

use App\Models\Institucion;
use App\Models\User;
use Tests\TestCase;

class RectorDashboardTest extends TestCase
{
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();

        Institucion::firstOrCreate([], [
            'nombre' => 'Unidad Educativa',
            'zona_horaria' => '-05:00',
        ]);
    }

    /** @test */
    public function el_dashboard_del_admin_es_la_vista_de_administracion()
    {
        $response = $this->actingAs($this->admin)->get('/dashboard');

        $response->assertOk();
        $response->assertViewIs('dashboard.admin');
        $response->assertSee('Panel de Administración');
        $response->assertSee('Estudiantes');
        $response->assertSee('Ventanas activas');
        $response->assertSee('Módulos de administración');
    }

    /** @test */
    public function el_sidebar_del_admin_muestra_solo_los_modulos_solicitados()
    {
        $response = $this->actingAs($this->admin)->get('/dashboard');

        // Módulos administrables solicitados.
        $response->assertSee('Sitio web');
        $response->assertSee('Comunidad educativa');
        $response->assertSee('Carrusel del portal');
        $response->assertSee('Carrusel del login');
        $response->assertSee('Circulares');
        $response->assertSee('Observaciones');
        $response->assertSee('Fechas de subida de notas');
        $response->assertSee('Usuarios');
        $response->assertSee('Historial de cambios');
        $response->assertSee('Configuración');

        // Los menús operativos de otros roles no se muestran al ADMIN.
        $response->assertDontSee('Solicitudes de matrícula');
        $response->assertDontSee('Mis Clases');
        $response->assertDontSee('Planificación');
    }

    /** @test */
    public function la_gestion_del_carrusel_vive_dentro_de_configuracion()
    {
        $response = $this->actingAs($this->admin)->get(route('admin.configuracion.index'));

        $response->assertOk();
        $response->assertViewIs('admin.configuracion.index');
        $response->assertSee('Carrusel del portal');
        $response->assertSee('Velocidad de transición');
    }

    /** @test */
    public function la_vista_hero_slides_redirige_a_configuracion()
    {
        $response = $this->actingAs($this->admin)->get(route('admin.hero-slides.index'));

        $response->assertRedirect(route('admin.configuracion.index'));
    }

    /** @test */
    public function las_vistas_admin_usan_el_layout_principal()
    {
        $response = $this->actingAs($this->admin)->get(route('admin.configuracion.index'));

        $response->assertViewHasAll(['institucion', 'desfases', 'slides']);
        $this->assertStringNotContainsString(
            'layouts.admin',
            (string) view('admin.configuracion.index', [
                'institucion' => Institucion::first(),
                'desfases' => desfasesHorariosDisponibles(),
                'slides' => collect(),
            ])->render()
        );
    }
}
