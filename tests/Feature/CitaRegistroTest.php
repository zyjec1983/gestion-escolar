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

class CitaRegistroTest extends TestCase
{
    use RefreshDatabase;

    private function crearCitaVencida(?Carbon $fecha = null): array
    {
        $docente = User::factory()->docente()->create();
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
            'solicitante_id' => $representante->id,
            'docente_id' => $docente->id,
            'alumno_id' => $alumno->id,
            'fecha_inicio' => $fecha ?? Carbon::yesterday()->setTime(10, 0),
            'fecha_fin' => Carbon::yesterday()->setTime(11, 0),
            'estado' => 'pendiente',
            'motivo' => 'Bajo rendimiento académico',
        ]);

        return compact('docente', 'representante', 'alumno', 'cita', 'curso', 'paralelo');
    }

    public function test_cita_vencida_no_cuenta_como_pendiente_y_aparece_por_registrar(): void
    {
        $ctx = $this->crearCitaVencida();

        $response = $this->actingAs($ctx['docente'])
            ->get(route('docente.citas.index'));

        $response->assertOk()
            ->assertSee('Citas vencidas por registrar')
            ->assertSee('Por registrar')
            ->assertSee('No tienes solicitudes de cita pendientes.');
    }

    public function test_cita_futura_no_aparece_como_por_registrar(): void
    {
        $ctx = $this->crearCitaVencida(Carbon::tomorrow()->setTime(10, 0));

        $response = $this->actingAs($ctx['docente'])
            ->get(route('docente.citas.index'));

        $response->assertOk()
            ->assertSee('No hay citas vencidas pendientes de registro.')
            ->assertDontSee('No tienes solicitudes de cita pendientes.');
    }

    public function test_docente_registra_asistencia_con_dudas_y_evidencia(): void
    {
        $ctx = $this->crearCitaVencida();

        $response = $this->actingAs($ctx['docente'])
            ->post(route('docente.citas.registrar', $ctx['cita']->id), [
                'resultado' => 'asistio',
                'dudas_aclaradas' => '1',
                'evidencia' => 'El padre asistió a la cita. Se explicaron los avances académicos y los acuerdos para mejorar.',
            ]);

        $response->assertRedirect()
            ->assertSessionHas('success');

        $cita = $ctx['cita']->fresh();
        $this->assertSame('realizada', $cita->estado);
        $this->assertSame('asistio', $cita->resultado);
        $this->assertTrue($cita->dudas_aclaradas);
        $this->assertStringContainsString('acuerdos para mejorar', $cita->evidencia);
        $this->assertSame($ctx['docente']->id, $cita->registrada_por);
        $this->assertNotNull($cita->registrada_en);
    }

    public function test_docente_registra_no_asistencia_sin_dudas(): void
    {
        $ctx = $this->crearCitaVencida();

        $response = $this->actingAs($ctx['docente'])
            ->post(route('docente.citas.registrar', $ctx['cita']->id), [
                'resultado' => 'no_asistio',
                'evidencia' => 'El representante no se presentó a la cita agendada y tampoco avisó.',
            ]);

        $response->assertRedirect()
            ->assertSessionHas('success');

        $cita = $ctx['cita']->fresh();
        $this->assertSame('realizada', $cita->estado);
        $this->assertSame('no_asistio', $cita->resultado);
        $this->assertNull($cita->dudas_aclaradas);
    }

    public function test_no_se_puede_registrar_cita_antes_de_la_hora(): void
    {
        $ctx = $this->crearCitaVencida(Carbon::now()->addDays(3)->setTime(10, 0));

        $this->actingAs($ctx['docente'])
            ->from(route('docente.citas.index'))
            ->post(route('docente.citas.registrar', $ctx['cita']->id), [
                'resultado' => 'asistio',
                'dudas_aclaradas' => '1',
                'evidencia' => 'Se adelantó el registro de una cita que aún no se realiza.',
            ])
            ->assertStatus(422);

        $this->assertNull($ctx['cita']->fresh()->resultado);
    }

    public function test_evidencia_corta_produce_error_de_validacion(): void
    {
        $ctx = $this->crearCitaVencida();

        $this->actingAs($ctx['docente'])
            ->post(route('docente.citas.registrar', $ctx['cita']->id), [
                'resultado' => 'asistio',
                'evidencia' => 'corto',
            ])
            ->assertSessionHasErrors('evidencia');
    }

    public function test_no_se_puede_re_registrar_una_cita_ya_realizada(): void
    {
        $ctx = $this->crearCitaVencida();
        $ctx['cita']->update([
            'resultado' => 'asistio',
            'evidencia' => 'Registro ya guardado con evidencia completa.',
            'estado' => 'realizada',
        ]);

        $this->actingAs($ctx['docente'])
            ->post(route('docente.citas.registrar', $ctx['cita']->id), [
                'resultado' => 'no_asistio',
                'evidencia' => 'Intento de modificar un registro ya existente.',
            ])
            ->assertStatus(409);

        $this->assertSame('asistio', $ctx['cita']->fresh()->resultado);
    }

    public function test_representante_no_puede_registrar_y_docente_ajeno_no_ve_la_cita(): void
    {
        $ctx = $this->crearCitaVencida();
        $otroDocente = User::factory()->docente()->create();
        $otroRepresentante = User::factory()->representante()->create();

        $this->actingAs($otroRepresentante)
            ->post(route('docente.citas.registrar', $ctx['cita']->id), [
                'resultado' => 'asistio',
                'evidencia' => 'Un representante intentando registrar una cita ajena.',
            ])
            ->assertForbidden();

        $this->actingAs($otroDocente)
            ->post(route('docente.citas.registrar', $ctx['cita']->id), [
                'resultado' => 'asistio',
                'evidencia' => 'Un docente ajeno intentando registrar una cita.',
            ])
            ->assertNotFound();

        $this->assertNull($ctx['cita']->fresh()->resultado);
    }

    public function test_directivo_ve_historial_con_reincidente(): void
    {
        $ctx = $this->crearCitaVencida();

        Cita::create([
            'solicitante_type' => User::class,
            'solicitante_id' => $ctx['representante']->id,
            'docente_id' => $ctx['docente']->id,
            'alumno_id' => $ctx['alumno']->id,
            'fecha_inicio' => Carbon::parse('-20 days')->setTime(9, 0),
            'fecha_fin' => Carbon::parse('-20 days')->setTime(10, 0),
            'estado' => 'realizada',
            'motivo' => 'Problemas de conducta',
            'resultado' => 'no_asistio',
            'evidencia' => 'El padre no asistió a la reunión de conducta.',
            'registrada_por' => $ctx['docente']->id,
            'registrada_en' => now(),
        ]);

        Cita::create([
            'solicitante_type' => User::class,
            'solicitante_id' => $ctx['representante']->id,
            'docente_id' => $ctx['docente']->id,
            'alumno_id' => $ctx['alumno']->id,
            'fecha_inicio' => Carbon::parse('-40 days')->setTime(14, 0),
            'fecha_fin' => Carbon::parse('-40 days')->setTime(15, 0),
            'estado' => 'pendiente',
            'motivo' => 'Inasistencias',
            'resultado' => null,
        ]);

        $directivo = User::factory()->create(['rol' => 'SECRETARIA']);

        // La URL histórica /directivo/citas redirige al módulo compartido.
        $this->actingAs($directivo)
            ->get(route('directivo.citas.historial'))
            ->assertRedirect(route('inspeccion.citas.historial'));

        $response = $this->actingAs($directivo)
            ->get(route('inspeccion.citas.historial', [
                'nivel' => $ctx['curso']->id,
            ]));

        $response->assertOk()
            ->assertSee('Historial de citas')
            ->assertSee(trim($ctx['alumno']->apellidos.' '.$ctx['alumno']->nombres))
            ->assertSee('Reincidente');
    }

    public function test_docente_y_representante_no_acceden_al_historial_directivo(): void
    {
        $docente = User::factory()->docente()->create();
        $representante = User::factory()->representante()->create();

        foreach ([$docente, $representante] as $user) {
            $this->actingAs($user)->get(route('directivo.citas.historial'))->assertForbidden();
            $this->actingAs($user)->get(route('inspeccion.citas.historial'))->assertForbidden();
            $this->actingAs($user)->get(route('inspeccion.citas.agendar'))->assertForbidden();
        }
    }
}
