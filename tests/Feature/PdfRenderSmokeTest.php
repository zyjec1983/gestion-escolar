<?php

namespace Tests\Feature;

use App\Models\Alumno;
use App\Models\ClaseDocente;
use App\Models\Curso;
use App\Models\Institucion;
use App\Models\Materia;
use App\Models\Matricula;
use App\Models\Paralelo;
use App\Models\PeriodoLectivo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PdfRenderSmokeTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function real_dompdf_renders_parcial_and_quimestral()
    {
        Institucion::factory()->create(['sistema_evaluacion' => 'quimestre']);
        $docente = User::factory()->docente()->create();
        $curso = Curso::factory()->create();
        $paralelo = Paralelo::factory()->create();
        $materia = Materia::create(['nombre' => 'Matemática', 'codigo' => 'MAT', 'color_tarjeta' => '#000000']);
        PeriodoLectivo::factory()->create(['activo' => true]);
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
            'periodo_id' => PeriodoLectivo::first()->id,
        ]);

        $this->actingAs($docente)
            ->get(route('docente.actas.pdf', ['clase_docente_id' => $clase->id, 'tipo' => 'parcial', 'parcial' => 1]))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->actingAs($docente)
            ->get(route('docente.actas.pdf', ['clase_docente_id' => $clase->id, 'tipo' => 'quimestral']))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
    }
}