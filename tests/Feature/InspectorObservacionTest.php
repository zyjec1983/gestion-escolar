<?php

namespace Tests\Feature;

use App\Models\Alumno;
use App\Models\ClaseDocente;
use App\Models\Curso;
use App\Models\Mensaje;
use App\Models\Matricula;
use App\Models\Materia;
use App\Models\Observacion;
use App\Models\Paralelo;
use App\Models\PeriodoLectivo;
use App\Models\TipoObservacion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\QueryException;
use Tests\TestCase;

class InspectorObservacionTest extends TestCase
{
    use RefreshDatabase;

    private User $inspector;

    private User $representante;

    private Curso $cursoA;

    private Curso $cursoB;

    private Paralelo $paraleloA1;

    private Paralelo $paraleloA2;

    private Paralelo $paraleloB1;

    private Alumno $alumnoA1;

    private Alumno $alumnoA2;

    private Alumno $alumnoB1;

    private TipoObservacion $conducta;

    protected function setUp(): void
    {
        parent::setUp();

        $this->inspector = User::factory()->create(['rol' => 'INSPECCION']);
        $this->representante = User::factory()->representante()->create();
        $otroRepresentante = User::factory()->representante()->create();

        $periodo = PeriodoLectivo::factory()->create();

        $this->cursoA = Curso::factory()->create(['nombre' => 'NOVENO GRADO', 'codigo' => 'NUEVO']);
        $this->cursoB = Curso::factory()->create(['nombre' => 'DECIMO GRADO', 'codigo' => 'DECI']);

        $this->paraleloA1 = Paralelo::factory()->create(['nombre' => 'A']);
        $this->paraleloA2 = Paralelo::factory()->create(['nombre' => 'B']);
        $this->paraleloB1 = Paralelo::factory()->create(['nombre' => 'A']);

        $this->alumnoA1 = Alumno::factory()->create([
            'apellidos' => 'Alvarez',
            'nombres' => 'Ana',
            'representante_user_id' => $this->representante->id,
        ]);
        $this->alumnoA2 = Alumno::factory()->create([
            'apellidos' => 'Betancourt',
            'nombres' => 'Luis',
            'representante_user_id' => $otroRepresentante->id,
        ]);
        $this->alumnoB1 = Alumno::factory()->create([
            'apellidos' => 'Cifuentes',
            'nombres' => 'Mara',
            'representante_user_id' => $otroRepresentante->id,
        ]);

        Matricula::factory()->create([
            'alumno_id' => $this->alumnoA1->id,
            'curso_id' => $this->cursoA->id,
            'paralelo_id' => $this->paraleloA1->id,
            'periodo_id' => $periodo->id,
        ]);
        Matricula::factory()->create([
            'alumno_id' => $this->alumnoA2->id,
            'curso_id' => $this->cursoA->id,
            'paralelo_id' => $this->paraleloA2->id,
            'periodo_id' => $periodo->id,
        ]);
        Matricula::factory()->create([
            'alumno_id' => $this->alumnoB1->id,
            'curso_id' => $this->cursoB->id,
            'paralelo_id' => $this->paraleloB1->id,
            'periodo_id' => $periodo->id,
        ]);

        $this->conducta = TipoObservacion::create([
            'codigo' => 'OBS_CONDUCTA',
            'nombre' => 'OBSERVACION DE CONDUCTA',
            'icono' => 'warning-red',
        ]);
    }

    private function claseDe(User $docente, int $cursoId, int $paraleloId, string $materia = 'Matematicas'): ClaseDocente
    {
        return ClaseDocente::create([
            'user_id' => $docente->id,
            'materia_id' => Materia::create(['nombre' => $materia])->id,
            'curso_id' => $cursoId,
            'paralelo_id' => $paraleloId,
        ]);
    }

    public function test_la_pagina_lista_todos_los_cursos(): void
    {
        $response = $this->actingAs($this->inspector)
            ->get(route('inspeccion.observaciones.index'));

        $response->assertOk()
            ->assertSee('NOVENO GRADO')
            ->assertSee('DECIMO GRADO')
            ->assertSee('Todos los cursos')
            ->assertSee('Todos los paralelos');
    }

    public function test_sin_filtro_lista_los_alumnos_de_todo_el_centro(): void
    {
        $response = $this->actingAs($this->inspector)
            ->get(route('inspeccion.observaciones.index'));

        $response->assertOk()
            ->assertSee('Alvarez Ana')
            ->assertSee('Betancourt Luis')
            ->assertSee('Cifuentes Mara');
    }

    public function test_filtrar_por_curso_deja_solo_sus_alumnos(): void
    {
        $response = $this->actingAs($this->inspector)
            ->get(route('inspeccion.observaciones.index', ['curso_id' => $this->cursoA->id]));

        $response->assertOk()
            ->assertSee('Alvarez Ana')
            ->assertSee('Betancourt Luis')
            ->assertDontSee('Cifuentes Mara');
    }

    public function test_filtrar_por_paralelo_acentua_el_listado(): void
    {
        $response = $this->actingAs($this->inspector)
            ->get(route('inspeccion.observaciones.index', [
                'curso_id' => $this->cursoA->id,
                'paralelo_id' => $this->paraleloA1->id,
            ]));

        $response->assertOk()
            ->assertSee('Alvarez Ana')
            ->assertDontSee('Betancourt Luis')
            ->assertDontSee('Cifuentes Mara');
    }

    public function test_al_seleccionar_el_estudiante_aparece_el_formulario(): void
    {
        $response = $this->actingAs($this->inspector)
            ->get(route('inspeccion.observaciones.index', [
                'curso_id' => $this->cursoA->id,
                'alumno_id' => $this->alumnoA1->id,
            ]));

        $response->assertOk()
            ->assertSee('Nueva Observación')
            ->assertSee('OBSERVACION DE CONDUCTA')
            ->assertSee('Imprimir historial');
    }

    public function test_el_inspector_registra_una_observacion_de_conducta(): void
    {
        $response = $this->actingAs($this->inspector)
            ->post(route('inspeccion.observaciones.store'), [
                'curso_id' => $this->cursoA->id,
                'alumno_id' => $this->alumnoA1->id,
                'tipo_observacion_id' => $this->conducta->id,
                'descripcion_texto' => 'disrupting la clase reiteradamente',
            ]);

        $response->assertRedirect(route('inspeccion.observaciones.index', [
            'curso_id' => $this->cursoA->id,
            'paralelo_id' => null,
            'alumno_id' => $this->alumnoA1->id,
        ]));

        $this->assertDatabaseHas('observaciones', [
            'alumno_id' => $this->alumnoA1->id,
            'tipo_observacion_id' => $this->conducta->id,
            'user_id' => $this->inspector->id,
            'descripcion_texto' => 'disrupting la clase reiteradamente',
            'notificar_representante' => true,
        ]);
    }

    public function test_el_representante_recibe_el_aviso_en_su_bandeja(): void
    {
        $this->actingAs($this->inspector)
            ->post(route('inspeccion.observaciones.store'), [
                'alumno_id' => $this->alumnoA1->id,
                'tipo_observacion_id' => $this->conducta->id,
                'descripcion_texto' => 'Conviene conversar en casa.',
            ]);

        $this->assertDatabaseHas('mensajes', [
            'destinatario_id' => $this->representante->id,
            'emisor_id' => $this->inspector->id,
            'emisor_type' => User::class,
            'contexto_estudiante_id' => $this->alumnoA1->id,
            'leido' => false,
        ]);

        $mensaje = Mensaje::where('destinatario_id', $this->representante->id)->firstOrFail();
        $this->assertStringContainsString('Ana Alvarez', $mensaje->asunto);
        $this->assertStringContainsString('Conviene conversar en casa.', $mensaje->cuerpo_contenido);
        $this->assertStringContainsString('OBSERVACION DE CONDUCTA', $mensaje->cuerpo_contenido);

        $this->actingAs($this->representante)
            ->get(route('mensajes.index'))
            ->assertOk()
            ->assertSee('Observación registrada');
    }

    public function test_el_aviso_marca_la_observacion_como_notificada_aunque_no_se_marque_la_casilla(): void
    {
        $this->actingAs($this->inspector)
            ->post(route('inspeccion.observaciones.store'), [
                'alumno_id' => $this->alumnoA1->id,
                'tipo_observacion_id' => $this->conducta->id,
                'descripcion_texto' => 'Sin casilla, pero se notifica igual.',
            ]);

        $observacion = Observacion::where('alumno_id', $this->alumnoA1->id)->firstOrFail();
        $this->assertTrue($observacion->notificar_representante);
    }

    public function test_la_observacion_queda_visible_en_el_portal_del_representante(): void
    {
        $this->actingAs($this->inspector)
            ->post(route('inspeccion.observaciones.store'), [
                'alumno_id' => $this->alumnoA1->id,
                'tipo_observacion_id' => $this->conducta->id,
                'descripcion_texto' => 'Buen comportamiento en la semana.',
            ]);

        $this->actingAs($this->representante)
            ->get(route('representante.observaciones'))
            ->assertOk()
            ->assertSee('Buen comportamiento en la semana.');
    }

    public function test_el_docente_no_puede_entrar_al_modulo_de_inspeccion(): void
    {
        $docente = User::factory()->docente()->create();

        $this->actingAs($docente)
            ->get(route('inspeccion.observaciones.index'))
            ->assertForbidden();

        $this->actingAs($docente)
            ->post(route('inspeccion.observaciones.store'), [
                'alumno_id' => $this->alumnoA1->id,
                'tipo_observacion_id' => $this->conducta->id,
                'descripcion_texto' => 'No deberia poder.',
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('observaciones', 0);
    }

    public function test_representante_estudiante_y_admin_no_pueden_entrar(): void
    {
        $roles = [
            User::factory()->representante()->create(),
            User::factory()->estudiante()->create(),
            User::factory()->admin()->create(),
        ];

        foreach ($roles as $user) {
            $this->actingAs($user)
                ->get(route('inspeccion.observaciones.index'))
                ->assertForbidden();
        }
    }

    public function test_las_directivas_tienen_acceso_por_si_no_hay_inspector(): void
    {
        foreach (['RECTORA', 'VICERRECTORA'] as $rol) {
            $directiva = User::factory()->create(['rol' => $rol]);

            $this->actingAs($directiva)
                ->get(route('inspeccion.observaciones.index'))
                ->assertOk()
                ->assertSee('NOVENO GRADO');
        }
    }

    public function test_la_impresion_muestra_el_historial_del_estudiante(): void
    {
        Observacion::create([
            'alumno_id' => $this->alumnoA1->id,
            'tipo_observacion_id' => $this->conducta->id,
            'user_id' => $this->inspector->id,
            'descripcion_texto' => 'Observacion que debe salir en el PDF.',
            'notificar_representante' => true,
        ]);

        $this->actingAs($this->inspector)
            ->get(route('inspeccion.observaciones.print', $this->alumnoA1->id))
            ->assertOk()
            ->assertSee('Historial de Observaciones')
            ->assertSee('Alvarez Ana')
            ->assertSee('Observacion que debe salir en el PDF.');
    }

    public function test_la_base_de_datos_no_permite_alumnos_sin_representante(): void
    {
        // Por regla ningun alumno puede quedar sin representante. La columna es
        // NOT NULL, asi que ni el seeder ni un bug pueden dejarlos huerfanos:
        // cualquier observers siempre tiene a quien avisar.
        $this->expectException(QueryException::class);

        Alumno::factory()->create([
            'apellidos' => 'SinRepresentante',
            'nombres' => 'Alumno',
            'representante_user_id' => null,
        ]);
    }

    public function test_el_docente_no_puede_registrar_sobre_un_alumno_de_otra_clase(): void
    {
        $docente = User::factory()->docente()->create();
        $clasePropia = $this->claseDe($docente, $this->cursoA->id, $this->paraleloA1->id);

        $this->actingAs($docente)
            ->post(route('docente.observaciones.store'), [
                'clase_docente_id' => $clasePropia->id,
                'alumno_id' => $this->alumnoB1->id,
                'tipo_observacion_id' => $this->conducta->id,
                'descripcion_texto' => 'Intento sobre un alumno de otra clase.',
            ])
            ->assertSessionHasErrors('alumno_id');

        $this->assertDatabaseCount('observaciones', 0);
        $this->assertDatabaseCount('mensajes', 0);
    }

    public function test_el_docente_sigue_podiendo_registrar_en_cualquiera_de_sus_clases(): void
    {
        $docente = User::factory()->docente()->create();
        $claseMateria = $this->claseDe($docente, $this->cursoA->id, $this->paraleloA1->id);
        $claseMatematicas = $this->claseDe($docente, $this->cursoA->id, $this->paraleloA2->id);

        foreach ([$this->alumnoA1->id => $claseMateria->id, $this->alumnoA2->id => $claseMatematicas->id] as $alumnoId => $claseId) {
            $this->actingAs($docente)
                ->post(route('docente.observaciones.store'), [
                    'clase_docente_id' => $claseId,
                    'alumno_id' => $alumnoId,
                    'tipo_observacion_id' => $this->conducta->id,
                    'descripcion_texto' => 'Observacion legitima.',
                ])
                ->assertSessionHasNoErrors();
        }

        $this->assertDatabaseCount('observaciones', 2);
    }

    public function test_un_docente_sin_clases_no_puede_registrar(): void
    {
        $docente = User::factory()->docente()->create();

        $this->actingAs($docente)
            ->post(route('docente.observaciones.store'), [
                'alumno_id' => $this->alumnoA1->id,
                'tipo_observacion_id' => $this->conducta->id,
                'descripcion_texto' => 'Sin clases asignadas.',
            ])
            ->assertSessionHasErrors('alumno_id');

        $this->assertDatabaseCount('observaciones', 0);
    }

    public function test_el_dece_puede_registrar_y_revisar_observaciones(): void
    {
        $dece = User::factory()->create(['rol' => 'DECE']);

        $this->actingAs($dece)
            ->get(route('inspeccion.observaciones.index'))
            ->assertOk()
            ->assertSee('Alvarez Ana');

        $this->actingAs($dece)
            ->post(route('inspeccion.observaciones.store'), [
                'alumno_id' => $this->alumnoB1->id,
                'tipo_observacion_id' => $this->conducta->id,
                'descripcion_texto' => 'Revision disciplinaria del DECE.',
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('observaciones', [
            'alumno_id' => $this->alumnoB1->id,
            'user_id' => $dece->id,
            'notificar_representante' => true,
        ]);
    }

    public function test_el_docente_sigue_registrando_observaciones_sin_aviso_si_no_lo_marca(): void
    {
        $docente = User::factory()->docente()->create();

        $this->claseDe($docente, $this->cursoA->id, $this->paraleloA1->id);

        $this->actingAs($docente)
            ->post(route('docente.observaciones.store'), [
                'clase_docente_id' => ClaseDocente::first()->id,
                'alumno_id' => $this->alumnoA1->id,
                'tipo_observacion_id' => $this->conducta->id,
                'descripcion_texto' => 'Observacion del docente.',
            ])
            ->assertRedirect();

        $observacion = Observacion::where('alumno_id', $this->alumnoA1->id)->firstOrFail();
        $this->assertFalse($observacion->notificar_representante);
        $this->assertDatabaseCount('mensajes', 0);
    }
}