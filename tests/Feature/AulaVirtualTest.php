<?php

namespace Tests\Feature;

use App\Models\Agenda;
use App\Models\Alumno;
use App\Models\AsignacionGrupo;
use App\Models\ClaseDocente;
use App\Models\Curso;
use App\Models\Materia;
use App\Models\Material;
use App\Models\Matricula;
use App\Models\Paralelo;
use App\Models\PeriodoLectivo;
use App\Models\TipoActividad;
use App\Models\Unidad;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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
            ->get('/clases/'.$ctx['clase']->id)
            ->assertOk()
            ->assertSee($ctx['alumno']->apellidos);
    }

    public function test_docente_no_accede_a_clase_de_otro_docente(): void
    {
        $otro = User::factory()->docente()->create();
        $ctx = $this->crearContextoClase($otro);
        $docente = User::factory()->docente()->create();

        $this->actingAs($docente)
            ->get('/clases/'.$ctx['clase']->id)
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
            ->get('/clases/'.$ctx['clase']->id)
            ->assertOk()
            ->assertSee($ctx['clase']->materia->nombre);
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
            ->get('/clases/'.$claseOtro->id)
            ->assertForbidden();
    }

    public function test_representante_accede_a_las_clases_de_sus_representados(): void
    {
        $ctx = $this->crearContextoClase(User::factory()->docente()->create());

        $this->actingAs($ctx['representante'])
            ->get('/clases')
            ->assertForbidden();

        $this->actingAs($ctx['representante'])
            ->get('/clases/'.$ctx['clase']->id)
            ->assertForbidden();
    }

    public function test_docente_ve_agenda_y_crea_actividad_con_unidad(): void
    {
        $ctx = $this->crearContextoClase(User::factory()->docente()->create());
        $clase = $ctx['clase'];

        $unidad = Unidad::create([
            'materia_id' => $clase->materia_id,
            'titulo' => 'Unidad 1 - Conjuntos',
            'fecha_inicio' => today()->subDays(5),
            'fecha_fin' => today()->addDays(10),
            'quimestre' => 1,
            'parcial' => 1,
        ]);

        TipoActividad::create(['codigo' => 'TAREA', 'nombre' => 'Tarea']);

        $this->actingAs($ctx['docente'])
            ->get(route('clases.agenda', $clase->id))
            ->assertOk()
            ->assertSee('Unidad 1 - Conjuntos');

        $this->actingAs($ctx['docente'])
            ->post(route('clases.agenda.store', $clase->id), [
                'titulo_agenda' => 'Resolver página 12',
                'tipo_actividad_id' => 'TAREA',
                'unidad_id' => $unidad->id,
                'fecha_inicio' => now()->format('Y-m-d\TH:i'),
                'fecha_entrega_limite' => now()->addDays(2)->format('Y-m-d\TH:i'),
                'descripcion_instrucciones_html' => 'Revisar los ejemplos antes de resolver.',
                'alumnos' => [$ctx['alumno']->id],
            ])
            ->assertRedirect(route('clases.agenda', $clase->id));

        $this->assertDatabaseHas('agendas', [
            'clase_docente_id' => $clase->id,
            'titulo_agenda' => 'Resolver página 12',
            'unidad_id' => $unidad->id,
        ]);

        $agenda = Agenda::where('clase_docente_id', $clase->id)->firstOrFail();
        $this->assertDatabaseHas('agenda_alumnos', [
            'agenda_id' => $agenda->id,
            'alumno_id' => $ctx['alumno']->id,
        ]);

        $this->actingAs($ctx['docente'])
            ->get(route('clases.agenda', $clase->id))
            ->assertSee('Resolver página 12')
            ->assertSee('Unidad 1 - Conjuntos');
    }

    public function test_docente_edita_actividad_y_actualiza_destinatarios(): void
    {
        $ctx = $this->crearContextoClase(User::factory()->docente()->create());
        $clase = $ctx['clase'];

        $alumno2 = Alumno::factory()->create();
        Matricula::factory()->create([
            'alumno_id' => $alumno2->id,
            'curso_id' => $clase->curso_id,
            'paralelo_id' => $clase->paralelo_id,
            'periodo_id' => PeriodoLectivo::factory()->create(['activo' => true])->id,
        ]);

        $tipo = TipoActividad::create(['codigo' => 'TAREA', 'nombre' => 'Tarea']);

        $agenda = Agenda::create([
            'clase_docente_id' => $clase->id,
            'tipo_actividad_id' => $tipo->id,
            'titulo_agenda' => 'Título original',
            'fecha_inicio' => now(),
        ]);
        $agenda->alumnos()->sync([$ctx['alumno']->id]);

        $this->actingAs($ctx['docente'])
            ->get(route('clases.agenda.edit', [$clase->id, $agenda->id]))
            ->assertOk()
            ->assertJsonPath('id', $agenda->id)
            ->assertJsonPath('alumnos', [$ctx['alumno']->id]);

        $this->actingAs($ctx['docente'])
            ->post(route('clases.agenda.store', $clase->id), [
                'agenda_id' => $agenda->id,
                'titulo_agenda' => 'Título editado',
                'tipo_actividad_id' => 'TAREA',
                'fecha_inicio' => now()->addDay()->format('Y-m-d\TH:i'),
                'alumnos' => [$alumno2->id],
            ])
            ->assertRedirect(route('clases.agenda', $clase->id));

        $this->assertDatabaseHas('agendas', [
            'id' => $agenda->id,
            'titulo_agenda' => 'Título editado',
        ]);
        $this->assertDatabaseMissing('agenda_alumnos', ['agenda_id' => $agenda->id, 'alumno_id' => $ctx['alumno']->id]);
        $this->assertDatabaseHas('agenda_alumnos', ['agenda_id' => $agenda->id, 'alumno_id' => $alumno2->id]);
    }

    public function test_estudiante_ve_agenda_pero_no_puede_crear_o_eliminar_actividades(): void
    {
        $ctx = $this->crearContextoClase(User::factory()->docente()->create());
        $clase = $ctx['clase'];

        TipoActividad::create(['codigo' => 'TAREA', 'nombre' => 'Tarea']);

        $this->actingAs($ctx['estudiante'])
            ->get(route('clases.agenda', $clase->id))
            ->assertOk();

        $this->actingAs($ctx['estudiante'])
            ->post(route('clases.agenda.store', $clase->id), [
                'titulo_agenda' => 'Intento no autorizado',
                'tipo_actividad_id' => 'TAREA',
                'fecha_inicio' => now()->format('Y-m-d\TH:i'),
            ])
            ->assertForbidden();
    }

    public function test_docente_no_modifica_el_aula_de_otro_docente(): void
    {
        $otro = User::factory()->docente()->create();
        $ctx = $this->crearContextoClase($otro);
        $intruso = User::factory()->docente()->create();

        $this->actingAs($intruso)
            ->get(route('clases.agenda', $ctx['clase']->id))
            ->assertForbidden();

        $this->actingAs($intruso)
            ->post(route('clases.agenda.store', $ctx['clase']->id), [
                'titulo_agenda' => 'Intrusión',
                'tipo_actividad_id' => 'TAREA',
                'fecha_inicio' => now()->format('Y-m-d\TH:i'),
            ])
            ->assertForbidden();

        $this->actingAs($intruso)
            ->post(route('clases.materiales.store', $ctx['clase']->id), [
                'titulo' => 'Intrusión',
                'archivo' => UploadedFile::fake()->create('x.pdf', 10),
            ])
            ->assertForbidden();
    }

    public function test_representante_ve_materiales_de_su_representado_solo_lectura(): void
    {
        Storage::fake('public');
        $ctx = $this->crearContextoClase(User::factory()->docente()->create());
        $clase = $ctx['clase'];

        $this->actingAs($ctx['docente'])
            ->post(route('clases.materiales.store', $clase->id), [
                'titulo' => 'Guía de repaso',
                'archivo' => UploadedFile::fake()->create('repaso.pdf', 10),
            ])
            ->assertRedirect();

        $this->actingAs($ctx['representante'])
            ->get(route('clases.materiales', $clase->id))
            ->assertOk()
            ->assertSee('Guía de repaso')
            ->assertDontSee('Subir material');

        $this->actingAs($ctx['representante'])
            ->post(route('clases.materiales.store', $clase->id), [
                'titulo' => 'Intento no autorizado',
                'archivo' => UploadedFile::fake()->create('x.pdf', 10),
            ])
            ->assertForbidden();
    }

    public function test_estudiante_ve_y_descarga_materiales_de_su_clase(): void
    {
        Storage::fake('public');
        $ctx = $this->crearContextoClase(User::factory()->docente()->create());
        $clase = $ctx['clase'];

        $this->actingAs($ctx['docente'])
            ->post(route('clases.materiales.store', $clase->id), [
                'titulo' => 'Libro capítulo 1',
                'archivo' => UploadedFile::fake()->create('libro.pdf', 100),
            ])
            ->assertRedirect();

        $this->actingAs($ctx['estudiante'])
            ->get(route('clases.materiales', $clase->id))
            ->assertOk()
            ->assertSee('Libro capítulo 1')
            ->assertSee('libro.pdf')
            ->assertSee('/storage/materiales/', false)
            ->assertDontSee('Subir material');

        $this->actingAs($ctx['estudiante'])
            ->post(route('clases.materiales.store', $clase->id), [
                'titulo' => 'Intento no autorizado',
                'archivo' => UploadedFile::fake()->create('x.pdf', 10),
            ])
            ->assertForbidden();
    }

    public function test_docente_sube_ve_y_elimina_material(): void
    {
        Storage::fake('public');
        $ctx = $this->crearContextoClase(User::factory()->docente()->create());
        $clase = $ctx['clase'];

        $this->actingAs($ctx['docente'])
            ->get(route('clases.materiales', $clase->id))
            ->assertOk();

        $this->actingAs($ctx['docente'])
            ->post(route('clases.materiales.store', $clase->id), [
                'titulo' => 'Guía de ejercicios',
                'descripcion' => 'Práctica del capítulo 3',
                'archivo' => UploadedFile::fake()->create('guia.pdf', 100),
            ])
            ->assertRedirect(route('clases.materiales', $clase->id));

        $this->assertDatabaseHas('materiales', [
            'clase_docente_id' => $clase->id,
            'titulo' => 'Guía de ejercicios',
        ]);

        $material = Material::where('clase_docente_id', $clase->id)->firstOrFail();

        $this->actingAs($ctx['docente'])
            ->get(route('clases.materiales', $clase->id))
            ->assertSee('Guía de ejercicios')
            ->assertSee('guia.pdf');

        $this->actingAs($ctx['docente'])
            ->delete(route('clases.materiales.destroy', [$clase->id, $material->id]))
            ->assertRedirect(route('clases.materiales', $clase->id));

        $this->assertDatabaseMissing('materiales', ['id' => $material->id]);
    }

    private function matricularAlumnosAdicionales(ClaseDocente $clase, int $cantidad): void
    {
        $periodo = PeriodoLectivo::factory()->create(['activo' => true]);

        for ($i = 0; $i < $cantidad; $i++) {
            $alumno = Alumno::factory()->create();
            Matricula::factory()->create([
                'alumno_id' => $alumno->id,
                'curso_id' => $clase->curso_id,
                'paralelo_id' => $clase->paralelo_id,
                'periodo_id' => $periodo->id,
            ]);
        }
    }

    private function conteoPorGrupo(int $claseId): array
    {
        $asignacion = AsignacionGrupo::where('clase_docente_id', $claseId)->firstOrFail();
        $porGrupo = $asignacion->integrantes()->get()->groupBy('grupo')->map->count()->sort()->values()->all();

        return $porGrupo;
    }

    public function test_docente_genera_grupos_por_numero_de_grupos(): void
    {
        $ctx = $this->crearContextoClase(User::factory()->docente()->create());
        $clase = $ctx['clase'];
        $this->matricularAlumnosAdicionales($clase, 24);

        $this->actingAs($ctx['docente'])
            ->get(route('clases.grupos', $clase->id))
            ->assertOk()
            ->assertSee('Generar nueva distribución');

        $this->actingAs($ctx['docente'])
            ->post(route('clases.grupos.store', $clase->id), [
                'modo' => 'grupos',
                'numero_grupos' => 4,
            ])
            ->assertRedirect(route('clases.grupos', $clase->id));

        $this->assertSame([6, 6, 6, 7], $this->conteoPorGrupo($clase->id));

        $this->actingAs($ctx['docente'])
            ->get(route('clases.grupos', $clase->id))
            ->assertSee('Grupo 1')
            ->assertSee('grupos')
            ->assertSee('Descargar JPG');
    }

    public function test_docente_genera_grupos_por_integrantes_por_grupo(): void
    {
        $ctx = $this->crearContextoClase(User::factory()->docente()->create());
        $clase = $ctx['clase'];
        $this->matricularAlumnosAdicionales($clase, 9);

        $this->actingAs($ctx['docente'])
            ->post(route('clases.grupos.store', $clase->id), [
                'modo' => 'tamano',
                'integrantes_por_grupo' => 3,
            ])
            ->assertRedirect(route('clases.grupos', $clase->id));

        $this->assertSame([2, 2, 3, 3], $this->conteoPorGrupo($clase->id));
    }

    public function test_nueva_distribucion_reemplaza_a_la_anterior_y_se_elimina(): void
    {
        $ctx = $this->crearContextoClase(User::factory()->docente()->create());
        $clase = $ctx['clase'];
        $this->matricularAlumnosAdicionales($clase, 5);

        $this->actingAs($ctx['docente'])
            ->post(route('clases.grupos.store', $clase->id), ['modo' => 'grupos', 'numero_grupos' => 2])
            ->assertRedirect();

        $this->assertSame(1, AsignacionGrupo::where('clase_docente_id', $clase->id)->count());

        $this->actingAs($ctx['docente'])
            ->post(route('clases.grupos.store', $clase->id), ['modo' => 'grupos', 'numero_grupos' => 3])
            ->assertRedirect();

        $this->assertSame(1, AsignacionGrupo::where('clase_docente_id', $clase->id)->count());
        $this->assertSame([2, 2, 2], $this->conteoPorGrupo($clase->id));

        $this->actingAs($ctx['docente'])
            ->delete(route('clases.grupos.destroy', $clase->id))
            ->assertRedirect(route('clases.grupos', $clase->id));

        $this->assertSame(0, AsignacionGrupo::where('clase_docente_id', $clase->id)->count());
        $this->assertDatabaseMissing('asignacion_grupo_alumnos', ['asignacion_id' => null]);
    }

    public function test_estudiante_y_representante_ven_grupos_pero_no_los_modifican(): void
    {
        $ctx = $this->crearContextoClase(User::factory()->docente()->create());
        $clase = $ctx['clase'];

        $this->actingAs($ctx['docente'])
            ->post(route('clases.grupos.store', $clase->id), ['modo' => 'grupos', 'numero_grupos' => 1])
            ->assertRedirect();

        $this->actingAs($ctx['estudiante'])
            ->get(route('clases.grupos', $clase->id))
            ->assertOk()
            ->assertSee('Grupo 1')
            ->assertSee($ctx['alumno']->apellidos)
            ->assertDontSee('Generar nueva distribución');

        $this->actingAs($ctx['estudiante'])
            ->post(route('clases.grupos.store', $clase->id), ['modo' => 'grupos', 'numero_grupos' => 1])
            ->assertForbidden();

        $this->actingAs($ctx['representante'])
            ->get(route('clases.grupos', $clase->id))
            ->assertOk()
            ->assertSee('Grupo 1');

        $this->actingAs($ctx['representante'])
            ->delete(route('clases.grupos.destroy', $clase->id))
            ->assertForbidden();
    }

    public function test_pagina_de_grupos_incluye_descarga_jpg_del_cliente(): void
    {
        $ctx = $this->crearContextoClase(User::factory()->docente()->create());
        $clase = $ctx['clase'];

        $this->actingAs($ctx['docente'])
            ->get(route('clases.grupos', $clase->id))
            ->assertOk()
            ->assertDontSee('id="btnDescargarJpg"');

        $this->actingAs($ctx['docente'])
            ->post(route('clases.grupos.store', $clase->id), ['modo' => 'grupos', 'numero_grupos' => 1])
            ->assertRedirect();

        $this->actingAs($ctx['estudiante'])
            ->get(route('clases.grupos', $clase->id))
            ->assertOk()
            ->assertSee('html2canvas')
            ->assertSee('id="btnDescargarJpg"', false)
            ->assertSee('contenedor-grupos', false);
    }

    public function test_docente_de_otra_clase_no_toca_los_grupos(): void
    {
        $ctx = $this->crearContextoClase(User::factory()->docente()->create());
        $intruso = User::factory()->docente()->create();

        $this->actingAs($intruso)
            ->get(route('clases.grupos', $ctx['clase']->id))
            ->assertForbidden();

        $this->actingAs($intruso)
            ->post(route('clases.grupos.store', $ctx['clase']->id), ['modo' => 'grupos', 'numero_grupos' => 1])
            ->assertForbidden();
    }
}
