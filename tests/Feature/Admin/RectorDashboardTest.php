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

        // Módulos administrables solicitados, agrupados por función.
        $response->assertSee('Portal y contenido');
        $response->assertSee('Apariencia');
        $response->assertSee('Identidad institucional');
        $response->assertSee('Apariencia del sistema');
        $response->assertSee('Carrusel principal');
        $response->assertSee('Carrusel del login');
        $response->assertSee('Contenido institucional');
        $response->assertSee('Textos e imágenes');
        $response->assertSee('Pre-registro');
        $response->assertSee('Historia');
        $response->assertSee('Comunidad educativa');
        $response->assertSee('Servicios y descargas');
        $response->assertSee('Útiles escolares');
        $response->assertSee('Descargas');
        $response->assertSee('Control académico');
        $response->assertSee('Usuarios y roles');
        $response->assertSee('Matrículas e inscripciones');
        $response->assertSee('Ventanas de calificaciones');
        $response->assertSee('Circulares y comunicados');
        $response->assertSee('Moderación de observaciones');
        $response->assertSee('Registros académicos');
        $response->assertSee('Historial de cambios');
        $response->assertSee('Parámetros académicos');

        // El ADMIN no agenda citas ni administra DECE.
        $response->assertDontSee('Citas');
        $response->assertDontSee('Consejería estudiantil');

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
