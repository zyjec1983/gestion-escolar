<?php

namespace Tests\Feature;

use App\Models\Alumno;
use App\Models\Asistencia;
use App\Models\Curso;
use App\Models\Matricula;
use App\Models\Paralelo;
use App\Models\PeriodoLectivo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InspectorAsistenciaEstadisticasTest extends TestCase
{
    use RefreshDatabase;

    private function crearAula(): array
    {
        $curso = Curso::create(['nombre' => 'OCTAVO A']);
        $paralelo = Paralelo::create(['nombre' => 'A']);
        $periodo = PeriodoLectivo::create([
            'nombre' => '2026',
            'fecha_inicio' => now()->startOfYear()->toDateString(),
            'fecha_fin' => now()->endOfYear()->toDateString(),
            'activo' => true,
        ]);
        $alumno = Alumno::factory()->create([
            'nombres' => 'Juan',
            'apellidos' => 'Pérez',
        ]);
        Matricula::create([
            'alumno_id' => $alumno->id,
            'curso_id' => $curso->id,
            'paralelo_id' => $paralelo->id,
            'periodo_id' => $periodo->id,
        ]);

        return [$curso, $paralelo, $alumno];
    }

    public function test_estadisticas_requiere_rol_permitido(): void
    {
        $this->actingAs(User::factory()->create(['rol' => 'DOCENTE']))
            ->get(route('inspeccion.asistencia.estadisticas'))
            ->assertForbidden();
    }

    public function test_estadisticas_muestra_conteos_por_alumno(): void
    {
        [$curso, $paralelo, $alumno] = $this->crearAula();

        $estados = ['presente', 'presente', 'injustificada', 'atraso'];
        foreach ($estados as $i => $estado) {
            Asistencia::create([
                'curso_id' => $curso->id,
                'paralelo_id' => $paralelo->id,
                'alumno_id' => $alumno->id,
                'fecha' => now()->subDays($i)->toDateString(),
                'estado' => $estado,
                'registrado_por' => User::factory()->create(['rol' => 'INSPECCION'])->id,
            ]);
        }

        $this->actingAs(User::factory()->create(['rol' => 'INSPECCION']))
            ->get(route('inspeccion.asistencia.estadisticas', [
                'curso_id' => $curso->id,
                'paralelo_id' => $paralelo->id,
            ]))
            ->assertOk()
            ->assertSee('Estadísticas de Asistencia')
            ->assertSee('Pérez')
            ->assertSee('2')   // presentes
            ->assertSee('1')   // faltas injustificadas
            ->assertSee('1');  // atrasos
    }

    public function test_estadisticas_renderiza_sin_aula(): void
    {
        $this->actingAs(User::factory()->create(['rol' => 'RECTORA']))
            ->get(route('inspeccion.asistencia.estadisticas'))
            ->assertOk()
            ->assertSee('Estadísticas de Asistencia');
    }
}
