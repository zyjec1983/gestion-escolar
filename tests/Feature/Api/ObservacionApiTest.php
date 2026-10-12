<?php

namespace Tests\Feature\Api;

use App\Models\Alumno;
use App\Models\ClaseDocente;
use App\Models\Curso;
use App\Models\Materia;
use App\Models\Mensaje;
use App\Models\Matricula;
use App\Models\Observacion;
use App\Models\Paralelo;
use App\Models\PeriodoLectivo;
use App\Models\TipoObservacion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ObservacionApiTest extends TestCase
{
    use RefreshDatabase;

    private User $docente;

    private User $otroDocente;

    private ClaseDocente $clase;

    private Alumno $alumnoPropio;

    private Alumno $alumnoAjeno;

    private TipoObservacion $conducta;

    protected function setUp(): void
    {
        parent::setUp();

        $periodo = PeriodoLectivo::factory()->create();
        $cursoA = Curso::factory()->create(['nombre' => 'NOVENO GRADO']);
        $cursoB = Curso::factory()->create(['nombre' => 'DECIMO GRADO']);
        $paraleloA = Paralelo::factory()->create(['nombre' => 'A']);
        $paraleloB = Paralelo::factory()->create(['nombre' => 'A']);

        $this->docente = User::factory()->docente()->create();
        $this->otroDocente = User::factory()->docente()->create();

        $this->clase = ClaseDocente::create([
            'user_id' => $this->docente->id,
            'materia_id' => Materia::create(['nombre' => 'Matematicas'])->id,
            'curso_id' => $cursoA->id,
            'paralelo_id' => $paraleloA->id,
        ]);

        $this->alumnoPropio = Alumno::factory()->create(['apellidos' => 'Propio', 'nombres' => 'Alumno']);
        $this->alumnoAjeno = Alumno::factory()->create(['apellidos' => 'Ajeno', 'nombres' => 'Alumno']);

        Matricula::factory()->create([
            'alumno_id' => $this->alumnoPropio->id,
            'curso_id' => $cursoA->id,
            'paralelo_id' => $paraleloA->id,
            'periodo_id' => $periodo->id,
        ]);
        Matricula::factory()->create([
            'alumno_id' => $this->alumnoAjeno->id,
            'curso_id' => $cursoB->id,
            'paralelo_id' => $paraleloB->id,
            'periodo_id' => $periodo->id,
        ]);

        $this->conducta = TipoObservacion::create([
            'codigo' => 'OBS_CONDUCTA',
            'nombre' => 'OBSERVACION DE CONDUCTA',
        ]);
    }

    private function payload(Alumno $alumno): array
    {
        return [
            'alumno_codigo' => $alumno->codigo,
            'tipo_observacion_id' => $this->conducta->codigo,
            'cuerpo_observacion' => 'Observacion creada desde la API.',
        ];
    }

    public function test_el_docente_registra_sobre_un_alumno_de_su_clase(): void
    {
        Sanctum::actingAs($this->docente);

        $this->postJson('/api/observaciones', $this->payload($this->alumnoPropio))
            ->assertCreated();

        $this->assertDatabaseHas('observaciones', [
            'alumno_id' => $this->alumnoPropio->id,
            'user_id' => $this->docente->id,
        ]);
    }

    public function test_el_docente_no_puede_registrar_sobre_un_alumno_ajeno(): void
    {
        Sanctum::actingAs($this->docente);

        $this->postJson('/api/observaciones', $this->payload($this->alumnoAjeno))
            ->assertForbidden();

        $this->assertDatabaseCount('observaciones', 0);
    }

    public function test_representante_y_estudiante_no_pueden_registrar_observaciones(): void
    {
        foreach ([User::factory()->representante()->create(), User::factory()->estudiante()->create()] as $usuario) {
            Sanctum::actingAs($usuario);

            $this->postJson('/api/observaciones', $this->payload($this->alumnoPropio))
                ->assertForbidden();
        }

        $this->assertDatabaseCount('observaciones', 0);
    }

    public function test_una_autoridad_registra_sobre_cualquier_alumno(): void
    {
        foreach (['INSPECCION', 'RECTORA', 'VICERRECTORA', 'DECE'] as $rol) {
            Sanctum::actingAs(User::factory()->create(['rol' => $rol]));

            $this->postJson('/api/observaciones', $this->payload($this->alumnoAjeno))
                ->assertCreated();
        }

        $this->assertDatabaseCount('observaciones', 4);
    }

    public function test_el_docente_consulta_el_historial_de_su_clase(): void
    {
        Sanctum::actingAs($this->docente);

        $this->getJson("/api/clases/{$this->clase->id}/observaciones")
            ->assertOk()
            ->assertJsonPath('contexto_clase.id', $this->clase->id);
    }

    public function test_el_docente_no_puede_consultar_la_clase_de_otro_docente(): void
    {
        $claseAjena = ClaseDocente::create([
            'user_id' => $this->otroDocente->id,
            'materia_id' => Materia::create(['nombre' => 'Lengua'])->id,
            'curso_id' => $this->clase->curso_id,
            'paralelo_id' => $this->clase->paralelo_id,
        ]);

        Sanctum::actingAs($this->docente);

        $this->getJson("/api/clases/{$claseAjena->id}/observaciones")
            ->assertForbidden();
    }

    public function test_una_autoridad_consulta_el_historial_de_cualquier_clase(): void
    {
        Sanctum::actingAs(User::factory()->create(['rol' => 'DECE']));

        $this->getJson("/api/clases/{$this->clase->id}/observaciones")
            ->assertOk();

        Sanctum::actingAs(User::factory()->create(['rol' => 'INSPECCION']));

        $this->getJson("/api/clases/{$this->clase->id}/observaciones")
            ->assertOk();
    }

    public function test_sin_autenticacion_no_se_puede_tocar_el_historial(): void
    {
        $this->postJson('/api/observaciones', $this->payload($this->alumnoPropio))
            ->assertUnauthorized();

        $this->getJson("/api/clases/{$this->clase->id}/observaciones")
            ->assertUnauthorized();

        $this->assertDatabaseCount('observaciones', 0);
    }

    public function test_el_docente_ve_el_historial_registrado_por_la_autoridad(): void
    {
        Sanctum::actingAs(User::factory()->create(['rol' => 'INSPECCION']));
        $this->postJson('/api/observaciones', $this->payload($this->alumnoPropio))->assertCreated();

        Sanctum::actingAs($this->docente);
        $this->getJson("/api/clases/{$this->clase->id}/observaciones")
            ->assertOk()
            ->assertJsonCount(1, 'tabla_historial_detalle.registros');

        $this->assertSame(1, Observacion::count());
    }

    public function test_el_docente_solo_avisa_al_representante_si_lo_pide(): void
    {
        Sanctum::actingAs($this->docente);

        $this->postJson('/api/observaciones', $this->payload($this->alumnoPropio))
            ->assertCreated()
            ->assertJsonPath('notificado', false);

        $this->assertDatabaseCount('mensajes', 0);

        Sanctum::actingAs($this->docente);

        $this->postJson('/api/observaciones', $this->payload($this->alumnoPropio) + ['notificar_representante' => true])
            ->assertCreated()
            ->assertJsonPath('notificado', true);

        $this->assertDatabaseHas('mensajes', [
            'destinatario_id' => $this->alumnoPropio->representante_user_id,
            'contexto_estudiante_id' => $this->alumnoPropio->id,
        ]);
    }

    public function test_la_autoridad_siempre_avisa_al_representante(): void
    {
        foreach (['INSPECCION', 'RECTORA', 'VICERRECTORA', 'DECE'] as $rol) {
            Sanctum::actingAs(User::factory()->create(['rol' => $rol]));

            $this->postJson('/api/observaciones', $this->payload($this->alumnoPropio))
                ->assertCreated()
                ->assertJsonPath('notificado', true);
        }

        // El aviso es obligatorio para las autoridades, asi que el atributo
        // queda marcado aunque el cliente no lo haya pedido.
        $this->assertSame(4, Observacion::where('notificar_representante', true)->count());
        $this->assertDatabaseCount('mensajes', 4);
    }

    public function test_el_aviso_por_api_dice_lo_mismo_que_el_de_la_pantalla(): void
    {
        Sanctum::actingAs(User::factory()->create(['rol' => 'INSPECCION']));

        $this->postJson('/api/observaciones', $this->payload($this->alumnoPropio))
            ->assertCreated();

        $mensaje = Mensaje::where('contexto_estudiante_id', $this->alumnoPropio->id)->firstOrFail();

        $this->assertSame('enviados', $mensaje->carpeta);
        $this->assertFalse($mensaje->leido);
        $this->assertStringContainsString($this->conducta->nombre, $mensaje->cuerpo_contenido);
        $this->assertStringContainsString('Observacion creada desde la API.', $mensaje->cuerpo_contenido);
        $this->assertStringContainsString('INSPECCION', $mensaje->cuerpo_contenido);
    }

    public function test_el_docente_no_puede_registrar_con_un_tipo_inexistente(): void
    {
        Sanctum::actingAs($this->docente);

        $this->postJson('/api/observaciones', [
            'alumno_codigo' => $this->alumnoPropio->codigo,
            'tipo_observacion_id' => 'NO_EXISTE',
            'cuerpo_observacion' => 'prueba',
        ])->assertStatus(422);

        $this->assertDatabaseCount('observaciones', 0);
    }
}