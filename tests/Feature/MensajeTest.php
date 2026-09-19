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

    public function test_docente_ve_compositor_por_curso_con_representante(): void
    {
        $ctx = $this->crearCtx();
        $alumno = $ctx['alumno'];

        $respuesta = $this->actingAs($ctx['docente'])
            ->get(route('mensajes.index'))
            ->assertOk()
            ->assertSee('Curso / Nivel')
            ->assertSee('Representante legal');

        $compositor = $respuesta->viewData('compositor');
        $this->assertEquals('DOCENTE', $compositor['rol']);
        $this->assertCount(1, $compositor['clases']);
        $this->assertCount(1, $compositor['clases'][0]['estudiantes']);

        $estudiante = $compositor['clases'][0]['estudiantes'][0];
        $this->assertEquals($alumno->id, $estudiante['id']);
        $this->assertEquals($ctx['estudiante']->id, $estudiante['estudiante_user_id']);
        $this->assertEquals($ctx['representante']->id, $estudiante['representante_id']);
        $this->assertStringContainsString($ctx['representante']->name, $estudiante['representante_nombre']);
    }

    public function test_representante_ve_compositor_por_materia_con_docente(): void
    {
        $ctx = $this->crearCtx();

        $respuesta = $this->actingAs($ctx['representante'])
            ->get(route('mensajes.index'))
            ->assertOk()
            ->assertSee('Materia')
            ->assertSee('Docente');

        $compositor = $respuesta->viewData('compositor');
        $this->assertNotEquals('DOCENTE', $compositor['rol']);
        $this->assertCount(1, $compositor['materias']);
        $this->assertEquals('Lengua', $compositor['materias'][0]['nombre']);
        $this->assertCount(1, $compositor['materias'][0]['docentes']);
        $this->assertEquals($ctx['docente']->id, $compositor['materias'][0]['docentes'][0]['id']);
        $this->assertEquals($ctx['alumno']->id, $compositor['materias'][0]['docentes'][0]['contexto_estudiante_id']);
    }

    public function test_docente_elimina_hilo_de_su_bandeja_y_el_representante_conserva_el_envio(): void
    {
        $ctx = $this->crearCtx();
        $mensaje = Mensaje::create([
            'asunto' => 'Tarea de investigación',
            'cuerpo_contenido' => 'Entrega el viernes.',
            'carpeta' => 'enviados',
            'emisor_id' => $ctx['docente']->id,
            'emisor_type' => User::class,
            'destinatario_id' => $ctx['representante']->id,
            'contexto_estudiante_id' => $ctx['alumno']->id,
            'leido' => false,
            'fecha_envio' => now(),
        ]);

        $this->actingAs($ctx['docente'])
            ->delete(route('mensajes.destroy'), ['ids' => [$mensaje->id], 'carpeta' => 'enviados'])
            ->assertRedirect();

        $this->assertDatabaseHas('mensajes', ['id' => $mensaje->id, 'borrado_emisor' => true, 'borrado_destinatario' => false]);

        $this->actingAs($ctx['docente'])
            ->get(route('mensajes.index'))
            ->assertDontSee('Tarea de investigación');

        $this->actingAs($ctx['representante'])
            ->get(route('mensajes.index'))
            ->assertOk()
            ->assertSee('Tarea de investigación');
    }

    public function test_representante_elimina_de_su_lado_y_el_docente_mantiene_su_envio(): void
    {
        $ctx = $this->crearCtx();
        $mensaje = Mensaje::create([
            'asunto' => 'Consulte la agenda',
            'cuerpo_contenido' => 'Revisen el diario.',
            'carpeta' => 'no_leidos',
            'emisor_id' => $ctx['docente']->id,
            'emisor_type' => User::class,
            'destinatario_id' => $ctx['representante']->id,
            'contexto_estudiante_id' => $ctx['alumno']->id,
            'leido' => false,
            'fecha_envio' => now(),
        ]);

        $this->actingAs($ctx['representante'])
            ->delete(route('mensajes.destroy'), ['ids' => [$mensaje->id]])
            ->assertRedirect();

        $this->assertDatabaseHas('mensajes', [
            'id' => $mensaje->id,
            'borrado_emisor' => false,
            'borrado_destinatario' => true,
        ]);

        $this->actingAs($ctx['representante'])
            ->get(route('mensajes.index'))
            ->assertDontSee('Consulte la agenda');

        $this->actingAs($ctx['docente'])
            ->get(route('mensajes.index'))
            ->assertOk()
            ->assertSee('Consulte la agenda');
    }

    public function test_conversacion_marca_como_leido_y_devuelve_el_hilo(): void
    {
        $ctx = $this->crearCtx();
        $mensaje = Mensaje::create([
            'asunto' => 'Bienvenida 2',
            'cuerpo_contenido' => 'Hola estudiante desde el panel.',
            'carpeta' => 'no_leidos',
            'emisor_id' => $ctx['docente']->id,
            'emisor_type' => User::class,
            'destinatario_id' => $ctx['estudiante']->id,
            'contexto_estudiante_id' => $ctx['alumno']->id,
            'leido' => false,
            'fecha_envio' => now(),
        ]);

        $this->actingAs($ctx['estudiante'])
            ->get(route('mensajes.conversacion', $mensaje->id))
            ->assertOk()
            ->assertSee('Hola estudiante desde el panel');

        $this->assertDatabaseHas('mensajes', ['id' => $mensaje->id, 'leido' => true]);
    }

    public function test_usuario_no_participante_no_abre_conversacion(): void
    {
        $ctx = $this->crearCtx();
        $otro = User::factory()->estudiante()->create();
        $mensaje = Mensaje::create([
            'asunto' => 'Confidencial',
            'cuerpo_contenido' => 'Solo entre docente y repre.',
            'carpeta' => 'no_leidos',
            'emisor_id' => $ctx['docente']->id,
            'emisor_type' => User::class,
            'destinatario_id' => $ctx['representante']->id,
            'contexto_estudiante_id' => $ctx['alumno']->id,
            'leido' => false,
            'fecha_envio' => now(),
        ]);

        $this->actingAs($otro)
            ->get(route('mensajes.conversacion', $mensaje->id))
            ->assertForbidden();
    }

    public function test_el_hilo_se_mantiene_en_una_sola_linea_y_el_nuevo_en_otra(): void
    {
        $ctx = $this->crearCtx();
        $otroAlumno = Alumno::factory()->create();
        $otroRepresentante = User::factory()->representante()->create();
        $otroAlumno->update(['representante_user_id' => $otroRepresentante->id]);

        $primero = Mensaje::create([
            'asunto' => 'Hilo A',
            'cuerpo_contenido' => 'Mensaje 1',
            'carpeta' => 'no_leidos',
            'emisor_id' => $ctx['docente']->id,
            'emisor_type' => User::class,
            'destinatario_id' => $ctx['representante']->id,
            'contexto_estudiante_id' => $ctx['alumno']->id,
            'leido' => false,
            'fecha_envio' => now()->subMinutes(10),
        ]);
        $segundo = Mensaje::create([
            'asunto' => 'Hilo A',
            'cuerpo_contenido' => 'Mensaje 2',
            'carpeta' => 'enviados',
            'emisor_id' => $ctx['representante']->id,
            'emisor_type' => User::class,
            'destinatario_id' => $ctx['docente']->id,
            'hilo_id' => $primero->id,
            'contexto_estudiante_id' => $ctx['alumno']->id,
            'leido' => false,
            'fecha_envio' => now()->subMinutes(5),
        ]);
        $nuevo = Mensaje::create([
            'asunto' => 'Hilo B',
            'cuerpo_contenido' => 'Para otro representante',
            'carpeta' => 'enviados',
            'emisor_id' => $ctx['docente']->id,
            'emisor_type' => User::class,
            'destinatario_id' => $otroRepresentante->id,
            'contexto_estudiante_id' => $otroAlumno->id,
            'leido' => false,
            'fecha_envio' => now(),
        ]);

        $respuesta = $this->actingAs($ctx['docente'])->get(route('mensajes.index'))->assertOk();

        $hilos = $respuesta->viewData('hilos');
        $this->assertCount(2, $hilos);

        $hiloA = collect($hilos)->first(fn ($h) => $h['ultimo_cuerpo'] === 'Mensaje 2');
        $this->assertEquals('Mensaje 2', $hiloA['ultimo_cuerpo']);
        $this->assertEquals($segundo->id, $hiloA['id']);
        $this->assertEquals(1, $hiloA['no_leidos']);

        $this->assertTrue(collect($hilos)->contains(fn ($h) => $h['ultimo_cuerpo'] === 'Para otro representante'));
    }

    public function test_responder_permanece_en_el_hilo_y_un_mensaje_nuevo_abre_otra_linea(): void
    {
        $ctx = $this->crearCtx();
        $companero = User::factory()->representante()->create();
        $companero->name = 'MENDOZA';
        $companero->apellidos = 'ANDRADE';
        $companero->save();

        $respuesta = $this->actingAs($ctx['docente'])->post(route('mensajes.store'), [
            'asunto' => 'Tarea de lecturas',
            'cuerpo_contenido' => 'Primer aviso',
            'destinatario_id' => $companero->id,
            'contexto_estudiante_id' => $ctx['alumno']->id,
        ])->assertRedirect();
        $raiz = Mensaje::where('cuerpo_contenido', 'Primer aviso')->first();
        $this->assertNull($raiz->hilo_id);

        $this->actingAs($companero)->post(route('mensajes.store'), [
            'asunto' => 'Re: Tarea de lecturas',
            'cuerpo_contenido' => 'Respuesta al aviso',
            'destinatario_id' => $ctx['docente']->id,
            'contexto_estudiante_id' => $ctx['alumno']->id,
            'hilo_id' => $raiz->id,
        ])->assertRedirect();

        $this->actingAs($ctx['docente'])->post(route('mensajes.store'), [
            'asunto' => 'Nuevo aviso',
            'cuerpo_contenido' => 'Segundo aviso, mensaje nuevo',
            'destinatario_id' => $companero->id,
            'contexto_estudiante_id' => $ctx['alumno']->id,
        ])->assertRedirect();

        $nuevo = Mensaje::where('cuerpo_contenido', 'Segundo aviso, mensaje nuevo')->first();
        $this->assertNull($nuevo->hilo_id);
        $this->assertNotEquals($raiz->id, $nuevo->id);

        $hilos = $this->actingAs($ctx['docente'])
            ->get(route('mensajes.index'))
            ->assertOk()
            ->viewData('hilos');

        $this->assertCount(2, $hilos);

        $hiloOriginal = collect($hilos)->first(fn ($h) => $h['ultimo_cuerpo'] === 'Respuesta al aviso');
        $this->assertNotNull($hiloOriginal);
        $this->assertTrue(collect($hilos)->contains(fn ($h) => $h['ultimo_cuerpo'] === 'Segundo aviso, mensaje nuevo'));
    }

    public function test_no_se_puede_responder_a_un_hilo_ajeno(): void
    {
        $ctx = $this->crearCtx();
        $ajeno = Mensaje::create([
            'asunto' => 'Hilo de otro',
            'cuerpo_contenido' => 'no debes verlo',
            'carpeta' => 'enviados',
            'emisor_id' => $ctx['docente']->id,
            'emisor_type' => User::class,
            'destinatario_id' => $ctx['representante']->id,
            'leido' => false,
            'fecha_envio' => now(),
        ]);

        $this->actingAs($ctx['estudiante'])
            ->post(route('mensajes.store'), [
                'asunto' => 'Hola',
                'cuerpo_contenido' => 'Intento ajeno',
                'destinatario_id' => $ctx['docente']->id,
                'hilo_id' => $ajeno->id,
            ])
            ->assertForbidden();
    }
}