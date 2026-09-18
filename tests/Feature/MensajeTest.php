<?php

namespace Tests\Feature;

use App\Models\Alumno;
use App\Models\ClaseDocente;
use App\Models\Curso;
use App\Models\Materia;
use App\Models\Matricula;
use App\Models\Mensaje;
use App\Models\Paralelo;
use App\Models\PeriodoLectivo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MensajeTest extends TestCase
{
    use RefreshDatabase;

    private function crearCtx(): array
    {
        $docente = User::factory()->docente()->create();
        $curso = Curso::factory()->create();
        $paralelo = Paralelo::factory()->create();
        $materia = Materia::create(['nombre' => 'Lengua', 'codigo' => 'LEN', 'color_tarjeta' => '#000000']);
        $periodo = PeriodoLectivo::factory()->create(['activo' => true]);

        ClaseDocente::create([
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

        return compact('docente', 'alumno', 'estudiante', 'representante');
    }

    public function test_docente_envia_mensaje_a_estudiante_con_contexto(): void
    {
        $ctx = $this->crearCtx();

        $this->actingAs($ctx['docente'])
            ->post(route('mensajes.store'), [
                'asunto' => 'Tarea 1',
                'cuerpo_contenido' => 'Recuerda la tarea de mañana.',
                'destinatario_id' => $ctx['estudiante']->id,
                'contexto_estudiante_id' => $ctx['alumno']->id,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('mensajes', [
            'destinatario_id' => $ctx['estudiante']->id,
            'contexto_estudiante_id' => $ctx['alumno']->id,
            'leido' => false,
        ]);
    }

    public function test_estudiante_ve_mensaje_y_se_marca_como_leido(): void
    {
        $ctx = $this->crearCtx();
        $mensaje = Mensaje::create([
            'asunto' => 'Bienvenida',
            'cuerpo_contenido' => 'Hola estudiante',
            'carpeta' => 'no_leidos',
            'emisor_id' => $ctx['docente']->id,
            'emisor_type' => User::class,
            'destinatario_id' => $ctx['estudiante']->id,
            'contexto_estudiante_id' => $ctx['alumno']->id,
            'leido' => false,
            'fecha_envio' => now(),
        ]);

        $this->actingAs($ctx['estudiante'])
            ->get(route('mensajes.index'))
            ->assertOk()
            ->assertSee('Bienvenida');

        $this->actingAs($ctx['estudiante'])
            ->get(route('mensajes.show', $mensaje->id))
            ->assertOk()
            ->assertSee('Hola estudiante');

        $this->assertDatabaseHas('mensajes', ['id' => $mensaje->id, 'leido' => true]);
    }

    public function test_representante_envia_mensaje_al_docente(): void
    {
        $ctx = $this->crearCtx();

        $this->actingAs($ctx['representante'])
            ->post(route('mensajes.store'), [
                'asunto' => 'Consulta',
                'cuerpo_contenido' => 'Quisiera agendar una reunión.',
                'destinatario_id' => $ctx['docente']->id,
                'contexto_estudiante_id' => $ctx['alumno']->id,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('mensajes', [
            'emisor_id' => $ctx['representante']->id,
            'destinatario_id' => $ctx['docente']->id,
            'contexto_estudiante_id' => $ctx['alumno']->id,
        ]);
    }

    public function test_estudiante_no_accede_a_mensaje_de_otro_destinatario(): void
    {
        $ctx = $this->crearCtx();
        $otro = User::factory()->estudiante()->create();

        $mensaje = Mensaje::create([
            'asunto' => 'Privado',
            'cuerpo_contenido' => 'Solo para otro estudiante',
            'carpeta' => 'no_leidos',
            'emisor_id' => $ctx['docente']->id,
            'emisor_type' => User::class,
            'destinatario_id' => $otro->id,
            'contexto_estudiante_id' => $ctx['alumno']->id,
            'leido' => false,
            'fecha_envio' => now(),
        ]);

        $this->actingAs($ctx['estudiante'])
            ->get(route('mensajes.show', $mensaje->id))
            ->assertForbidden();
    }
}