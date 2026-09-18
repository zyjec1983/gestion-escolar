<?php

namespace Tests\Feature;

use App\Models\Alumno;
use App\Models\ClaseDocente;
use App\Models\Curso;
use App\Models\Institucion;
use App\Models\Materia;
use App\Models\Matricula;
use App\Models\Nota;
use App\Models\Paralelo;
use App\Models\PeriodoLectivo;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotasQuimestreTest extends TestCase
{
    use RefreshDatabase;

    private function crearClase(User $docente): array
    {
        $curso = Curso::factory()->create();
        $paralelo = Paralelo::factory()->create();
        $materia = Materia::create(['nombre' => 'MatemÃ¡tica', 'codigo' => 'MAT', 'color_tarjeta' => '#000000']);
        $periodo = PeriodoLectivo::factory()->create(['activo' => true]);

        $clase = ClaseDocente::create([
            'user_id' => $docente->id,
            'materia_id' => $materia->id,
            'curso_id' => $curso->id,
            'paralelo_id' => $paralelo->id,
        ]);

        $alumno = Alumno::factory()->create();
        $matricula = Matricula::factory()->create([
            'alumno_id' => $alumno->id,
            'curso_id' => $curso->id,
            'paralelo_id' => $paralelo->id,
            'periodo_id' => $periodo->id,
        ]);

        return compact('clase', 'alumno', 'matricula');
    }

    public function test_admin_can_change_sistema_evaluacion(): void
    {
        Institucion::factory()->create(['sistema_evaluacion' => 'quimestre']);
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('admin.configuracion.sistema-evaluacion'), ['sistema_evaluacion' => 'trimestre'])
            ->assertRedirect();

        $this->assertDatabaseHas('instituciones', ['sistema_evaluacion' => 'trimestre']);
    }

    public function test_notas_index_shows_quimestre_options_and_examen_grid(): void
    {
        Institucion::factory()->create(['sistema_evaluacion' => 'quimestre']);
        $docente = User::factory()->docente()->create();
        $data = $this->crearClase($docente);

        $this->actingAs($docente)
            ->get(route('docente.notas.index', ['clase_docente_id' => $data['clase']->id, 'parcial' => 3]))
            ->assertOk()
            ->assertSee('1er Quimestre')
            ->assertSee('2do Quimestre')
            ->assertSee('Examen Quimestral')
            ->assertSee($data['alumno']->apellidos);
    }

    public function test_store_partial_and_examen_with_calculation(): void
    {
        Institucion::factory()->create(['sistema_evaluacion' => 'quimestre']);
        $docente = User::factory()->docente()->create();
        $data = $this->crearClase($docente);

        $payload = [
            'clase_docente_id' => $data['clase']->id,
            'parcial' => 1,
            'tipo_guardado' => 'final',
            'notas' => [
                $data['alumno']->id => [
                    'alumno_id' => $data['alumno']->id,
                    'fii' => 10, 'ffi' => 10, 'fig' => 10, 'ffg' => 10,
                    'proyecto_integrador' => 10, 'evaluacion_parcial' => 10,
                ],
            ],
        ];

        $this->actingAs($docente)->post(route('docente.notas.store'), $payload)->assertRedirect();

        $this->assertDatabaseHas('notas', [
            'matricula_id' => $data['matricula']->id,
            'clase_docente_id' => $data['clase']->id,
            'parcial' => 1,
            'tipo_nota' => 'parcial',
            'estado' => 'final',
        ]);

        $payload['parcial'] = 3;
        $payload['notas'][$data['alumno']->id] = ['alumno_id' => $data['alumno']->id, 'nota_examen' => 8.0];

        $this->actingAs($docente)->post(route('docente.notas.store'), $payload)->assertRedirect();

        $this->assertDatabaseHas('notas', [
            'matricula_id' => $data['matricula']->id,
            'clase_docente_id' => $data['clase']->id,
            'parcial' => 3,
            'tipo_nota' => 'examen',
            'estado' => 'final',
        ]);

        $examen = Nota::where('matricula_id', $data['matricula']->id)->where('parcial', 3)->first();
        $this->assertEquals(8.0, (float) $examen->nota_examen);
    }

    public function test_store_final_rejects_missing_fields(): void
    {
        Institucion::factory()->create(['sistema_evaluacion' => 'quimestre']);
        $docente = User::factory()->docente()->create();
        $data = $this->crearClase($docente);

        $payload = [
            'clase_docente_id' => $data['clase']->id,
            'parcial' => 1,
            'tipo_guardado' => 'final',
            'notas' => [
                $data['alumno']->id => ['alumno_id' => $data['alumno']->id, 'fii' => '', 'ffi' => '', 'fig' => '', 'ffg' => '', 'proyecto_integrador' => '', 'evaluacion_parcial' => ''],
            ],
        ];

        $this->actingAs($docente)
            ->post(route('docente.notas.store'), $payload)
            ->assertSessionHasErrors('notas');

        $this->assertDatabaseCount('notas', 0);
    }

    public function test_resumen_quimestral_matches_user_example(): void
    {
        Institucion::factory()->create(['sistema_evaluacion' => 'quimestre']);
        $docente = User::factory()->docente()->create();
        $data = $this->crearClase($docente);
        $aid = $data['alumno']->id;

        $fii = 10; $ffi = 10; $fig = 10; $ffg = 10;
        $prom10 = fn() => [
            'alumno_id' => $aid,
            'fii' => $fii, 'ffi' => $ffi, 'fig' => $fig, 'ffg' => $ffg,
            'proyecto_integrador' => 10, 'evaluacion_parcial' => 10,
        ];

        $this->actingAs($docente)->post(route('docente.notas.store'), [
            'clase_docente_id' => $data['clase']->id,
            'parcial' => 1,
            'tipo_guardado' => 'final',
            'notas' => [$aid => [...$prom10(), 'fii' => 7.67, 'ffi' => 7.67, 'fig' => 7.67, 'ffg' => 7.67, 'proyecto_integrador' => 7.67, 'evaluacion_parcial' => 7.67]],
        ])->assertRedirect();

        $this->actingAs($docente)->post(route('docente.notas.store'), [
            'clase_docente_id' => $data['clase']->id,
            'parcial' => 2,
            'tipo_guardado' => 'final',
            'notas' => [$aid => $prom10()],
        ])->assertRedirect();

        $this->actingAs($docente)->post(route('docente.notas.store'), [
            'clase_docente_id' => $data['clase']->id,
            'parcial' => 3,
            'tipo_guardado' => 'final',
            'notas' => [$aid => ['alumno_id' => $aid, 'nota_examen' => 8.0]],
        ])->assertRedirect();

        $this->actingAs($docente)
            ->get(route('docente.notas.index', ['clase_docente_id' => $data['clase']->id, 'parcial' => 1]))
            ->assertOk();

        $this->assertDatabaseHas('notas', ['matricula_id' => $data['matricula']->id, 'parcial' => 1]);
        $this->assertDatabaseHas('notas', ['matricula_id' => $data['matricula']->id, 'parcial' => 2]);
        $this->assertDatabaseHas('notas', ['matricula_id' => $data['matricula']->id, 'parcial' => 3, 'nota_examen' => '8.0000']);

        $q1 = app(\App\Http\Controllers\Docente\NotaController::class);
        $resumen = $this->invocarResumen($q1, $data, $docente);

        $this->assertNotNull($resumen[0]['p1']);
        $this->assertNotNull($resumen[0]['p2']);
        $this->assertNotNull($resumen[0]['te1']);
        $this->assertEqualsWithDelta(8.58, (float) $resumen[0]['q1'], 0.02);
    }

    public function test_acta_parcial_renders(): void
    {
        Institucion::factory()->create(['sistema_evaluacion' => 'quimestre']);
        $docente = User::factory()->docente()->create();
        $data = $this->crearClase($docente);

        $this->actingAs($docente)->post(route('docente.notas.store'), [
            'clase_docente_id' => $data['clase']->id,
            'parcial' => 1,
            'tipo_guardado' => 'final',
            'notas' => [
                $data['alumno']->id => [
                    'alumno_id' => $data['alumno']->id,
                    'fii' => 10, 'ffi' => 10, 'fig' => 10, 'ffg' => 10,
                    'proyecto_integrador' => 10, 'evaluacion_parcial' => 10,
                ],
            ],
        ])->assertRedirect();

        $this->actingAs($docente)
            ->get(route('docente.actas.index', ['clase_docente_id' => $data['clase']->id, 'tipo' => 'parcial', 'parcial' => 1]))
            ->assertOk()
            ->assertSee('Acta Parcial')
            ->assertSee('F. Docente')
            ->assertSee($data['alumno']->apellidos);
    }

    public function test_acta_quimestral_renders(): void
    {
        Institucion::factory()->create(['sistema_evaluacion' => 'quimestre']);
        $docente = User::factory()->docente()->create();
        $data = $this->crearClase($docente);

        $this->actingAs($docente)
            ->get(route('docente.actas.index', ['clase_docente_id' => $data['clase']->id, 'tipo' => 'quimestral']))
            ->assertOk()
            ->assertSee('Acta Quimestral')
            ->assertSee('Quimestre 1')
            ->assertSee('AÑO');
    }

    public function test_acta_excel_downloads(): void
    {
        Institucion::factory()->create(['sistema_evaluacion' => 'quimestre']);
        $docente = User::factory()->docente()->create();
        $data = $this->crearClase($docente);

        $this->actingAs($docente)
            ->get(route('docente.actas.excel', ['clase_docente_id' => $data['clase']->id, 'tipo' => 'parcial', 'parcial' => 1]))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_acta_pdf_downloads(): void
    {
        Institucion::factory()->create(['sistema_evaluacion' => 'quimestre']);
        $docente = User::factory()->docente()->create();
        $data = $this->crearClase($docente);

        Pdf::shouldReceive('loadView')->once()->andReturnSelf();
        Pdf::shouldReceive('setPaper')->once()->andReturnSelf();
        Pdf::shouldReceive('download')->once()->andReturn(response('pdf', 200));

        $this->actingAs($docente)
            ->get(route('docente.actas.pdf', ['clase_docente_id' => $data['clase']->id, 'tipo' => 'quimestral']))
            ->assertOk();
    }

    private function invocarResumen($controller, array $data, User $docente): array
    {
        $alumnos = collect([$data['alumno']]);
        $notasClase = \App\Models\Nota::where('clase_docente_id', $data['clase']->id)
            ->get()
            ->groupBy('alumno_id');
        $ref = new \ReflectionMethod($controller, 'resumenNotas');
        $ref->setAccessible(true);
        return $ref->invoke($controller, $alumnos, $notasClase, 'quimestre');
    }
}