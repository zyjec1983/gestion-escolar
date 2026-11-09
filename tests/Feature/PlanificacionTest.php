<?php

namespace Tests\Feature;

use App\Models\ClaseDocente;
use App\Models\Curso;
use App\Models\Institucion;
use App\Models\Materia;
use App\Models\Paralelo;
use App\Models\PeriodoLectivo;
use App\Models\PlanificacionAuditoria;
use App\Models\Unidad;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class PlanificacionTest extends TestCase
{
    use RefreshDatabase;

    private User $secretaria;

    private User $rectora;

    private User $vicerrectora;

    private User $docente;

    private User $otroDocente;

    private Materia $materia;

    private Materia $otraMateria;

    private Curso $curso;

    private Curso $otroCurso;

    private Paralelo $paralelo;

    private array $paralelos;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->secretaria = User::factory()->create(['rol' => 'SECRETARIA', 'institucion_id' => 1]);
        $this->rectora = User::factory()->create(['rol' => 'RECTORA', 'institucion_id' => 1]);
        $this->vicerrectora = User::factory()->create(['rol' => 'VICERRECTORA', 'institucion_id' => 1]);
        $this->docente = User::factory()->create(['rol' => 'DOCENTE', 'institucion_id' => 1]);
        $this->otroDocente = User::factory()->create(['rol' => 'DOCENTE', 'institucion_id' => 1]);

        $this->materia = Materia::first();
        $this->otraMateria = Materia::where('id', '!=', $this->materia->id)->first();
        $this->curso = Curso::first();
        $this->otroCurso = Curso::where('id', '!=', $this->curso->id)->first();
        $this->paralelos = Paralelo::limit(3)->pluck('id')->all();
        $this->paralelo = Paralelo::first();

        foreach ($this->paralelos as $paraleloId) {
            ClaseDocente::firstOrCreate([
                'user_id' => $this->docente->id,
                'materia_id' => $this->materia->id,
                'curso_id' => $this->curso->id,
                'paralelo_id' => $paraleloId,
            ]);
        }

        ClaseDocente::firstOrCreate([
            'user_id' => $this->otroDocente->id,
            'materia_id' => $this->otraMateria->id,
            'curso_id' => $this->otroCurso->id,
            'paralelo_id' => $this->paralelo->id,
        ]);
    }

    public function test_docente_puede_acceder_a_crear_planificaciones(): void
    {
        $this->actingAs($this->docente)
            ->get('/planificaciones/crear')
            ->assertOk()
            ->assertSee('Crear Planificación de Unidad');
    }

    public function test_autoridades_no_pueden_crear_planificaciones(): void
    {
        $totalInicial = Unidad::count();

        foreach ([$this->secretaria, $this->rectora, $this->vicerrectora] as $autoridad) {
            $this->actingAs($autoridad)->get('/planificaciones/crear')->assertForbidden();

            $this->actingAs($autoridad)->post('/planificaciones', $this->datosPlanificacion())->assertForbidden();
        }

        $this->assertSame($totalInicial, Unidad::count());
    }

    public function test_docente_crea_planificacion_y_replica_en_paralelos(): void
    {
        $periodo = PeriodoLectivo::where('activo', true)->first();

        $response = $this->actingAs($this->docente)->post('/planificaciones', $this->datosPlanificacion([
            'titulo' => 'Unit X - Ensayo argumentativo',
        ]));

        $response->assertRedirect(route('planificaciones.index'))
            ->assertSessionHas('success');

        $unidades = Unidad::where('titulo', 'Unit X - Ensayo argumentativo')->get();

        $this->assertCount(count($this->paralelos), $unidades);
        $this->assertSame(1, $unidades->pluck('grupo_id')->unique()->count());
        $this->assertSame($periodo->id, $unidades->first()->periodo_lectivo_id);
        $this->assertSame($this->docente->id, $unidades->first()->creado_por);

        foreach ($unidades as $unidad) {
            $this->assertNotNull($unidad->clase_docente_id);
            $this->assertSame($this->materia->id, $unidad->materia_id);
            $this->assertEquals('2026-10-01', $unidad->fecha_inicio->format('Y-m-d'));
            $this->assertEquals('2026-10-15', $unidad->fecha_fin->format('Y-m-d'));
            $this->assertSame(15, $unidad->duracionDias());
        }
    }

    public function test_docente_no_puede_crear_para_clases_que_no_le_pertenecen(): void
    {
        $response = $this->actingAs($this->docente)->post('/planificaciones', $this->datosPlanificacion([
            'curso_id' => $this->otroCurso->id,
        ]));

        $response->assertSessionHasErrors('curso_id');
        $this->assertSame(0, Unidad::where('titulo', 'Unit Test')->count());
    }

    public function test_replica_no_incluye_paralelos_de_otro_curso(): void
    {
        $response = $this->actingAs($this->docente)->post('/planificaciones', $this->datosPlanificacion([
            'curso_id' => $this->otroCurso->id,
            'paralelo_ids' => [$this->paralelo->id],
            'titulo' => 'Solo curso correcto',
        ]));

        $response->assertSessionHasErrors('curso_id');
        $this->assertSame(0, Unidad::where('titulo', 'Solo curso correcto')->count());
    }

    public function test_requiere_fecha_fin_posterior_al_inicio(): void
    {
        $response = $this->actingAs($this->docente)->post('/planificaciones', $this->datosPlanificacion([
            'fecha_inicio' => '2026-10-15',
            'fecha_fin' => '2026-10-01',
        ]));

        $response->assertSessionHasErrors('fecha_fin');
        $this->assertSame(0, Unidad::where('titulo', 'Unit Test')->count());
    }

    public function test_requiere_titulo_descripcion_y_paralelo(): void
    {
        $response = $this->actingAs($this->docente)->post('/planificaciones', $this->datosPlanificacion([
            'paralelo_ids' => [],
            'titulo' => '',
            'descripcion' => '',
        ]));

        $response->assertSessionHasErrors(['paralelo_ids', 'titulo', 'descripcion']);
        $this->assertSame(0, Unidad::where('titulo', 'Unit Test')->count());
    }

    public function test_listado_para_docente_muestra_creacion_y_no_acciones_de_auditoria(): void
    {
        $this->actingAs($this->docente)
            ->get('/planificaciones')
            ->assertOk()
            ->assertSee('Planificaciones de Unidad')
            ->assertSee('Nueva Planificación')
            ->assertDontSee('Revisar / Imprimir')
            ->assertDontSee('Bitácora de auditoría');
    }

    public function test_listado_para_autoridades_oculta_creacion_y_borrado(): void
    {
        $this->crearUnidadComoDocente();

        foreach ([$this->secretaria, $this->rectora, $this->vicerrectora] as $autoridad) {
            $response = $this->actingAs($autoridad)->get('/planificaciones');

            $response->assertOk()
                ->assertSee('Planificaciones Académicas - Visitas Áulicas')
                ->assertSee('Revisar / Imprimir')
                ->assertSee('Bitácora')
                ->assertDontSee('Nueva Planificación')
                ->assertDontSee('form-delete-unidad');
        }
    }

    public function test_filtros_por_materia_nivel_paralelo_y_docente(): void
    {
        $this->crearUnidadComoDocente(['titulo' => 'Unidad del docente titular']);
        $this->crearUnidadComoDocente([
            'docente' => $this->otroDocente,
            'titulo' => 'Unidad de otra materia',
            'materia_id' => $this->otraMateria->id,
            'curso_id' => $this->otroCurso->id,
            'paralelo_ids' => [$this->paralelo->id],
        ]);

        $this->actingAs($this->secretaria)
            ->get('/planificaciones?materia_id='.$this->materia->id)
            ->assertOk()
            ->assertSee('Unidad del docente titular')
            ->assertDontSee('Unidad de otra materia');

        $this->actingAs($this->secretaria)
            ->get('/planificaciones?docente_id='.$this->otroDocente->id)
            ->assertOk()
            ->assertSee('Unidad de otra materia')
            ->assertDontSee('Unidad del docente titular');

        $this->actingAs($this->secretaria)
            ->get('/planificaciones?curso_id='.$this->curso->id.'&paralelo_id='.$this->paralelos[0])
            ->assertOk()
            ->assertSee('Unidad del docente titular')
            ->assertDontSee('Unidad de otra materia');
    }

    public function test_docente_puede_editar_su_propia_planificacion(): void
    {
        $unidad = $this->crearUnidadComoDocente();

        $this->actingAs($this->docente)
            ->get("/planificaciones/{$unidad->id}/editar")
            ->assertOk()
            ->assertSee('Editar Planificación de Unidad')
            ->assertSee($unidad->titulo);
    }

    public function test_docente_no_puede_editar_ni_eliminar_planificacion_ajena(): void
    {
        $unidad = $this->crearUnidadComoDocente();

        $this->actingAs($this->otroDocente)
            ->get("/planificaciones/{$unidad->id}/editar")
            ->assertForbidden();

        $this->actingAs($this->otroDocente)
            ->put("/planificaciones/{$unidad->id}", $this->datosActualizacion())
            ->assertForbidden();

        $this->actingAs($this->otroDocente)
            ->delete("/planificaciones/{$unidad->id}")
            ->assertForbidden();

        $this->assertSame(3, Unidad::where('grupo_id', $unidad->grupo_id)->count());
    }

    public function test_autoridad_no_edita_sin_intervencion_autorizada(): void
    {
        $unidad = $this->crearUnidadComoDocente();

        foreach ([$this->secretaria, $this->rectora, $this->vicerrectora] as $autoridad) {
            $this->actingAs($autoridad)
                ->get("/planificaciones/{$unidad->id}/editar")
                ->assertForbidden();

            $this->actingAs($autoridad)
                ->put("/planificaciones/{$unidad->id}", $this->datosActualizacion())
                ->assertForbidden();
        }

        $this->assertSame($unidad->titulo, $unidad->fresh()->titulo);
        $this->assertSame(0, PlanificacionAuditoria::count());
    }

    public function test_intervencion_exige_motivo_y_no_desbloquea_sin_el(): void
    {
        $unidad = $this->crearUnidadComoDocente();

        $this->actingAs($this->secretaria)
            ->post("/planificaciones/{$unidad->id}/intervencion", ['motivo' => 'corto'])
            ->assertSessionHasErrors('motivo');

        $this->assertSame(0, PlanificacionAuditoria::count());

        $this->actingAs($this->secretaria)
            ->get("/planificaciones/{$unidad->id}/editar")
            ->assertForbidden();
    }

    public function test_intervencion_autorizada_desbloquea_edicion_y_registra_bitacora(): void
    {
        $unidad = $this->crearUnidadComoDocente();
        $motivo = 'Reemplazo por renuncia del docente titular.';

        $this->actingAs($this->secretaria)
            ->post("/planificaciones/{$unidad->id}/intervencion", ['motivo' => $motivo])
            ->assertRedirect(route('planificaciones.edit', $unidad))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('planificacion_auditorias', [
            'unidad_id' => $unidad->id,
            'grupo_id' => $unidad->grupo_id,
            'usuario_id' => $this->secretaria->id,
            'usuario_rol' => 'SECRETARIA',
            'accion' => PlanificacionAuditoria::ACCION_AUTORIZACION,
            'motivo' => $motivo,
        ]);

        $this->actingAs($this->secretaria)
            ->get("/planificaciones/{$unidad->id}/editar")
            ->assertOk()
            ->assertSee('Intervención administrativa autorizada')
            ->assertSee($motivo);

        $this->actingAs($this->secretaria)
            ->put("/planificaciones/{$unidad->id}", $this->datosActualizacion())
            ->assertRedirect(route('planificaciones.index'))
            ->assertSessionHas('success');

        foreach (Unidad::where('grupo_id', $unidad->grupo_id)->get() as $replica) {
            $this->assertSame('Unit Y - Actualizada', $replica->titulo);
            $this->assertSame('Q2', $replica->quimestre);
            $this->assertSame('P2', $replica->parcial);
            $this->assertSame('2026-11-20', $replica->fecha_fin->format('Y-m-d'));
        }

        $this->assertDatabaseHas('planificacion_auditorias', [
            'unidad_id' => $unidad->id,
            'usuario_id' => $this->secretaria->id,
            'accion' => PlanificacionAuditoria::ACCION_ACTUALIZACION,
            'motivo' => $motivo,
        ]);

        $actualizacion = PlanificacionAuditoria::where('accion', PlanificacionAuditoria::ACCION_ACTUALIZACION)->first();
        $this->assertSame('Unit R - Robust', $actualizacion->datos_anteriores['titulo']);
        $this->assertSame('Unit Y - Actualizada', $actualizacion->datos_nuevos['titulo']);
        $this->assertSame($this->secretaria->nombreCompleto(), $actualizacion->usuario_nombre);
        $this->assertNotNull($actualizacion->created_at);
    }

    public function test_autorizacion_de_intervencion_es_por_usuario_y_por_grupo(): void
    {
        $unidad = $this->crearUnidadComoDocente();

        $this->actingAs($this->secretaria)
            ->post("/planificaciones/{$unidad->id}/intervencion", [
                'motivo' => 'Reemplazo por abandono del puesto de trabajo.',
            ])
            ->assertRedirect();

        $this->actingAs($this->rectora)
            ->get("/planificaciones/{$unidad->id}/editar")
            ->assertForbidden();

        $otraUnidad = $this->crearUnidadComoDocente(['titulo' => 'Otra unidad independiente']);

        $this->actingAs($this->secretaria)
            ->get("/planificaciones/{$otraUnidad->id}/editar")
            ->assertForbidden();
    }

    public function test_docente_puede_actualizar_su_grupo_sin_registrar_intervencion(): void
    {
        $unidad = $this->crearUnidadComoDocente();

        $this->actingAs($this->docente)
            ->put("/planificaciones/{$unidad->id}", $this->datosActualizacion())
            ->assertRedirect(route('planificaciones.index'));

        $this->assertSame(3, Unidad::where('grupo_id', $unidad->grupo_id)->where('titulo', 'Unit Y - Actualizada')->count());
        $this->assertSame(0, PlanificacionAuditoria::count());
    }

    public function test_actualizacion_rechaza_fecha_fin_anterior_al_inicio(): void
    {
        $unidad = $this->crearUnidadComoDocente();

        $response = $this->actingAs($this->docente)->put("/planificaciones/{$unidad->id}", $this->datosActualizacion([
            'fecha_inicio' => '2026-12-10',
            'fecha_fin' => '2026-12-01',
        ]));

        $response->assertSessionHasErrors('fecha_fin');
    }

    public function test_docente_elimina_su_grupo_completo(): void
    {
        $unidad = $this->crearUnidadComoDocente();
        $grupoId = $unidad->grupo_id;

        $this->actingAs($this->docente)
            ->delete("/planificaciones/{$unidad->id}")
            ->assertRedirect(route('planificaciones.index'))
            ->assertSessionHas('success');

        $this->assertSame(0, Unidad::where('grupo_id', $grupoId)->count());
    }

    public function test_autoridades_no_pueden_eliminar_planificaciones(): void
    {
        $unidad = $this->crearUnidadComoDocente();

        foreach ([$this->secretaria, $this->rectora, $this->vicerrectora] as $autoridad) {
            $this->actingAs($autoridad)
                ->delete("/planificaciones/{$unidad->id}")
                ->assertForbidden();
        }

        $this->assertSame(3, Unidad::where('grupo_id', $unidad->grupo_id)->count());
    }

    public function test_bitacora_solo_para_autoridades(): void
    {
        $this->crearUnidadComoDocente();

        $this->actingAs($this->docente)->get('/planificaciones/bitacora')->assertForbidden();

        foreach ([$this->secretaria, $this->rectora, $this->vicerrectora] as $autoridad) {
            $this->actingAs($autoridad)
                ->get('/planificaciones/bitacora')
                ->assertOk()
                ->assertSee('Bitácora de auditoría');
        }
    }

    public function test_bitacora_muestra_usuario_fecha_exacta_y_motivo(): void
    {
        $unidad = $this->crearUnidadComoDocente();
        $motivo = 'Reemplazo por despido del docente titular.';

        $this->actingAs($this->rectora)
            ->post("/planificaciones/{$unidad->id}/intervencion", ['motivo' => $motivo]);

        $auditoria = PlanificacionAuditoria::firstOrFail();

        $this->actingAs($this->secretaria)
            ->get('/planificaciones/bitacora')
            ->assertOk()
            ->assertSee($unidad->titulo)
            ->assertSee($this->rectora->nombreCompleto())
            ->assertSee($motivo)
            ->assertSee($auditoria->created_at->format('d/m/Y H:i:s'))
            ->assertSee('Intervención autorizada');
    }

    public function test_impresion_disponible_para_autoridades_con_membrete(): void
    {
        $unidad = $this->crearUnidadComoDocente();
        $institucion = Institucion::first();

        $this->actingAs($this->secretaria)
            ->get("/planificaciones/{$unidad->id}/imprimir")
            ->assertOk()
            ->assertSee('Reporte de Planificación Académica - Visita Áulica')
            ->assertSee($institucion->nombre)
            ->assertSee($unidad->titulo)
            ->assertSee($unidad->descripcion)
            ->assertSee('Contenido y objetivos');
    }

    public function test_docente_solo_imprime_sus_propias_planificaciones(): void
    {
        $unidad = $this->crearUnidadComoDocente();

        $this->actingAs($this->docente)
            ->get("/planificaciones/{$unidad->id}/imprimir")
            ->assertOk();

        $this->actingAs($this->otroDocente)
            ->get("/planificaciones/{$unidad->id}/imprimir")
            ->assertForbidden();
    }

    public function test_bitacora_es_inalterable(): void
    {
        $unidad = $this->crearUnidadComoDocente();

        $this->actingAs($this->secretaria)
            ->post("/planificaciones/{$unidad->id}/intervencion", [
                'motivo' => 'Reemplazo por renuncia del docente titular.',
            ]);

        $auditoria = PlanificacionAuditoria::firstOrFail();

        try {
            $auditoria->update(['motivo' => 'Motivo alterado']);
            $this->fail('La bitácora permitió una actualización.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('inalterable', $e->getMessage());
        }

        try {
            $auditoria->delete();
            $this->fail('La bitácora permitió una eliminación.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('inalterable', $e->getMessage());
        }

        $this->assertDatabaseHas('planificacion_auditorias', [
            'id' => $auditoria->id,
            'motivo' => 'Reemplazo por renuncia del docente titular.',
        ]);
    }

    public function test_representante_no_accede_al_modulo(): void
    {
        $unidad = $this->crearUnidadComoDocente();
        $representante = User::factory()->create(['rol' => 'REPRESENTANTE', 'institucion_id' => 1]);

        $this->actingAs($representante)->get('/planificaciones/crear')->assertForbidden();
        $this->actingAs($representante)->get("/planificaciones/{$unidad->id}/editar")->assertForbidden();
        $this->actingAs($representante)->get("/planificaciones/{$unidad->id}/imprimir")->assertForbidden();
        $this->actingAs($representante)->get('/planificaciones/bitacora')->assertForbidden();
        $this->actingAs($representante)->delete("/planificaciones/{$unidad->id}")->assertForbidden();

        $this->assertSame(3, Unidad::where('grupo_id', $unidad->grupo_id)->count());
    }

    private function crearUnidadComoDocente(array $overrides = []): Unidad
    {
        $docente = $overrides['docente'] ?? $this->docente;
        $titulo = $overrides['titulo'] ?? 'Unit R - Robust';

        $datos = array_merge($this->datosPlanificacion([
            'materia_id' => $this->materia->id,
            'curso_id' => $this->curso->id,
            'paralelo_ids' => $this->paralelos,
        ]), $overrides);

        $datos['titulo'] = $titulo;
        unset($datos['docente']);

        $this->actingAs($docente)->post('/planificaciones', $datos)->assertRedirect(route('planificaciones.index'));

        return Unidad::where('titulo', $titulo)->firstOrFail();
    }

    private function datosPlanificacion(array $overrides = []): array
    {
        return array_merge([
            'materia_id' => $this->materia->id,
            'curso_id' => $this->curso->id,
            'paralelo_ids' => $this->paralelos,
            'titulo' => 'Unit Test',
            'descripcion' => 'Planificación de prueba con objetivos y contenidos.',
            'fecha_inicio' => '2026-10-01',
            'fecha_fin' => '2026-10-15',
            'quimestre' => 'Q1',
            'parcial' => 'P1',
        ], $overrides);
    }

    private function datosActualizacion(array $overrides = []): array
    {
        return array_merge([
            'titulo' => 'Unit Y - Actualizada',
            'descripcion' => 'Descripción actualizada para todas las copias.',
            'fecha_inicio' => '2026-11-01',
            'fecha_fin' => '2026-11-20',
            'quimestre' => 'Q2',
            'parcial' => 'P2',
        ], $overrides);
    }
}
