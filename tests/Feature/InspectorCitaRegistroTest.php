<?php

namespace Tests\Feature;

use App\Models\Alumno;
use App\Models\Cita;
use App\Models\Curso;
use App\Models\Matricula;
use App\Models\Paralelo;
use App\Models\PeriodoLectivo;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InspectorCitaRegistroTest extends TestCase
{
    use RefreshDatabase;

    private function crearCitaDeInspector(?Carbon $fecha = null): array
    {
        $inspector = User::factory()->create(['rol' => 'INSPECCION']);
        $representante = User::factory()->representante()->create();
        $alumno = Alumno::factory()->create([
            'representante_user_id' => $representante->id,
        ]);

        $periodo = PeriodoLectivo::factory()->create();
        $curso = Curso::factory()->create();
        $paralelo = Paralelo::factory()->create();
        Matricula::factory()->create([
            'alumno_id' => $alumno->id,
            'curso_id' => $curso->id,
            'paralelo_id' => $paralelo->id,
            'periodo_id' => $periodo->id,
        ]);

        $cita = Cita::create([
            'solicitante_type' => User::class,
            'solicitante_id' => $inspector->id,
            'docente_id' => $inspector->id,
            'alumno_id' => $alumno->id,
            'fecha_inicio' => $fecha ?? Carbon::yesterday()->setTime(10, 0),
            'fecha_fin' => Carbon::yesterday()->setTime(11, 0),
            'estado' => 'pendiente',
            'motivo' => 'Bajo rendimiento académico',
        ]);

        return compact('inspector', 'representante', 'alumno', 'cita', 'curso', 'paralelo');
    }

    public function test_inspector_registra_resultado_de_su_propia_cita(): void
    {
        $ctx = $this->crearCitaDeInspector();

        $response = $this->actingAs($ctx['inspector'])
            ->post(route('inspeccion.citas.registrar', $ctx['cita']->id), [
                'resultado' => 'asistio',
                'dudas_aclaradas' => '1',
                'evidencia' => 'El representante asistió a la cita y se aclararon todas sus dudas.',
            ]);

        $response->assertRedirect()->assertSessionHas('success');

        $cita = $ctx['cita']->fresh();
        $this->assertSame('realizada', $cita->estado);
        $this->assertSame('asistio', $cita->resultado);
        $this->assertTrue($cita->dudas_aclaradas);
        $this->assertSame($ctx['inspector']->id, $cita->registrada_por);
        $this->assertNotNull($cita->registrada_en);
    }

    public function test_inspector_registra_no_asistencia_cuando_el_representante_no_llega(): void
    {
        $ctx = $this->crearCitaDeInspector();

        $this->actingAs($ctx['inspector'])
            ->post(route('inspeccion.citas.registrar', $ctx['cita']->id), [
                'resultado' => 'no_asistio',
                'evidencia' => 'El representante no se presentó a la cita ni justificó su inasistencia.',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $cita = $ctx['cita']->fresh();
        $this->assertSame('no_asistio', $cita->resultado);
        $this->assertNull($cita->dudas_aclaradas);
    }

    public function test_inspector_no_puede_registrar_cita_agendada_por_otro(): void
    {
        $ctx = $this->crearCitaDeInspector();
        $otroInspector = User::factory()->create(['rol' => 'INSPECCION']);

        $this->actingAs($otroInspector)
            ->post(route('inspeccion.citas.registrar', $ctx['cita']->id), [
                'resultado' => 'asistio',
                'evidencia' => 'Intento de registrar una cita que no le corresponde.',
            ])
            ->assertNotFound();

        $this->assertNull($ctx['cita']->fresh()->resultado);
    }

    public function test_inspector_no_puede_registrar_antes_de_la_hora(): void
    {
        $ctx = $this->crearCitaDeInspector(Carbon::now()->addDays(3)->setTime(10, 0));

        $this->actingAs($ctx['inspector'])
            ->from(route('inspeccion.citas.historial'))
            ->post(route('inspeccion.citas.registrar', $ctx['cita']->id), [
                'resultado' => 'asistio',
                'dudas_aclaradas' => '1',
                'evidencia' => 'Se adelantó el registro de una cita que aún no se realiza.',
            ])
            ->assertStatus(422);

        $this->assertNull($ctx['cita']->fresh()->resultado);
    }

    public function test_inspector_no_puede_re_registrar_una_cita_ya_realizada(): void
    {
        $ctx = $this->crearCitaDeInspector();
        $ctx['cita']->update([
            'resultado' => 'asistio',
            'evidencia' => 'Registro ya guardado con evidencia completa.',
            'registrada_por' => $ctx['inspector']->id,
            'registrada_en' => now(),
            'estado' => 'realizada',
        ]);

        $this->actingAs($ctx['inspector'])
            ->post(route('inspeccion.citas.registrar', $ctx['cita']->id), [
                'resultado' => 'no_asistio',
                'evidencia' => 'Intento de modificar un registro ya existente.',
            ])
            ->assertStatus(409);

        $this->assertSame('asistio', $ctx['cita']->fresh()->resultado);
    }

    public function test_historial_del_inspector_muestra_boton_registrar(): void
    {
        $ctx = $this->crearCitaDeInspector();

        $response = $this->actingAs($ctx['inspector'])->get(route('inspeccion.citas.historial', [
            'alumno' => $ctx['alumno']->id,
        ]));

        $response->assertOk()
            ->assertSee('Historial personal')
            ->assertSee('Registrar');
    }

    public function test_historial_no_muestra_estudiantes_sin_filtros(): void
    {
        $ctx = $this->crearCitaDeInspector();

        $response = $this->actingAs($ctx['inspector'])->get(route('inspeccion.citas.historial'));

        $response->assertOk()
            ->assertSee('Seleccione un nivel o paralelo para ver los estudiantes.');
    }

    public function test_historial_muestra_estudiantes_al_seleccionar_filtro(): void
    {
        $ctx = $this->crearCitaDeInspector();

        $response = $this->actingAs($ctx['inspector'])->get(route('inspeccion.citas.historial', [
            'nivel' => $ctx['curso']->id,
        ]));

        $response->assertOk()
            ->assertDontSee('Seleccione un nivel o paralelo para ver los estudiantes.')
            ->assertSee('1 estudiantes');
    }
}
