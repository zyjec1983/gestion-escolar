<?php

namespace Tests\Feature\Secretaria;

use App\Models\Alumno;
use App\Models\ClaseDocente;
use App\Models\Curso;
use App\Models\Institucion;
use App\Models\Materia;
use App\Models\Matricula;
use App\Models\Nota;
use App\Models\NotaAuditoria;
use App\Models\Paralelo;
use App\Models\PeriodoLectivo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

/**
 * Correccion extraordinaria de notas: permisos, validaciones, trazabilidad e
 * inmutabilidad de la bitacora. Tambien cubre que el docente ya no pueda
 * sobrescribir una nota final.
 */
class NotaExtraordinariaTest extends TestCase
{
    use RefreshDatabase;

    private User $secretaria;

    private User $docente;

    private ClaseDocente $clase;

    private Alumno $alumno;

    private Matricula $matricula;

    protected function setUp(): void
    {
        parent::setUp();

        Institucion::factory()->create(['sistema_evaluacion' => 'quimestre']);
        PeriodoLectivo::factory()->create(['activo' => true]);

        $this->secretaria = User::factory()->secretaria()->create();
        $this->docente = User::factory()->docente()->create();

        $curso = Curso::factory()->create();
        $paralelo = Paralelo::factory()->create();
        $materia = Materia::create(['nombre' => 'Matemática', 'codigo' => 'MAT', 'color_tarjeta' => '#000000']);

        $this->clase = ClaseDocente::create([
            'user_id' => $this->docente->id,
            'materia_id' => $materia->id,
            'curso_id' => $curso->id,
            'paralelo_id' => $paralelo->id,
        ]);

        $this->alumno = Alumno::factory()->create();
        $this->matricula = Matricula::factory()->create([
            'alumno_id' => $this->alumno->id,
            'curso_id' => $curso->id,
            'paralelo_id' => $paralelo->id,
            'periodo_id' => PeriodoLectivo::first()->id,
        ]);
    }

    private function crearNota(array $cambios = []): Nota
    {
        return Nota::create(array_merge([
            'matricula_id' => $this->matricula->id,
            'alumno_id' => $this->alumno->id,
            'clase_docente_id' => $this->clase->id,
            'parcial' => 1,
            'tipo_nota' => 'parcial',
            'fii' => 8,
            'ffi' => 8,
            'fig' => 8,
            'ffg' => 8,
            'proyecto_integrador' => 8,
            'evaluacion_parcial' => 8,
            'estado' => 'final',
        ], $cambios));
    }

    private function payloadCorreccion(Nota $nota, array $cambios = []): array
    {
        return array_merge([
            'nota_id' => $nota->id,
            'fii' => 9.5,
            'ffi' => 9.5,
            'fig' => 9.5,
            'ffg' => 9.5,
            'proyecto_integrador' => 9.5,
            'evaluacion_parcial' => 9.5,
            'motivo' => 'Error de digitación verificada contra el acta física firmada por el docente.',
        ], $cambios);
    }

    /** @test */
    public function solo_secretaria_y_admin_pueden_acceder_al_modulo(): void
    {
        $this->actingAs($this->secretaria)
            ->get(route('notas-extraordinarias.index'))
            ->assertOk()
            ->assertSee('Corrección extraordinaria de notas');

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('notas-extraordinarias.index'))
            ->assertOk();

        $this->actingAs($this->docente)
            ->get(route('notas-extraordinarias.index'))
            ->assertForbidden();

        $this->actingAs($this->docente)
            ->get(route('notas-extraordinarias.bitacora'))
            ->assertForbidden();

        auth()->logout();
        $this->get(route('notas-extraordinarias.index'))->assertRedirect('/login');
    }

    /** @test */
    public function la_correccion_guarda_el_antes_y_despues_con_trazabilidad_de_red(): void
    {
        $nota = $this->crearNota(['fii' => 7.25]);

        $this->actingAs($this->secretaria)
            ->withHeader('User-Agent', 'PHPUnit/NotaExtraordinaria')
            ->withServerVariables(['REMOTE_ADDR' => '10.20.30.40'])
            ->post(route('notas-extraordinarias.store'), $this->payloadCorreccion($nota))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $nota->refresh();
        $this->assertSame(9.5, round((float) $nota->fii, 2));
        $this->assertSame('final', $nota->estado);

        $auditoria = NotaAuditoria::firstOrFail();
        $this->assertSame(NotaAuditoria::ACCION_CORRECCION, $auditoria->accion);
        $this->assertSame($this->secretaria->id, $auditoria->usuario_id);
        $this->assertSame('SECRETARIA', $auditoria->usuario_rol);
        $this->assertSame('10.20.30.40', $auditoria->ip);
        $this->assertStringContainsString('PHPUnit', (string) $auditoria->user_agent);
        $this->assertSame(7.25, $auditoria->datos_anteriores['fii']);
        $this->assertSame(9.5, $auditoria->datos_nuevos['fii']);
        $this->assertSame('final', $auditoria->datos_anteriores['estado']);
        $this->assertNotEmpty($auditoria->motivo);
        $this->assertSame('Matemática', $auditoria->materia);
    }

    /** @test */
    public function el_motivo_es_obligatorio_y_las_notas_se_validan_en_rango(): void
    {
        $nota = $this->crearNota();

        $this->actingAs($this->secretaria)
            ->post(route('notas-extraordinarias.store'), $this->payloadCorreccion($nota, ['motivo' => 'corto']))
            ->assertSessionHasErrors('motivo');

        $this->actingAs($this->secretaria)
            ->post(route('notas-extraordinarias.store'), $this->payloadCorreccion($nota, ['fii' => 11]))
            ->assertSessionHasErrors('fii');

        $this->actingAs($this->secretaria)
            ->post(route('notas-extraordinarias.store'), $this->payloadCorreccion($nota, ['ffi' => '9.555']))
            ->assertSessionHasErrors('ffi');

        $this->assertDatabaseCount('nota_auditorias', 0);
    }

    /** @test */
    public function una_correccion_sin_cambios_reales_se_rechaza(): void
    {
        $nota = $this->crearNota(['fii' => 8, 'ffi' => 8, 'fig' => 8, 'ffg' => 8, 'proyecto_integrador' => 8, 'evaluacion_parcial' => 8]);

        $this->actingAs($this->secretaria)
            ->post(route('notas-extraordinarias.store'), [
                'nota_id' => $nota->id,
                'fii' => 8, 'ffi' => 8, 'fig' => 8, 'ffg' => 8,
                'proyecto_integrador' => 8, 'evaluacion_parcial' => 8,
                'motivo' => 'Se intenta guardar exactamente el mismo valor sin cambios.',
            ])
            ->assertSessionHasErrors('motivo');

        $this->assertDatabaseCount('nota_auditorias', 0);
    }

    /** @test */
    public function la_bitacora_es_inalterable_a_nivel_de_modelo(): void
    {
        $nota = $this->crearNota();

        $this->actingAs($this->secretaria)
            ->post(route('notas-extraordinarias.store'), $this->payloadCorreccion($nota));

        $auditoria = NotaAuditoria::firstOrFail();

        try {
            $auditoria->update(['motivo' => 'Intento de alterar la bitácora']);
            $this->fail('La bitácora permitió una actualización.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('inalterable', $e->getMessage());
        }

        try {
            $auditoria->delete();
            $this->fail('La bitácora permitió un borrado.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('inalterable', $e->getMessage());
        }

        $this->assertDatabaseCount('nota_auditorias', 1);
    }

    /** @test */
    public function secretaria_puede_corregir_una_nota_de_examen(): void
    {
        $nota = $this->crearNota([
            'parcial' => 3,
            'tipo_nota' => 'examen',
            'nota_examen' => 6,
        ]);

        $this->actingAs($this->secretaria)
            ->post(route('notas-extraordinarias.store'), [
                'nota_id' => $nota->id,
                'nota_examen' => 8.75,
                'motivo' => 'Recalificación autorizada por el examen quimestral.',
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(8.75, round((float) $nota->fresh()->nota_examen, 2));
        $this->assertSame(6, (int) round((float) NotaAuditoria::firstOrFail()->datos_anteriores['nota_examen']));
    }

    /** @test */
    public function la_bitacora_muestra_el_asiento_con_usuario_y_motivo(): void
    {
        $nota = $this->crearNota();

        $this->actingAs($this->secretaria)
            ->post(route('notas-extraordinarias.store'), $this->payloadCorreccion($nota));

        $this->actingAs($this->secretaria)
            ->get(route('notas-extraordinarias.bitacora'))
            ->assertOk()
            ->assertSee('Bitácora de notas extraordinarias')
            ->assertSee($this->alumno->apellidos)
            ->assertSee('Matemática')
            ->assertSee('SECRETARIA');
    }

    /** @test */
    public function un_docente_no_puede_modificar_una_nota_final_ya_guardada(): void
    {
        $nota = $this->crearNota(['fii' => 7]);

        $this->actingAs($this->docente)
            ->post(route('docente.notas.store'), [
                'clase_docente_id' => $this->clase->id,
                'parcial' => 1,
                'tipo_guardado' => 'final',
                'notas' => [
                    $this->alumno->id => [
                        'alumno_id' => $this->alumno->id,
                        'fii' => 10, 'ffi' => 10, 'fig' => 10, 'ffg' => 10,
                        'proyecto_integrador' => 10, 'evaluacion_parcial' => 10,
                    ],
                ],
            ])
            ->assertSessionHasErrors('notas');

        $this->assertSame(7.0, round((float) $nota->fresh()->fii, 2));
        $this->assertSame(0, NotaAuditoria::count());
    }

    /** @test */
    public function un_docente_no_puede_escribir_en_una_clase_ajena(): void
    {
        $otroDocente = User::factory()->docente()->create();
        $claseAjena = ClaseDocente::create([
            'user_id' => $otroDocente->id,
            'materia_id' => $this->clase->materia_id,
            'curso_id' => $this->clase->curso_id,
            'paralelo_id' => $this->clase->paralelo_id,
        ]);

        $this->actingAs($this->docente)
            ->post(route('docente.notas.store'), [
                'clase_docente_id' => $claseAjena->id,
                'parcial' => 1,
                'tipo_guardado' => 'parcial',
                'notas' => [
                    $this->alumno->id => [
                        'alumno_id' => $this->alumno->id,
                        'fii' => 10, 'ffi' => 10, 'fig' => 10, 'ffg' => 10,
                        'proyecto_integrador' => 10, 'evaluacion_parcial' => 10,
                    ],
                ],
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('notas', 0);
    }
}
