<?php

namespace Tests\Feature\Admin;

use App\Models\HeroSlide;
use App\Models\Institucion;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Institución, logo/favicon y carrusel por ubicación.
 *
 * Qué hace: valida que el nombre y el logo configurados por el administrador
 * se muestren en el login y en el inicio, y que cada carrusel use solo las
 * imágenes marcadas para su ubicación.
 * Conecta con: ConfiguracionController, HeroSlideController, Institucion,
 * HeroSlide, layouts/app y auth/login.
 * Rutas: POST /admin/configuracion/institucion, POST /admin/hero-slides, GET /,
 *        GET /login.
 */
class InstitucionTest extends TestCase
{
    /** @test */
    public function el_admin_actualiza_nombre_subtitulo_y_logo()
    {
        Storage::fake('public');

        $institucion = Institucion::factory()->create(['nombre' => 'Nombre anterior']);
        $admin = User::factory()->admin()->create(['institucion_id' => $institucion->id]);

        $response = $this->actingAs($admin)->post(route('admin.configuracion.institucion'), [
            'nombre' => 'Unidad Educativa Nueva',
            'subtitulo' => 'Bilingüe',
            'logo' => UploadedFile::fake()->image('logo.png', 200, 200),
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $institucion->refresh();

        $this->assertSame('Unidad Educativa Nueva', $institucion->nombre);
        $this->assertSame('Bilingüe', $institucion->subtitulo);
        $this->assertNotNull($institucion->logo_path);
        Storage::disk('public')->assertExists($institucion->logo_path);
    }

    /** @test */
    public function el_login_muestra_el_nombre_y_usa_el_logo_como_favicon()
    {
        Storage::fake('public');

        $institucion = Institucion::factory()->create([
            'nombre' => 'Colegio Central',
            'logo_path' => 'institucion/logo.png',
        ]);

        Storage::disk('public')->put($institucion->logo_path, 'logo');

        $response = $this->get('/login');

        $response->assertOk();
        $response->assertSee('Colegio Central');
        $response->assertSee('rel="icon"', false);
        $response->assertSee($institucion->logoUrl(), false);
    }

    /** @test */
    public function el_admin_puede_quitar_el_logo()
    {
        Storage::fake('public');

        $institucion = Institucion::factory()->create(['logo_path' => 'institucion/viejo.png']);
        $admin = User::factory()->admin()->create(['institucion_id' => $institucion->id]);
        Storage::disk('public')->put($institucion->logo_path, 'logo');

        $this->actingAs($admin)->post(route('admin.configuracion.institucion'), [
            'nombre' => $institucion->nombre,
            'quitar_logo' => '1',
        ])->assertRedirect();

        $this->assertNull($institucion->fresh()->logo_path);
        Storage::disk('public')->assertMissing('institucion/viejo.png');
    }

    /** @test */
    public function el_nombre_de_la_institucion_es_obligatorio()
    {
        $admin = User::factory()->admin()->create();
        Institucion::factory()->create();

        $this->actingAs($admin)
            ->post(route('admin.configuracion.institucion'), ['nombre' => ''])
            ->assertSessionHasErrors('nombre');
    }

    /** @test */
    public function un_usuario_sin_perfil_admin_no_puede_cambiar_la_institucion()
    {
        $docente = User::factory()->docente()->create();
        Institucion::factory()->create();

        $this->actingAs($docente)
            ->post(route('admin.configuracion.institucion'), ['nombre' => 'Otro nombre'])
            ->assertForbidden();
    }

    /** @test */
    public function el_login_solo_muestra_los_slides_de_su_ubicacion()
    {
        Institucion::factory()->create();

        HeroSlide::factory()->create(['titulo' => 'SoloLogin', 'ubicacion' => 'login']);
        HeroSlide::factory()->create(['titulo' => 'SoloInicio', 'ubicacion' => 'inicio']);
        HeroSlide::factory()->create(['titulo' => 'EnAmbos', 'ubicacion' => 'ambos']);
        HeroSlide::factory()->create(['titulo' => 'InactivoLogin', 'ubicacion' => 'login', 'activo' => false]);

        $login = $this->get('/login');
        $login->assertOk();
        $login->assertSee('SoloLogin');
        $login->assertSee('EnAmbos');
        $login->assertDontSee('SoloInicio');
        $login->assertDontSee('InactivoLogin');

        $inicio = $this->get('/');
        $inicio->assertOk();
        $inicio->assertSee('SoloInicio');
        $inicio->assertSee('EnAmbos');
        $inicio->assertDontSee('SoloLogin');
    }

    /** @test */
    public function el_admin_guarda_la_ubicacion_al_crear_un_slide()
    {
        Storage::fake('public');

        $admin = User::factory()->admin()->create();
        Institucion::factory()->create();

        $this->actingAs($admin)->post(route('admin.hero-slides.store'), [
            'titulo' => 'Slide de login',
            'imagen' => UploadedFile::fake()->image('slide.jpg', 800, 600),
            'ubicacion' => 'login',
        ])->assertRedirect();

        $this->assertDatabaseHas('hero_slides', [
            'titulo' => 'Slide de login',
            'ubicacion' => 'login',
        ]);
    }

    /** @test */
    public function el_carrusel_de_configuracion_muestra_la_columna_mostrar_en()
    {
        $admin = User::factory()->admin()->create();
        Institucion::factory()->create();
        HeroSlide::factory()->create(['titulo' => 'Slide login', 'ubicacion' => 'login']);

        $this->actingAs($admin)
            ->get(route('admin.configuracion.index'))
            ->assertOk()
            ->assertSee('Mostrar en')
            ->assertSee('Inicio de sesión');
    }
}
