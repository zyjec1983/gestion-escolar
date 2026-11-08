<?php

namespace Tests\Feature;

use App\Models\Alumno;
use App\Models\Curso;
use App\Models\Matricula;
use App\Models\Observacion;
use App\Models\Paralelo;
use App\Models\PeriodoLectivo;
use App\Models\TipoObservacion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ObservacionReporteAlumnoGraficosTest extends TestCase
{
    use RefreshDatabase;

    private User $inspector;

    private Alumno $alumno;

    private Curso $curso;

    protected function setUp(): void
    {
        parent::setUp();

        $this->inspector = User::factory()->create(['rol' => 'INSPECCION']);
        $representante = User::factory()->representante()->create();
        $periodo = PeriodoLectivo::factory()->create();
        $this->curso = Curso::factory()->create(['nombre' => 'NOVENO GRADO', 'codigo' => 'NUEVO']);
        $paralelo = Paralelo::factory()->create(['nombre' => 'A']);

        $this->alumno = Alumno::factory()->create([
            'apellidos' => 'Alvarez',
            'nombres' => 'Ana',
            'representante_user_id' => $representante->id,
        ]);

        Matricula::factory()->create([
            'alumno_id' => $this->alumno->id,
            'curso_id' => $this->curso->id,
            'paralelo_id' => $paralelo->id,
            'periodo_id' => $periodo->id,
        ]);
    }

    public function test_reporte_alumno_muestra_resumen_y_graficos(): void
    {
        $positiva = TipoObservacion::create(['codigo' => 'OBS_POS', 'nombre' => 'FELICITACION', 'clasificacion' => 'positiva']);
        $leve = TipoObservacion::create(['codigo' => 'OBS_LEVE', 'nombre' => 'INASISTENCIA LEVE', 'clasificacion' => 'leve']);

        Observacion::create([
            'alumno_id' => $this->alumno->id,
            'tipo_observacion_id' => $positiva->id,
            'user_id' => $this->inspector->id,
            'descripcion_texto' => 'Buena participacion en clase.',
        ]);
        Observacion::create([
            'alumno_id' => $this->alumno->id,
            'tipo_observacion_id' => $leve->id,
            'user_id' => $this->inspector->id,
            'descripcion_texto' => 'Llego tarde a la clase.',
        ]);

        $this->actingAs($this->inspector)
            ->get(route('inspeccion.observaciones.reporte.alumno', [
                'nivel' => $this->curso->id,
                'alumno' => $this->alumno->id,
            ]))
            ->assertOk()
            ->assertSee('Hoja de vida')
            ->assertSee('chartHojaVida')
            ->assertSee('Positivas')
            ->assertSee('FELICITACION: 1')
            ->assertSee('INASISTENCIA LEVE: 1');
    }

    public function test_reporte_alumno_limpio_muestra_mensaje_de_hoja_de_vida(): void
    {
        $this->actingAs($this->inspector)
            ->get(route('inspeccion.observaciones.reporte.alumno', [
                'nivel' => $this->curso->id,
                'alumno' => $this->alumno->id,
            ]))
            ->assertOk()
            ->assertSee('Hoja de vida')
            ->assertSee('No existen registros de observaciones para este estudiante.')
            ->assertDontSee('chartHojaVida');
    }
}
