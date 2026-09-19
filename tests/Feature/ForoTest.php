<?php

namespace Tests\Feature;

use App\Models\Alumno;
use App\Models\ClaseDocente;
use App\Models\Curso;
use App\Models\Foro;
use App\Models\ForoIntervencion;
use App\Models\Matricula;
use App\Models\Materia;
use App\Models\Paralelo;
use App\Models\PeriodoLectivo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ForoTest extends TestCase
{
    use RefreshDatabase;

    private function crearContextoClase(User $docente): array
    {
        $curso = Curso::factory()->create();
        $paralelo = Paralelo::factory()->create();
        $materia = Materia::create(['nombre' => 'Lengua', 'codigo' => 'LEN', 'color_tarjeta' => '#000000']);
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

    private function crearForo(ClaseDocente $clase, array $extra = []): Foro
    {
        return Foro::create(array_merge([
            'clase_docente_id' => $clase->id,
            'titulo' => 'Tema de prueba',
            'pregunta' => '¿Cuál es tu postura sobre la lectura crítica?',
            'fecha_inicio' => now()->subHour(),
            'fecha_cierre' => now()->addHour(),
            'links' => [
                ['nombre' => 'Articulo de consulta', 'url' => 'https://ejemplo.com/lectura'],
            ],
        ], $extra));
    }

    public function test_docente_crea_un_foro_con_fechas_y_enlaces(): void
    {
        $ctx = $this->crearContextoClase(User::factory()->docente()->create());
        $clase = $ctx['clase'];

        $this->actingAs($ctx['docente'])
            ->post(route('clases.foros.store', $clase->id), [
                'titulo' => 'Resultados PISA y la IA',
                'pregunta' => '¿Qué relación existe entre PISA y la IA?',
                'fecha_inicio' => now()->addDay()->format('Y-m-d\TH:i'),
                'fecha_cierre' => now()->addDays(5)->format('Y-m-d\TH:i'),
                'links' => [
                    ['nombre' => 'Informe PISA', 'url' => 'https://ejemplo.com/pisa'],
                    ['nombre' => '', 'url' => ''],
                    ['nombre' => 'Sobre IA', 'url' => 'https://ejemplo.com/ia'],
                ],
            ])
            ->assertRedirect(route('clases.foros', $clase->id));

        $this->assertDatabaseHas('foros', [
            'clase_docente_id' => $clase->id,
            'titulo' => 'Resultados PISA y la IA',
        ]);

        $foro = $clase->foros()->firstOrFail();
        $this->assertCount(2, $foro->links);
        $this->assertSame('Informe PISA', $foro->links[0]['nombre']);
        $this->assertSame('no_iniciado', $foro->estado());

        $this->actingAs($ctx['docente'])
            ->get(route('clases.foros', $clase->id))
            ->assertOk()
            ->assertSee('Resultados PISA y la IA');
    }

    public function test_validacion_del_foro(): void
    {
        $ctx = $this->crearContextoClase(User::factory()->docente()->create());
        $clase = $ctx['clase'];

        $this->actingAs($ctx['docente'])
            ->post(route('clases.foros.store', $clase->id), [])
            ->assertSessionHasErrors(['titulo', 'pregunta', 'fecha_inicio', 'fecha_cierre']);

        $this->actingAs($ctx['docente'])
            ->post(route('clases.foros.store', $clase->id), [
                'titulo' => 'Tema',
                'pregunta' => 'Pregunta',
                'fecha_inicio' => now()->addDays(2)->format('Y-m-d\TH:i'),
                'fecha_cierre' => now()->addDay()->format('Y-m-d\TH:i'),
            ])
            ->assertSessionHasErrors(['fecha_cierre']);

        $this->actingAs($ctx['docente'])
            ->post(route('clases.foros.store', $clase->id), [
                'titulo' => 'Tema',
                'pregunta' => 'Pregunta',
                'fecha_inicio' => now()->addDay()->format('Y-m-d\TH:i'),
                'fecha_cierre' => now()->addDays(2)->format('Y-m-d\TH:i'),
                'links' => [
                    ['nombre' => '', 'url' => 'no-es-una-url'],
                ],
            ])
            ->assertSessionHasErrors(['links.0.url']);
    }

    public function test_estudiante_no_puede_crear_foros(): void
    {
        $ctx = $this->crearContextoClase(User::factory()->docente()->create());
        $clase = $ctx['clase'];

        $this->actingAs($ctx['estudiante'])
            ->post(route('clases.foros.store', $clase->id), [
                'titulo' => 'Intruso',
                'pregunta' => 'Contenido',
                'fecha_inicio' => now()->addDay()->format('Y-m-d\TH:i'),
                'fecha_cierre' => now()->addDays(2)->format('Y-m-d\TH:i'),
            ])
            ->assertForbidden();
    }

    public function test_estudiante_participa_responde_replica_y_concluye(): void
    {
        $ctx = $this->crearContextoClase(User::factory()->docente()->create());
        $foro = $this->crearForo($ctx['clase']);

        $this->actingAs($ctx['estudiante'])
            ->post(route('foros.intervenciones.store', $foro->id), [
                'tipo' => 'respuesta',
                'cuerpo' => 'Considero que la IA debe usarse como tutor, no como sustituto.',
            ])
            ->assertRedirect(route('foros.show', $foro->id));

        $respuesta = $foro->intervenciones()->where('tipo', 'respuesta')->firstOrFail();
        $this->assertSame($ctx['estudiante']->id, $respuesta->user_id);

        $this->actingAs($ctx['estudiante'])
            ->post(route('foros.intervenciones.store', $foro->id), [
                'tipo' => 'replica',
                'padre_id' => $respuesta->id,
                'cuerpo' => 'Estoy de acuerdo y añado que debemos evaluar la argumentación.',
            ])
            ->assertRedirect(route('foros.show', $foro->id));

        $this->actingAs($ctx['estudiante'])
            ->post(route('foros.intervenciones.store', $foro->id), [
                'tipo' => 'conclusion',
                'cuerpo' => 'Mi conclusión es que la IA complementa, no reemplaza, el pensamiento crítico.',
            ])
            ->assertRedirect(route('foros.show', $foro->id));

        $this->assertSame(3, $foro->intervenciones()->count());

        $this->actingAs($ctx['estudiante'])
            ->get(route('foros.show', $foro->id))
            ->assertOk()
            ->assertSee('Estoy de acuerdo')
            ->assertSee('Acción prohibida');
    }

    public function test_no_se_participa_antes_del_inicio_ni_despues_del_cierre(): void
    {
        $ctx = $this->crearContextoClase(User::factory()->docente()->create());

        $futuro = $this->crearForo($ctx['clase'], [
            'fecha_inicio' => now()->addDay(),
            'fecha_cierre' => now()->addDays(2),
        ]);

        $this->actingAs($ctx['estudiante'])
            ->from(route('foros.show', $futuro->id))
            ->post(route('foros.intervenciones.store', $futuro->id), [
                'tipo' => 'respuesta',
                'cuerpo' => 'Intento antes de tiempo.',
            ])
            ->assertRedirect(route('foros.show', $futuro->id))
            ->assertSessionHas('error');

        $this->assertSame(0, $futuro->intervenciones()->count());

        $cerrado = $this->crearForo($ctx['clase'], [
            'fecha_inicio' => now()->subDays(2),
            'fecha_cierre' => now()->subHour(),
        ]);

        $this->actingAs($ctx['estudiante'])
            ->from(route('foros.show', $cerrado->id))
            ->post(route('foros.intervenciones.store', $cerrado->id), [
                'tipo' => 'respuesta',
                'cuerpo' => 'Intento fuera del plazo.',
            ])
            ->assertRedirect(route('foros.show', $cerrado->id))
            ->assertSessionHas('error');

        $this->assertSame(0, $cerrado->intervenciones()->count());
    }

    public function test_solo_se_permite_una_respuesta_principal_y_una_conclusion(): void
    {
        $ctx = $this->crearContextoClase(User::factory()->docente()->create());
        $foro = $this->crearForo($ctx['clase']);

        $this->actingAs($ctx['estudiante'])
            ->post(route('foros.intervenciones.store', $foro->id), [
                'tipo' => 'respuesta',
                'cuerpo' => 'Primera respuesta.',
            ])
            ->assertRedirect(route('foros.show', $foro->id));

        $this->actingAs($ctx['estudiante'])
            ->from(route('foros.show', $foro->id))
            ->post(route('foros.intervenciones.store', $foro->id), [
                'tipo' => 'respuesta',
                'cuerpo' => 'Segunda respuesta.',
            ])
            ->assertRedirect(route('foros.show', $foro->id))
            ->assertSessionHas('error');

        $this->actingAs($ctx['estudiante'])
            ->from(route('foros.show', $foro->id))
            ->post(route('foros.intervenciones.store', $foro->id), [
                'tipo' => 'conclusion',
                'cuerpo' => 'Conclusión única.',
            ])
            ->assertRedirect(route('foros.show', $foro->id))
            ->assertSessionHas('success');

        $this->actingAs($ctx['estudiante'])
            ->from(route('foros.show', $foro->id))
            ->post(route('foros.intervenciones.store', $foro->id), [
                'tipo' => 'conclusion',
                'cuerpo' => 'Intento de segunda conclusión.',
            ])
            ->assertRedirect(route('foros.show', $foro->id))
            ->assertSessionHas('error');
    }

    public function test_no_se_puede_replicar_una_replica(): void
    {
        $ctx = $this->crearContextoClase(User::factory()->docente()->create());
        $foro = $this->crearForo($ctx['clase']);

        $this->actingAs($ctx['estudiante'])
            ->post(route('foros.intervenciones.store', $foro->id), [
                'tipo' => 'respuesta',
                'cuerpo' => 'Respuesta a replicar.',
            ]);

        $respuesta = $foro->intervenciones()->where('tipo', 'respuesta')->firstOrFail();

        $this->actingAs($ctx['estudiante'])
            ->post(route('foros.intervenciones.store', $foro->id), [
                'tipo' => 'replica',
                'padre_id' => $respuesta->id,
                'cuerpo' => 'Réplica válida.',
            ])
            ->assertRedirect(route('foros.show', $foro->id));

        $replica = $foro->intervenciones()->where('tipo', 'replica')->firstOrFail();

        $this->actingAs($ctx['estudiante'])
            ->from(route('foros.show', $foro->id))
            ->post(route('foros.intervenciones.store', $foro->id), [
                'tipo' => 'replica',
                'padre_id' => $replica->id,
                'cuerpo' => 'Réplica a otra réplica.',
            ])
            ->assertRedirect(route('foros.show', $foro->id))
            ->assertSessionHas('error');
    }

    public function test_resumen_de_participacion_por_estudiante(): void
    {
        $ctx = $this->crearContextoClase(User::factory()->docente()->create());
        $foro = $this->crearForo($ctx['clase']);

        $this->actingAs($ctx['estudiante'])
            ->post(route('foros.intervenciones.store', $foro->id), [
                'tipo' => 'respuesta',
                'cuerpo' => 'Respuesta para el resumen.',
            ]);

        $respuesta = $foro->intervenciones()->where('tipo', 'respuesta')->firstOrFail();

        $this->actingAs($ctx['estudiante'])
            ->post(route('foros.intervenciones.store', $foro->id), [
                'tipo' => 'replica',
                'padre_id' => $respuesta->id,
                'cuerpo' => 'Réplica para el resumen.',
            ]);

        $this->actingAs($ctx['docente'])
            ->get(route('foros.show', $foro->id))
            ->assertOk()
            ->assertSee('Resumen de participación')
            ->assertSee($ctx['estudiante']->apellidos);
    }

    public function test_docente_elimina_el_foro(): void
    {
        $ctx = $this->crearContextoClase(User::factory()->docente()->create());
        $foro = $this->crearForo($ctx['clase']);

        $this->actingAs($ctx['docente'])
            ->delete(route('foros.destroy', $foro->id))
            ->assertRedirect(route('clases.foros', $ctx['clase']->id));

        $this->assertDatabaseMissing('foros', ['id' => $foro->id]);
    }

    public function test_acceso_denegado_a_otro_docente_y_estudiante_ajeno(): void
    {
        $otroDocente = User::factory()->docente()->create();
        $ctx = $this->crearContextoClase($otroDocente);
        $foro = $this->crearForo($ctx['clase']);

        $ajeno = User::factory()->docente()->create();
        $this->actingAs($ajeno)->get(route('foros.show', $foro->id))->assertForbidden();

        $alumnoAjeno = Alumno::factory()->create();
        $estudianteAjeno = User::factory()->estudiante()->create();
        $alumnoAjeno->update(['user_id' => $estudianteAjeno->id]);

        $this->actingAs($estudianteAjeno)->get(route('foros.show', $foro->id))->assertForbidden();
    }

    public function test_pagina_visible_tras_cerrar_muestra_resumen_y_bloquea_escritura(): void
    {
        $ctx = $this->crearContextoClase(User::factory()->docente()->create());
        $foro = $this->crearForo($ctx['clase'], [
            'fecha_inicio' => now()->subDays(2),
            'fecha_cierre' => now()->subHour(),
        ]);

        $this->actingAs($ctx['estudiante'])
            ->get(route('foros.show', $foro->id))
            ->assertOk()
            ->assertSee('Resumen de participación')
            ->assertSee('foro-textarea')
            ->assertSee('Acción prohibida');
    }
}