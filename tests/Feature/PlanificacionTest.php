<?php

namespace Tests\Feature;

use App\Models\ClaseDocente;
use App\Models\Curso;
use App\Models\Materia;
use App\Models\Paralelo;
use App\Models\PeriodoLectivo;
use App\Models\Unidad;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlanificacionTest extends TestCase
{
    use RefreshDatabase;

    private User $secretaria;
    private User $docente;
    private Materia $materia;
    private Curso $curso;
    private Paralelo $paralelo;
    private array $paralelos;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->secretaria = User::factory()->create(['rol' => 'SECRETARIA', 'institucion_id' => 1]);
        $this->docente = User::factory()->create(['rol' => 'DOCENTE', 'institucion_id' => 1]);
        $this->materia = Materia::first();
        $this->curso = Curso::first();
        $this->paralelos = Paralelo::limit(3)->pluck('id')->all();
        $this->paralelo = Paralelo::first();

        foreach ($this->paralelos as $p) {
            ClaseDocente::firstOrCreate([
                'user_id' => $this->docente->id,
                'materia_id' => $this->materia->id,
                'curso_id' => $this->curso->id,
                'paralelo_id' => $p,
            ]);
        }
    }

    public function test_docente_puede_acceder_a_crear_planificaciones(): void
    {
        $response = $this->actingAs($this->docente)->get('/planificaciones/crear');

        $response->assertOk()
            ->assertSee('Crear Planificación de Unidad');
    }

    public function test_secretaria_puede_ver_formulario(): void
    {
        $response = $this->actingAs($this->secretaria)->get('/planificaciones/crear');

        $response->assertOk()
            ->assertSee('Crear Planificación de Unidad');
    }

    public function test_crear_planificacion_replica_a_paralelos_con_mismo_grupo_id(): void
    {
        $periodo = PeriodoLectivo::where('activo', true)->first();

        $response = $this->actingAs($this->secretaria)->post('/planificaciones', [
            'materia_id' => $this->materia->id,
            'curso_id' => $this->curso->id,
            'paralelo_ids' => $this->paralelos,
            'titulo' => 'Unit X - Ensayo argumentativo',
            'descripcion' => 'Planificación de prueba con réplica.',
            'fecha_inicio' => '2026-10-01',
            'fecha_fin' => '2026-10-15',
            'quimestre' => 'Q2',
            'parcial' => 'P1',
        ]);

        $response->assertRedirect(route('planificaciones.index'));
        $response->assertSessionHas('success');

        $this->assertSame(3, Unidad::where('titulo', 'Unit X - Ensayo argumentativo')->count());

        $unidades = Unidad::where('titulo', 'Unit X - Ensayo argumentativo')->get();
        $this->assertCount(count($this->paralelos), $unidades);
        $this->assertSame(1, $unidades->pluck('grupo_id')->unique()->count());
        $this->assertSame($periodo->id, $unidades->first()->periodo_lectivo_id);
        $this->assertSame($this->secretaria->id, $unidades->first()->creado_por);

        foreach ($unidades as $unidad) {
            $this->assertNotNull($unidad->clase_docente_id);
            $this->assertSame($this->materia->id, $unidad->materia_id);
            $this->assertEquals('2026-10-01', $unidad->fecha_inicio->format('Y-m-d'));
            $this->assertEquals('2026-10-15', $unidad->fecha_fin->format('Y-m-d'));
            $this->assertSame(15, $unidad->duracionDias());
        }
    }

    public function test_replica_no_incluye_paralelos_de_otro_curso(): void
    {
        $otroCurso = Curso::where('id', '!=', $this->curso->id)->first();
        $claseOtroCurso = ClaseDocente::create([
            'user_id' => $this->docente->id,
            'materia_id' => $this->materia->id,
            'curso_id' => $otroCurso->id,
            'paralelo_id' => $this->paralelo->id,
        ]);

        $response = $this->actingAs($this->secretaria)->post('/planificaciones', [
            'materia_id' => $this->materia->id,
            'curso_id' => $otroCurso->id,
            'paralelo_ids' => [$this->paralelo->id],
            'titulo' => 'Solo curso correcto',
            'descripcion' => 'Validación de réplica restringida al nivel.',
            'fecha_inicio' => '2026-10-01',
            'fecha_fin' => '2026-10-15',
            'quimestre' => 'Q1',
            'parcial' => 'P2',
        ]);

        $response->assertRedirect(route('planificaciones.index'));

        $this->assertSame(1, Unidad::where('titulo', 'Solo curso correcto')->count());
        $this->assertSame($claseOtroCurso->id, Unidad::where('titulo', 'Solo curso correcto')->first()->clase_docente_id);
    }

    public function test_requiere_sus_dates_no_planifies_con_fecha_fin_anterior(): void
    {
        $response = $this->actingAs($this->secretaria)->post('/planificaciones', [
            'materia_id' => $this->materia->id,
            'curso_id' => $this->curso->id,
            'paralelo_ids' => [$this->paralelo->id],
            'titulo' => 'Fecha inválida',
            'descripcion' => 'X',
            'fecha_inicio' => '2026-10-15',
            'fecha_fin' => '2026-10-01',
            'quimestre' => 'Q1',
            'parcial' => 'P1',
        ]);

        $response->assertSessionHasErrors('fecha_fin');
    }

    public function test_requiere_titulo_descripcion_y_paralelo(): void
    {
        $response = $this->actingAs($this->secretaria)->post('/planificaciones', [
            'materia_id' => $this->materia->id,
            'curso_id' => $this->curso->id,
            'paralelo_ids' => [],
            'titulo' => '',
            'descripcion' => '',
            'fecha_inicio' => '2026-10-01',
            'fecha_fin' => '2026-10-10',
            'quimestre' => 'Q1',
            'parcial' => 'P1',
        ]);

        $response->assertSessionHasErrors(['paralelo_ids', 'titulo', 'descripcion']);
    }

    public function test_listado_planificaciones_disponible_para_docente(): void
    {
        $response = $this->actingAs($this->docente)->get('/planificaciones');

        $response->assertOk()
            ->assertSee('Planificaciones de Unidad')
            ->assertSee('Nueva Planificación');
    }

    public function test_representante_no_accede_a_crear_planificaciones(): void
    {
        $representante = User::factory()->create(['rol' => 'REPRESENTANTE', 'institucion_id' => 1]);

        $this->actingAs($representante)->get('/planificaciones/crear')->assertForbidden();
    }
}