<?php

namespace Tests\Feature;

use App\Models\Alumno;
use App\Models\ClaseDocente;
use App\Models\Curso;
use App\Models\Materia;
use App\Models\Matricula;
use App\Models\Paralelo;
use App\Models\PeriodoLectivo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AulaVirtualTest extends TestCase
{
    use RefreshDatabase;

    private function crearContextoClase(User $docente): array
    {
        $curso = Curso::factory()->create();
        $paralelo = Paralelo::factory()->create();
        $materia = Materia::create(['nombre' => 'Matematica', 'codigo' => 'MAT', 'color_tarjeta' => '#000000']);
        $periodo = PeriodoLectivo::factory()->create(['activo' => true]);

        $clase = ClaseDocente::create([
            'user_id' => $docente->id,
            'materia_id' => $materia->id,
            'curso_id' => $curso->id,
            'paralelo_id' => $paralelo->id,
        ]);

        $alumno = Alumno::factory()->create();
        Matricula::factory()->create([
            'alumno_id' => $alumno->id,
            'curso_id' => $curso->id,
            'paralelo_id' => $paralelo->id,
            'periodo_id' => $periodo->id,
        ]);

        $estudiante = User::factory()->estudiante()->create();
        $representante = User::factory()->representante()->create();
        $alumno->update(['user_id' => $estudiante->id, 'representante_user_id' => $representante->id]);

        return compact('clase', 'alumno', 'estudiante', 'representante', 'docente');
    }

    public function test_docente_accede_a_la_lista_y_a_su_clase(): void
    {
        $ctx = $this->crearContextoClase(User::factory()->docente()->create());

        $this->actingAs($ctx['docente'])
            ->get('/clases')
            ->assertOk()
            ->assertSee($ctx['clase']->materia->nombre);

        $this->actingAs($ctx['docente'])
            ->get('/clases/' . $ctx['clase']->id)
            ->assertOk()
            ->assertSee($ctx['alumno']->apellidos);
    }

    public function test_docente_no_accede_a_clase_de_otro_docente(): void
    {
        $otro = User::factory()->docente()->create();
        $ctx = $this->crearContextoClase($otro);
        $docente = User::factory()->docente()->create();

        $this->actingAs($docente)
            ->get('/clases/' . $ctx['clase']->id)
            ->assertForbidden();
    }

    public function test_estudiante_accede_a_sus_clases_por_matricula(): void
    {
        $ctx = $this->crearContextoClase(User::factory()->docente()->create());

        $this->actingAs($ctx['estudiante'])
            ->get('/clases')
            ->assertOk()
            ->assertSee($ctx['clase']->materia->nombre);

        $this->actingAs($ctx['estudiante'])
            ->get('/clases/' . $ctx['clase']->id)
            ->assertOk()
            ->assertSee($ctx['alumno']->apellidos);
    }

    public function test_estudiante_no_accede_a_clase_de_otro_paralelo(): void
    {
        $ctx = $this->crearContextoClase(User::factory()->docente()->create());

        $cursoOtro = Curso::factory()->create();
        $paraleloOtro = Paralelo::factory()->create();
        $materiaOtro = Materia::create(['nombre' => 'Ciencias', 'codigo' => 'CCN', 'color_tarjeta' => '#000000']);

        $claseOtro = ClaseDocente::create([
            'user_id' => $ctx['docente']->id,
            'materia_id' => $materiaOtro->id,
            'curso_id' => $cursoOtro->id,
            'paralelo_id' => $paraleloOtro->id,
        ]);

        $this->actingAs($ctx['estudiante'])
            ->get('/clases/' . $claseOtro->id)
            ->assertForbidden();
    }

    public function test_representante_accede_a_las_clases_de_sus_representados(): void
    {
        $ctx = $this->crearContextoClase(User::factory()->docente()->create());

        $this->actingAs($ctx['representante'])
            ->get('/clases')
            ->assertOk()
            ->assertSee($ctx['clase']->materia->nombre);

        $this->actingAs($ctx['representante'])
            ->get('/clases/' . $ctx['clase']->id)
            ->assertOk();
    }
}