<?php

namespace Tests\Feature;

use App\Models\Alumno;
use App\Models\Cita;
use App\Models\ClaseDocente;
use App\Models\Curso;
use App\Models\Materia;
use App\Models\Matricula;
use App\Models\Observacion;
use App\Models\Paralelo;
use App\Models\PeriodoLectivo;
use App\Models\TipoObservacion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RepresentanteDashboardTest extends TestCase
{
    use RefreshDatabase;

    private function crearContexto(): array
    {
        $docente = User::factory()->docente()->create();
        $representante = User::factory()->representante()->create();
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
        $estudiante = User::factory()->estudiante()->create();
        $alumno->update(['user_id' => $estudiante->id, 'representante_user_id' => $representante->id]);

        Matricula::factory()->create([
            'alumno_id' => $alumno->id,
            'curso_id' => $curso->id,
            'paralelo_id' => $paralelo->id,
            'periodo_id' => $periodo->id,
        ]);

        $tipo = TipoObservacion::create([
            'codigo' => 'CONDUCTA',
            'nombre' => 'OBSERVACIÓN DE CONDUCTA',
        ]);

        $observacion = Observacion::create([
            'alumno_id' => $alumno->id,
            'tipo_observacion_id' => $tipo->id,
            'user_id' => $docente->id,
            'descripcion_texto' => 'Llegó tarde a la clase de hoy.',
            'notificar_representante' => true,
        ]);

        return compact('docente', 'representante', 'alumno', 'estudiante', 'clase', 'observacion');
    }

    public function test_representante_ve_observaciones_de_su_representado(): void
    {
        $ctx = $this->crearContexto();

        $this->actingAs($ctx['representante'])
            ->get('/observaciones')
            ->assertOk()
            ->assertSee('Llegó tarde a la clase de hoy.')
            ->assertSee('OBSERVACIÓN DE CONDUCTA')
            ->assertSee($ctx['alumno']->apellidos);
    }

    public function test_representante_ve_alerta_y_materiales_por_materia(): void
    {
        $ctx = $this->crearContexto();

        \App\Models\Material::create([
            'clase_docente_id' => $ctx['clase']->id,
            'titulo' => 'Material nuevo para el parcial',
            'nombre_archivo' => 'diapositivas.pdf',
            'url' => '/storage/materiales/1/diapositivas.pdf',
            'subido_por' => $ctx['docente']->id,
        ]);

        \App\Models\Material::create([
            'clase_docente_id' => $ctx['clase']->id,
            'titulo' => 'Material anterior',
            'nombre_archivo' => 'viejo.pdf',
            'url' => '/storage/materiales/1/viejo.pdf',
            'subido_por' => $ctx['docente']->id,
            'created_at' => now()->subDays(9),
        ]);

        $this->actingAs($ctx['representante'])
            ->get(route('representante.agendas'))
            ->assertOk()
            ->assertSee('Nuevos materiales subidos')
            ->assertSee('Material nuevo para el parcial')
            ->assertDontSee('tall;', false)
            ->assertSee('Matematica')
            ->assertSee('Materiales por materia')
            ->assertSee('Material anterior')
            ->assertSee('Descargar');
    }

    public function test_representante_ve_tarjeta_de_materiales_en_dashboard(): void
    {
        $ctx = $this->crearContexto();

        \App\Models\Material::create([
            'clase_docente_id' => $ctx['clase']->id,
            'titulo' => 'Guía completa',
            'nombre_archivo' => 'guia.pdf',
            'url' => '/storage/materiales/1/guia.pdf',
            'subido_por' => $ctx['docente']->id,
        ]);

        $this->actingAs($ctx['representante'])
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Materiales disponibles')
            ->assertSee('1', false);
    }

    public function test_representante_ve_tarjetas_de_materias_con_agendas_en_dashboard(): void
    {
        $ctx = $this->crearContexto();

        $agenda = \App\Models\Agenda::create([
            'clase_docente_id' => $ctx['clase']->id,
            'tipo_actividad_id' => \App\Models\TipoActividad::create(['codigo' => 'TAREA', 'nombre' => 'Tarea'])->id,
            'titulo_agenda' => 'Resolver ejercicios del capítulo 3',
            'fecha_inicio' => now()->subDay(),
            'fecha_entrega_limite' => now()->addDays(5),
            'enviar_a_todos' => true,
        ]);

        $this->actingAs($ctx['representante'])
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Matematica')
            ->assertSee('1', false)
            ->assertSee('hay-actividad', false);
    }

    public function test_representante_ve_agendas_con_adjuntos_en_su_pagina(): void
    {
        $ctx = $this->crearContexto();

        $tipo = \App\Models\TipoActividad::create(['codigo' => 'TAREA', 'nombre' => 'Tarea']);
        $agenda = \App\Models\Agenda::create([
            'clase_docente_id' => $ctx['clase']->id,
            'tipo_actividad_id' => $tipo->id,
            'titulo_agenda' => 'Taller de matemática',
            'fecha_inicio' => now()->subDay(),
            'fecha_entrega_limite' => now()->addDays(5),
            'enviar_a_todos' => true,
        ]);
        \App\Models\ArchivoAdjunto::create([
            'attachable_type' => \App\Models\Agenda::class,
            'attachable_id' => $agenda->id,
            'nombre' => 'taller.pdf',
            'url' => '/storage/archivos/taller.pdf',
        ]);

        $this->actingAs($ctx['representante'])
            ->get(route('representante.agendas'))
            ->assertOk()
            ->assertSee('Taller de matemática')
            ->assertSee('Matematica')
            ->assertSee('taller.pdf')
            ->assertDontSee('Enviar respuesta');
    }

    public function test_representante_no_ve_opciones_de_docente_en_el_sidebar(): void
    {
        $ctx = $this->crearContexto();

        $this->actingAs($ctx['representante'])
            ->get('/dashboard')
            ->assertOk()
            ->assertDontSee('Mis Clases')
            ->assertDontSee('Asistencia')
            ->assertDontSee('Cargar Notas')
            ->assertDontSee('Actas');
    }

    public function test_representante_no_accede_a_rutas_de_docente(): void
    {
        $ctx = $this->crearContexto();

        $this->actingAs($ctx['representante'])
            ->get(route('docente.asistencia.index'))
            ->assertForbidden();
    }

    public function test_representante_solicita_cita_y_queda_pendiente(): void
    {
        $ctx = $this->crearContexto();
        $fecha = now()->addDays(2)->format('Y-m-d');

        $this->actingAs($ctx['representante'])
            ->post(route('representante.citas.store'), [
                'alumno_id' => $ctx['alumno']->id,
                'docente_id' => $ctx['docente']->id,
                'motivo' => 'Rendimiento académico',
                'motivo_otro' => '',
                'fecha' => $fecha,
                'hora' => 9,
                'minuto' => 0,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('citas', [
            'solicitante_type' => User::class,
            'solicitante_id' => $ctx['representante']->id,
            'docente_id' => $ctx['docente']->id,
            'alumno_id' => $ctx['alumno']->id,
            'estado' => 'pendiente',
            'motivo' => 'Rendimiento académico',
        ]);
    }

    public function test_representante_solicita_cita_con_motivo_otro_y_texto(): void
    {
        $ctx = $this->crearContexto();
        $fecha = now()->addDays(3)->format('Y-m-d');

        $this->actingAs($ctx['representante'])
            ->post(route('representante.citas.store'), [
                'alumno_id' => $ctx['alumno']->id,
                'docente_id' => $ctx['docente']->id,
                'motivo' => 'Otro',
                'motivo_otro' => 'Quiero conocer las notas finales',
                'fecha' => $fecha,
                'hora' => 10,
                'minuto' => 30,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('citas', [
            'motivo' => 'Otro: Quiero conocer las notas finales',
        ]);
    }

    public function test_representante_no_puede_solicitar_cita_para_alumno_ajeno(): void
    {
        $ctx = $this->crearContexto();
        $otro = User::factory()->representante()->create();
        $fecha = now()->addDays(2)->format('Y-m-d');

        $this->actingAs($otro)
            ->post(route('representante.citas.store'), [
                'alumno_id' => $ctx['alumno']->id,
                'docente_id' => $ctx['docente']->id,
                'motivo' => 'Consulta',
                'fecha' => $fecha,
                'hora' => 9,
                'minuto' => 0,
            ])
            ->assertSessionHasErrors('alumno_id');

        $this->assertDatabaseMissing('citas', ['alumno_id' => $ctx['alumno']->id]);
    }

    public function test_representante_no_puede_solicitar_cita_con_docente_de_otro_curso(): void
    {
        $ctx = $this->crearContexto();
        $docenteOtro = User::factory()->docente()->create();
        $fecha = now()->addDays(2)->format('Y-m-d');

        $this->actingAs($ctx['representante'])
            ->post(route('representante.citas.store'), [
                'alumno_id' => $ctx['alumno']->id,
                'docente_id' => $docenteOtro->id,
                'motivo' => 'Consulta',
                'fecha' => $fecha,
                'hora' => 9,
                'minuto' => 0,
            ])
            ->assertSessionHasErrors('docente_id');

        $this->assertDatabaseMissing('citas', ['docente_id' => $docenteOtro->id]);
    }
}