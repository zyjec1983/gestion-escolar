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
    public function el_dashboard_del_rector_es_la_vista_rector()
    {
        $response = $this->actingAs($this->admin)->get('/dashboard');

        $response->assertOk();
        $response->assertViewIs('dashboard.rector');
        $response->assertSee('Panel de Rectorado');
        $response->assertSee('Estudiantes matriculados');
        $response->assertSee('Solicitudes pendientes');
        $response->assertSee('Cursos abiertos');
    }

    /** @test */
    public function el_sidebar_muestra_solicitudes_de_matricula_para_admin()
    {
        $response = $this->actingAs($this->admin)->get('/dashboard');

        $response->assertSee('Solicitudes de matrícula');
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
