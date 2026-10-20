<?php

namespace Tests\Feature;

use App\Models\Alumno;
use App\Models\Asistencia;
use App\Models\ClaseDocente;
use App\Models\Curso;
use App\Models\Materia;
use App\Models\Matricula;
use App\Models\Paralelo;
use App\Models\PeriodoLectivo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AsistenciaFechaRegresionTest extends TestCase
{
    use RefreshDatabase;

    private function crearAula(): array
    {
        $curso = Curso::create(['nombre' => 'NOVENO B']);
        $paralelo = Paralelo::create(['nombre' => 'B']);
        $periodo = PeriodoLectivo::create([
            'nombre' => '2026',
            'fecha_inicio' => now()->startOfYear()->toDateString(),
            'fecha_fin' => now()->endOfYear()->toDateString(),
            'activo' => true,
        ]);
        $alumno = Alumno::factory()->create();
        Matricula::create([
            'alumno_id' => $alumno->id,
            'curso_id' => $curso->id,
            'paralelo_id' => $paralelo->id,
            'periodo_id' => $periodo->id,
        ]);

        return [$curso, $paralelo, $alumno];
    }

    public function test_inspector_reguardar_el_mismo_dia_actualiza_sin_fallar(): void
    {
        [$curso, $paralelo, $alumno] = $this->crearAula();
        $inspector = User::factory()->create(['rol' => 'INSPECCION']);
        $fecha = now()->toDateString();

        $payload = fn (string $estado) => [
            'curso_id' => $curso->id,
            'paralelo_id' => $paralelo->id,
            'fecha' => $fecha,
            'asistencia' => [
                ['alumno_id' => $alumno->id, 'estado' => $estado, 'observacion' => null],
            ],
        ];

        // Registro inicial.
        $this->actingAs($inspector)
            ->post(route('inspeccion.asistencia.store'), $payload('presente'))
            ->assertSessionHas('success');

        // Re-guardar el mismo dia no debe lanzar UniqueConstraintViolationException.
        $this->actingAs($inspector)
            ->post(route('inspeccion.asistencia.store'), $payload('atraso'))
            ->assertSessionHas('success');

        $this->assertSame(1, Asistencia::where('alumno_id', $alumno->id)->count());
        $this->assertSame('atraso', Asistencia::where('alumno_id', $alumno->id)->first()->estado);
    }

    public function test_index_precarga_asistencia_existente_del_dia(): void
    {
        [$curso, $paralelo, $alumno] = $this->crearAula();
        $inspector = User::factory()->create(['rol' => 'INSPECCION']);
        $fecha = now()->toDateString();

        Asistencia::create([
            'curso_id' => $curso->id,
            'paralelo_id' => $paralelo->id,
            'alumno_id' => $alumno->id,
            'fecha' => $fecha,
            'estado' => 'atraso',
            'registrado_por' => $inspector->id,
        ]);

        // El index debe precargar el estado existente (el radio atraso marcado).
        $this->actingAs($inspector)
            ->get(route('inspeccion.asistencia.index', [
                'curso_id' => $curso->id,
                'paralelo_id' => $paralelo->id,
                'fecha' => $fecha,
            ]))
            ->assertOk()
            ->assertSee('value="atraso" checked', false);
    }

    public function test_docente_reguardar_el_mismo_dia_es_bloqueado_no_falla(): void
    {
        [$curso, $paralelo, $alumno] = $this->crearAula();
        $docente = User::factory()->docente()->create();
        $clase = ClaseDocente::create([
            'user_id' => $docente->id,
            'curso_id' => $curso->id,
            'paralelo_id' => $paralelo->id,
            'materia_id' => Materia::create(['nombre' => 'Matemática'])->id,
        ]);
        $fecha = now()->toDateString();

        Asistencia::create([
            'curso_id' => $curso->id,
            'paralelo_id' => $paralelo->id,
            'alumno_id' => $alumno->id,
            'fecha' => $fecha,
            'estado' => 'presente',
            'registrado_por' => $docente->id,
        ]);

        // Reintento del docente debe ser bloqueado con mensaje, no con excepcion.
        $this->actingAs($docente)
            ->post(route('docente.asistencia.store'), [
                'clase_docente_id' => $clase->id,
                'fecha' => $fecha,
                'asistencia' => [
                    ['alumno_id' => $alumno->id, 'estado' => 'atraso', 'observacion' => null],
                ],
            ])
            ->assertSessionHas('error');

        $this->assertSame(1, Asistencia::where('alumno_id', $alumno->id)->count());
    }
}
