<?php

namespace Tests\Feature\Dece;

use App\Models\Alumno;
use App\Models\Curso;
use App\Models\DeceAtencion;
use App\Models\DeceAuditoria;
use App\Models\Institucion;
use App\Models\Matricula;
use App\Models\Paralelo;
use App\Models\PeriodoLectivo;
use App\Models\User;
use RuntimeException;
use Tests\TestCase;

/**
 * Acceso por rol, inmutabilidad de la bitacora y confidencialidad del
 * expediente. Son las garantias transversales de todo el modulo DECE.
 */
class DeceAccesoTest extends TestCase
{
    public function test_roles_no_autorizados_no_acceden_al_modulo(): void
    {
        $docente = User::factory()->docente()->create();
        $estudiante = User::factory()->estudiante()->create();

        $this->actingAs($docente)->get(route('dece.expedientes.index'))->assertForbidden();
        $this->actingAs($estudiante)->get(route('dece.expedientes.index'))->assertForbidden();
    }

    public function test_dece_y_autoridades_acceden_al_modulo(): void
    {
        $dece = User::factory()->dece()->create();
        $rectora = User::factory()->rectora()->create();
        $vicerrectora = User::factory()->vicerrectora()->create();
        $admin = User::factory()->admin()->create();

        $this->actingAs($dece)->get(route('dece.expedientes.index'))->assertOk();
        $this->actingAs($admin)->get(route('dece.expedientes.index'))->assertOk();
        $this->actingAs($rectora)->get(route('dece.expedientes.index'))->assertOk();
        $this->actingAs($vicerrectora)->get(route('dece.expedientes.index'))->assertOk();
    }

    public function test_la_ruta_antigua_dece_ya_no_existe(): void
    {
        $dece = User::factory()->dece()->create();

        $this->actingAs($dece)->get('/dece')->assertNotFound();
    }

    public function test_dashboard_institucional_del_dece_muestra_el_panel(): void
    {
        $dece = User::factory()->dece()->create();

        $this->actingAs($dece)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Panel DECE')
            ->assertSee('Circulares publicadas')
            ->assertSee('Notificaciones');
    }

    public function test_autoridades_no_entran_a_la_gestion_clinica(): void
    {
        $rectora = User::factory()->rectora()->create();

        $this->actingAs($rectora)->get(route('dece.atenciones.index'))->assertForbidden();
        $this->actingAs($rectora)->get(route('dece.citas.index'))->assertForbidden();
        $this->actingAs($rectora)->get(route('dece.acuerdos.index'))->assertForbidden();
    }

    public function test_docente_puede_entrar_al_formulario_de_derivacion(): void
    {
        $docente = User::factory()->docente()->create();

        $this->actingAs($docente)->get(route('dece.derivaciones.create'))->assertOk();
    }

    public function test_bitacora_bloquea_actualizacion_y_borrado(): void
    {
        $auditoria = DeceAuditoria::create([
            'auditable_type' => Alumno::class,
            'auditable_id' => 1,
            'usuario_nombre' => 'Profesional DECE',
            'usuario_rol' => 'DECE',
            'accion' => DeceAuditoria::ACCION_CONSULTA,
            'detalle' => 'Consulta de prueba.',
        ]);

        try {
            $auditoria->update(['detalle' => 'Intento de cambio']);
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

        $this->assertDatabaseHas('dece_auditorias', ['detalle' => 'Consulta de prueba.']);
    }

    public function test_expediente_oculta_relatoria_a_las_autoridades(): void
    {
        [$alumno] = $this->contexto();
        $dece = User::factory()->dece()->create();
        $rectora = User::factory()->rectora()->create();

        DeceAtencion::create([
            'alumno_id' => $alumno->id,
            'profesional_id' => $dece->id,
            'fecha_atencion' => now(),
            'motivo' => 'Soporte emocional / familiar',
            'historia_narrada' => 'SECRETO_CLINICO_123 relato confidencial del estudiante.',
            'estado' => DeceAtencion::ESTADO_ABIERTA,
        ]);

        $this->actingAs($dece)
            ->get(route('dece.expedientes.show', $alumno))
            ->assertOk()
            ->assertSee('SECRETO_CLINICO_123');

        $this->actingAs($rectora)
            ->get(route('dece.expedientes.show', $alumno))
            ->assertOk()
            ->assertDontSee('SECRETO_CLINICO_123')
            ->assertSee('solo están disponibles para el equipo del DECE', false);
    }

    /**
     * @return array{0: Alumno, 1: User, 2: User}
     */
    private function contexto(): array
    {
        $institucion = Institucion::factory()->create();
        $periodo = PeriodoLectivo::factory()->create();
        $curso = Curso::factory()->create();
        $paralelo = Paralelo::factory()->create();
        $representante = User::factory()->representante()->create(['institucion_id' => $institucion->id]);

        $alumno = Alumno::factory()->create(['representante_user_id' => $representante->id]);

        Matricula::factory()->create([
            'alumno_id' => $alumno->id,
            'curso_id' => $curso->id,
            'paralelo_id' => $paralelo->id,
            'periodo_id' => $periodo->id,
        ]);

        return [$alumno, $representante, $curso];
    }
}
