<?php

namespace Tests\Feature;

use App\Models\Agenda;
use App\Models\Alumno;
use App\Models\ClaseDocente;
use App\Models\Curso;
use App\Models\Foro;
use App\Models\Material;
use App\Models\Materia;
use App\Models\Matricula;
use App\Models\Paralelo;
use App\Models\PeriodoLectivo;
use App\Models\TipoActividad;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EstudianteDashboardTest extends TestCase
{
    use RefreshDatabase;

    private function crearContexto(): array
    {
        $docente = User::factory()->docente()->create();
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
        $alumno->update(['user_id' => $estudiante->id]);

        Matricula::factory()->create([
            'alumno_id' => $alumno->id,
            'curso_id' => $curso->id,
            'paralelo_id' => $paralelo->id,
            'periodo_id' => $periodo->id,
        ]);

        return compact('docente', 'alumno', 'estudiante', 'clase');
    }

    public function test_estudiante_ve_tarjetas_del_dashboard_con_sus_stats(): void
    {
        $ctx = $this->crearContexto();

        Material::create([
            'clase_docente_id' => $ctx['clase']->id,
            'titulo' => 'Guía completa',
            'nombre_archivo' => 'guia.pdf',
            'url' => '/storage/materiales/1/guia.pdf',
            'subido_por' => $ctx['docente']->id,
        ]);

        $tipo = TipoActividad::create(['codigo' => 'TAREA', 'nombre' => 'Tarea']);
        Agenda::create([
            'clase_docente_id' => $ctx['clase']->id,
            'tipo_actividad_id' => $tipo->id,
            'titulo_agenda' => 'Resolver ejercicios del capítulo 3',
            'fecha_inicio' => now()->subDay(),
            'fecha_entrega_limite' => now()->addDays(5),
            'enviar_a_todos' => true,
        ]);

        Foro::create([
            'clase_docente_id' => $ctx['clase']->id,
            'titulo' => 'Debate sobre la lectura',
            'pregunta' => '¿Qué opinas?',
            'links' => [],
            'fecha_inicio' => now()->subDay(),
            'fecha_cierre' => now()->addDays(3),
        ]);

        $this->actingAs($ctx['estudiante'])
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Mi Espacio')
            ->assertSee('Mensajes sin leer')
            ->assertSee('Materiales disponibles')
            ->assertSee('Tareas pendientes')
            ->assertSee('Foros activos')
            ->assertSee('Matematica')
            ->assertSee('hay-actividad', false);
    }

    public function test_estudiante_ve_alerta_materiales_y_agendas_por_materia(): void
    {
        $ctx = $this->crearContexto();

        Material::create([
            'clase_docente_id' => $ctx['clase']->id,
            'titulo' => 'Material nuevo para el parcial',
            'nombre_archivo' => 'diapositivas.pdf',
            'url' => '/storage/materiales/1/diapositivas.pdf',
            'subido_por' => $ctx['docente']->id,
        ]);

        Material::create([
            'clase_docente_id' => $ctx['clase']->id,
            'titulo' => 'Material anterior',
            'nombre_archivo' => 'viejo.pdf',
            'url' => '/storage/materiales/1/viejo.pdf',
            'subido_por' => $ctx['docente']->id,
            'created_at' => now()->subDays(9),
        ]);

        $this->actingAs($ctx['estudiante'])
            ->get(route('estudiante.agendas'))
            ->assertOk()
            ->assertSee('Nuevos materiales subidos')
            ->assertSee('Material nuevo para el parcial')
            ->assertSee('Materiales por materia')
            ->assertSee('Material anterior')
            ->assertSee('Descargar')
            ->assertSee('No hay agendas enviadas para este filtro.');
    }

    public function test_estudiante_no_accede_a_rutas_del_representante(): void
    {
        $ctx = $this->crearContexto();

        $this->actingAs($ctx['estudiante'])
            ->get('/agendas')
            ->assertForbidden();
    }

    public function test_estudiante_solo_envia_mensajes_a_sus_docentes(): void
    {
        $ctx = $this->crearContexto();

        $this->actingAs($ctx['estudiante'])
            ->post('/mensajes', [
                'asunto' => 'Consulta',
                'cuerpo_contenido' => 'Hola, profesor.',
                'destinatario_id' => $ctx['docente']->id,
            ])
            ->assertRedirect(route('mensajes.index'))
            ->assertSessionHas('exito');

        $ajeno = User::factory()->docente()->create();

        $this->actingAs($ctx['estudiante'])
            ->post('/mensajes', [
                'asunto' => 'Consulta',
                'cuerpo_contenido' => 'Hola.',
                'destinatario_id' => $ajeno->id,
            ])
            ->assertForbidden();
    }
}