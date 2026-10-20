<?php

namespace Tests\Feature;

use App\Models\Ajuste;
use App\Models\TipoObservacion;
use App\Models\User;
use Database\Seeders\TipoObservacionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ObservacionTiposTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(TipoObservacionSeeder::class);
    }

    public function test_catalogo_index_autoridades_pueden_verlo(): void
    {
        $this->actingAs(User::factory()->create(['rol' => 'RECTORA']))
            ->get(route('inspeccion.observaciones.tipos.index'))
            ->assertOk()
            ->assertSee('Catálogo de tipos de observación');
    }

    public function test_inspector_sin_switch_no_puede_crear_tipo(): void
    {
        $this->actingAs(User::factory()->create(['rol' => 'INSPECCION']))
            ->post(route('inspeccion.observaciones.tipos.store'), [
                'nombre' => 'UNIFORME CORRECTO',
                'clasificacion' => 'positiva',
            ])
            ->assertForbidden();
    }

    public function test_inspector_con_switch_puede_crear_tipo(): void
    {
        Ajuste::guardar(Ajuste::TIPOS_OBS_EDICION, '1');
        $this->actingAs(User::factory()->create(['rol' => 'INSPECCION']))
            ->post(route('inspeccion.observaciones.tipos.store'), [
                'nombre' => 'FELICITACION ESPECIAL',
                'clasificacion' => 'positiva',
            ])
            ->assertRedirect(route('inspeccion.observaciones.tipos.index'));

        $this->assertTrue(TipoObservacion::where('nombre', 'FELICITACION ESPECIAL')->exists());
    }

    public function test_rector_puede_crear_tipo_sin_switch(): void
    {
        $this->actingAs(User::factory()->create(['rol' => 'RECTORA']))
            ->post(route('inspeccion.observaciones.tipos.store'), [
                'nombre' => 'BUENA PARTICIPACION',
                'clasificacion' => 'positiva',
            ])
            ->assertRedirect(route('inspeccion.observaciones.tipos.index'));
    }

    public function test_no_se_puede_registrar_observacion_con_tipo_deshabilitado(): void
    {
        $tipo = TipoObservacion::create([
            'codigo' => 'OBS_TEST_OFF',
            'nombre' => 'TIPO DESHABILITADO',
            'clasificacion' => 'leve',
            'activo' => false,
        ]);

        $this->actingAs(User::factory()->create(['rol' => 'INSPECCION']))
            ->post(route('inspeccion.observaciones.store'), [
                'alumno_id' => 1,
                'tipo_observacion_id' => $tipo->id,
                'descripcion_texto' => 'Intento con un tipo deshabilitado.',
            ])
            ->assertSessionHasErrors('tipo_observacion_id');
    }

    public function test_reporte_alumno_requiere_rol_permitido(): void
    {
        $this->actingAs(User::factory()->docente()->create())
            ->get(route('inspeccion.observaciones.reporte.alumno'))
            ->assertForbidden();
    }

    public function test_estadisticas_requiere_rol_permitido(): void
    {
        $this->actingAs(User::factory()->create(['rol' => 'DOCENTE']))
            ->get(route('inspeccion.observaciones.estadisticas'))
            ->assertForbidden();
    }

    public function test_estadisticas_renderiza_para_inspeccion(): void
    {
        $this->actingAs(User::factory()->create(['rol' => 'INSPECCION']))
            ->get(route('inspeccion.observaciones.estadisticas'))
            ->assertOk()
            ->assertSee('Estadísticas de Observaciones')
            ->assertSee('Docentes que más registran observaciones');
    }
}
