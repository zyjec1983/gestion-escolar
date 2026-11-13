<?php

namespace Tests\Feature\Dece;

use App\Models\Alumno;
use App\Models\Curso;
use App\Models\DeceAcuerdo;
use App\Models\DeceAcuerdoSeguimiento;
use App\Models\DeceAtencion;
use App\Models\DeceAuditoria;
use App\Models\DeceCita;
use App\Models\DeceDerivacion;
use App\Models\DeceDiagnostico;
use App\Models\DeceNee;
use App\Models\DeceTaller;
use App\Models\Institucion;
use App\Models\Matricula;
use App\Models\Mensaje;
use App\Models\Paralelo;
use App\Models\PeriodoLectivo;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Flujo funcional del modulo DECE: atenciones, citas, acuerdos, NEE,
 * derivaciones, talleres y reportes. Cada prueba verifica el efecto en base de
 * datos y el rastro dejado en la bitacora.
 */
class DeceGestionTest extends TestCase
{
    public function test_registrar_atencion_con_evidencia_genera_bitacora_y_pdf(): void
    {
        Storage::fake('public');

        [$alumno] = $this->contexto();
        $dece = User::factory()->dece()->create();

        $respuesta = $this->actingAs($dece)->post(route('dece.atenciones.store'), [
            'alumno_id' => $alumno->id,
            'fecha_atencion' => now()->format('Y-m-d H:i:s'),
            'motivo' => DeceAtencion::MOTIVOS[0],
            'historia_narrada' => 'El estudiante relata dificultades para concentrarse durante las evaluaciones.',
            'acuerdos' => 'Se acuerda acompanamiento semanal y seguimiento con el docente tutor.',
            'evidencias' => [
                UploadedFile::fake()->create('informe.pdf', 120, 'application/pdf'),
            ],
        ]);

        $atencion = DeceAtencion::firstOrFail();

        $respuesta->assertRedirect(route('dece.atenciones.show', $atencion));

        $this->assertDatabaseHas('dece_atenciones', [
            'id' => $atencion->id,
            'alumno_id' => $alumno->id,
            'profesional_id' => $dece->id,
            'estado' => DeceAtencion::ESTADO_ABIERTA,
            'confidencial' => true,
        ]);

        $this->assertDatabaseHas('dece_archivos', [
            'archivoable_type' => DeceAtencion::class,
            'archivoable_id' => $atencion->id,
            'nombre_original' => 'informe.pdf',
            'subido_por' => $dece->id,
        ]);

        $this->assertDatabaseHas('dece_auditorias', [
            'auditable_type' => DeceAtencion::class,
            'auditable_id' => $atencion->id,
            'accion' => DeceAuditoria::ACCION_CREACION,
        ]);

        $this->assertDatabaseHas('dece_auditorias', [
            'auditable_type' => DeceAtencion::class,
            'auditable_id' => $atencion->id,
            'accion' => DeceAuditoria::ACCION_ARCHIVO,
        ]);

        $this->actingAs($dece)
            ->get(route('dece.atenciones.pdf', $atencion))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_cerrar_atencion_cambia_estado_y_audita(): void
    {
        [$alumno] = $this->contexto();
        $dece = User::factory()->dece()->create();

        $atencion = DeceAtencion::create([
            'alumno_id' => $alumno->id,
            'profesional_id' => $dece->id,
            'fecha_atencion' => now(),
            'motivo' => DeceAtencion::MOTIVOS[0],
            'historia_narrada' => 'Relato inicial de la entrevista de seguimiento.',
            'estado' => DeceAtencion::ESTADO_ABIERTA,
        ]);

        $this->actingAs($dece)
            ->post(route('dece.atenciones.cerrar', $atencion))
            ->assertRedirect();

        $this->assertSame(DeceAtencion::ESTADO_CERRADA, $atencion->fresh()->estado);
        $this->assertDatabaseHas('dece_auditorias', [
            'auditable_type' => DeceAtencion::class,
            'auditable_id' => $atencion->id,
            'accion' => DeceAuditoria::ACCION_CIERRE,
        ]);
    }

    public function test_agendar_cita_notifica_al_representante(): void
    {
        [$alumno] = $this->contexto();
        $dece = User::factory()->dece()->create();

        $this->actingAs($dece)->post(route('dece.citas.store'), [
            'alumno_id' => $alumno->id,
            'fecha_hora' => now()->addDays(2)->format('Y-m-d H:i:s'),
            'duracion_minutos' => 30,
            'motivo' => 'Entrevista por bajo rendimiento academico',
            'observaciones' => 'Traer el cuaderno de trabajo.',
        ])->assertRedirect(route('dece.citas.index'));

        $cita = DeceCita::firstOrFail();

        $this->assertNotNull($cita->notificado_en);
        $this->assertSame($alumno->representante_user_id, $cita->representante_user_id);

        $this->assertDatabaseHas('mensajes', [
            'destinatario_id' => $alumno->representante_user_id,
            'emisor_id' => $dece->id,
            'contexto_estudiante_id' => $alumno->id,
        ]);

        $mensaje = Mensaje::where('destinatario_id', $alumno->representante_user_id)->firstOrFail();
        $this->assertStringContainsString('Citación del DECE', $mensaje->asunto);

        $this->assertDatabaseHas('dece_auditorias', [
            'auditable_type' => DeceCita::class,
            'auditable_id' => $cita->id,
            'accion' => DeceAuditoria::ACCION_NOTIFICACION,
        ]);
    }

    public function test_agendar_cita_rechaza_fechas_pasadas(): void
    {
        [$alumno] = $this->contexto();
        $dece = User::factory()->dece()->create();

        $this->actingAs($dece)->post(route('dece.citas.store'), [
            'alumno_id' => $alumno->id,
            'fecha_hora' => now()->subDay()->format('Y-m-d H:i:s'),
            'duracion_minutos' => 30,
            'motivo' => 'Cita vencida',
        ])->assertSessionHasErrors('fecha_hora');

        $this->assertSame(0, DeceCita::count());
    }

    public function test_acta_solo_se_emite_con_las_tres_firmas(): void
    {
        [$alumno] = $this->contexto();
        $dece = User::factory()->dece()->create();

        $acuerdo = $this->acuerdo($alumno, $dece);

        $this->actingAs($dece)
            ->get(route('dece.acuerdos.pdf', $acuerdo))
            ->assertRedirect()
            ->assertSessionHas('error');

        foreach (['estudiante', 'representante', 'profesional'] as $firma) {
            $this->actingAs($dece)
                ->post(route('dece.acuerdos.firmar', $acuerdo), ['firma' => $firma])
                ->assertRedirect();
        }

        $acuerdo->refresh();

        $this->assertTrue($acuerdo->firmadoCompleto());
        $this->assertSame(3, $acuerdo->firmasRegistradas());
        $this->assertSame(3, DeceAuditoria::where('auditable_type', DeceAcuerdo::class)
            ->where('auditable_id', $acuerdo->id)
            ->where('accion', DeceAuditoria::ACCION_FIRMA)
            ->count());

        $this->actingAs($dece)
            ->get(route('dece.acuerdos.pdf', $acuerdo))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_seguimiento_actualiza_el_estado_del_acuerdo(): void
    {
        [$alumno] = $this->contexto();
        $dece = User::factory()->dece()->create();
        $acuerdo = $this->acuerdo($alumno, $dece);

        $this->actingAs($dece)->post(route('dece.acuerdos.seguimiento', $acuerdo), [
            'fecha' => now()->toDateString(),
            'estado' => DeceAcuerdoSeguimiento::ESTADO_CUMPLIDO,
            'observacion' => 'El estudiante cumplio los compromisos acordados.',
        ])->assertRedirect();

        $this->assertSame(DeceAcuerdo::ESTADO_CUMPLIDO, $acuerdo->fresh()->estado);
        $this->assertDatabaseHas('dece_acuerdo_seguimientos', [
            'acuerdo_id' => $acuerdo->id,
            'estado' => DeceAcuerdoSeguimiento::ESTADO_CUMPLIDO,
            'registrado_por' => $dece->id,
        ]);
        $this->assertDatabaseHas('dece_auditorias', [
            'auditable_type' => DeceAcuerdoSeguimiento::class,
            'accion' => DeceAuditoria::ACCION_SEGUIMIENTO,
        ]);
    }

    public function test_acuerdos_vencidos_se_marcan_al_abrir_el_panel(): void
    {
        [$alumno] = $this->contexto();
        $dece = User::factory()->dece()->create();

        $acuerdo = DeceAcuerdo::create([
            'alumno_id' => $alumno->id,
            'profesional_id' => $dece->id,
            'tipo' => DeceAcuerdo::TIPO_CONDUCTUAL,
            'descripcion' => 'Compromiso de convivencia vencido para probar el barrido automatico.',
            'fecha_inicio' => now()->subMonth()->toDateString(),
            'fecha_compromiso' => now()->subDay()->toDateString(),
            'estado' => DeceAcuerdo::ESTADO_ACTIVO,
        ]);

        $this->actingAs($dece)->get(route('dashboard'))->assertOk();

        $this->assertSame(DeceAcuerdo::ESTADO_VENCIDO, $acuerdo->fresh()->estado);
    }

    public function test_registro_nee_interno_y_externo(): void
    {
        [$alumno] = $this->contexto();
        $dece = User::factory()->dece()->create();
        $diagnostico = DeceDiagnostico::create([
            'codigo' => 'F81.0',
            'nombre' => 'Dislexia',
            'categoria' => DeceDiagnostico::CATEGORIAS[0],
        ]);

        $this->actingAs($dece)->post(route('dece.nee.store'), [
            'procedencia' => DeceNee::PROCEDENCIA_INTERNO,
            'alumno_id' => $alumno->id,
            'diagnostico_id' => $diagnostico->id,
            'grado_adaptacion' => 2,
            'requiere_informe' => 1,
            'observaciones' => 'Requiere adaptaciones en evaluaciones escritas.',
        ])->assertRedirect();

        $interno = DeceNee::where('procedencia', DeceNee::PROCEDENCIA_INTERNO)->firstOrFail();
        $this->assertSame($alumno->id, $interno->alumno_id);
        $this->assertTrue($interno->activo);

        $this->actingAs($dece)->post(route('dece.nee.store'), [
            'procedencia' => DeceNee::PROCEDENCIA_EXTERNO,
            'nombre_externo' => 'Aspirante Externo',
            'curso_externo' => 'Octavo grado',
            'diagnostico_detalle' => 'Diagnostico emitido por el MSP.',
        ])->assertRedirect();

        $externo = DeceNee::where('procedencia', DeceNee::PROCEDENCIA_EXTERNO)->firstOrFail();
        $this->assertNull($externo->alumno_id);
        $this->assertSame('Aspirante Externo', $externo->nombre_externo);
        $this->assertSame('Octavo grado', $externo->curso_externo);
    }

    public function test_registro_nee_externo_exige_nombre_y_curso(): void
    {
        $dece = User::factory()->dece()->create();

        $this->actingAs($dece)->post(route('dece.nee.store'), [
            'procedencia' => DeceNee::PROCEDENCIA_EXTERNO,
        ])->assertSessionHasErrors(['nombre_externo', 'curso_externo']);

        $this->assertSame(0, DeceNee::count());
    }

    public function test_docente_solo_consulta_sus_propias_derivaciones(): void
    {
        [$alumno] = $this->contexto();
        $docente = User::factory()->docente()->create();
        $otroDocente = User::factory()->docente()->create();
        $dece = User::factory()->dece()->create();

        $this->actingAs($docente)->post(route('dece.derivaciones.store'), [
            'alumno_id' => $alumno->id,
            'tipo' => DeceDerivacion::TIPO_INTERNA,
            'motivo' => 'Cambio de conducta en clase',
            'descripcion' => 'El estudiante presenta aislamiento y bajo rendimiento desde hace dos semanas.',
            'fecha_derivacion' => now()->toDateString(),
        ])->assertRedirect();

        $derivacion = DeceDerivacion::firstOrFail();

        $this->assertSame($docente->id, $derivacion->derivado_por);
        $this->assertSame(DeceDerivacion::ESTADO_PENDIENTE, $derivacion->estado);

        $this->actingAs($docente)
            ->get(route('dece.derivaciones.show', $derivacion))
            ->assertOk();

        $this->actingAs($otroDocente)
            ->get(route('dece.derivaciones.show', $derivacion))
            ->assertForbidden();

        $this->actingAs($dece)->post(route('dece.derivaciones.update', $derivacion), [
            'estado' => DeceDerivacion::ESTADO_ATENDIDA,
            'respuesta' => 'Se realizo la entrevista y se coordino con el representante.',
        ])->assertRedirect();

        $this->assertSame(DeceDerivacion::ESTADO_ATENDIDA, $derivacion->fresh()->estado);
    }

    public function test_buscador_de_estudiantes_devuelve_coincidencias_con_representante(): void
    {
        [$alumno, $representante] = $this->contexto();
        $alumno->update([
            'nombres' => 'Ana María',
            'apellidos' => 'Zambrano Ruiz',
            'numero_cedula' => '0912345678',
        ]);
        $dece = User::factory()->dece()->create();

        $respuesta = $this->actingAs($dece)
            ->getJson(route('dece.buscar.alumnos', ['q' => 'zambrano']))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $alumno->id)
            ->assertJsonPath('data.0.representante.id', $representante->id);

        $this->assertStringContainsString('Zambrano', $respuesta->json('data.0.nombre'));
        $this->assertSame('0912345678', $respuesta->json('data.0.cedula'));
    }

    public function test_buscador_carga_citas_y_atenciones_del_estudiante(): void
    {
        [$alumno] = $this->contexto();
        $dece = User::factory()->dece()->create();

        $cita = DeceCita::create([
            'alumno_id' => $alumno->id,
            'representante_user_id' => $alumno->representante_user_id,
            'profesional_id' => $dece->id,
            'fecha_hora' => now()->addDay(),
            'duracion_minutos' => 30,
            'motivo' => 'Entrevista de seguimiento',
            'estado' => DeceCita::ESTADO_PROGRAMADA,
        ]);

        $atencion = DeceAtencion::create([
            'alumno_id' => $alumno->id,
            'profesional_id' => $dece->id,
            'fecha_atencion' => now(),
            'motivo' => DeceAtencion::MOTIVOS[0],
            'historia_narrada' => 'Primera entrevista de seguimiento academico.',
            'estado' => DeceAtencion::ESTADO_ABIERTA,
        ]);

        $this->actingAs($dece)
            ->getJson(route('dece.buscar.citas', $alumno))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $cita->id)
            ->assertJsonPath('data.0.motivo', 'Entrevista de seguimiento');

        $this->actingAs($dece)
            ->getJson(route('dece.buscar.atenciones', $alumno))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $atencion->id)
            ->assertJsonPath('data.0.estado', 'Abierta');
    }

    public function test_formularios_de_alta_usan_buscador_de_estudiantes(): void
    {
        $dece = User::factory()->dece()->create();

        $rutas = [
            'dece.atenciones.create',
            'dece.citas.create',
            'dece.acuerdos.create',
            'dece.derivaciones.create',
            'dece.nee.create',
        ];

        foreach ($rutas as $ruta) {
            $this->actingAs($dece)
                ->get(route($ruta))
                ->assertOk()
                ->assertSee('data-buscador-alumno', false)
                ->assertDontSee('<select name="alumno_id"', false);
        }
    }

    public function test_los_listados_dece_muestran_buscador_con_sugerencias(): void
    {
        $dece = User::factory()->dece()->create();

        $rutas = [
            'dece.expedientes.index',
            'dece.atenciones.index',
            'dece.citas.index',
            'dece.acuerdos.index',
            'dece.derivaciones.index',
            'dece.nee.index',
            'dece.talleres.index',
        ];

        foreach ($rutas as $ruta) {
            $this->actingAs($dece)
                ->get(route($ruta))
                ->assertOk()
                ->assertSee('data-buscador-filtro', false);
        }
    }

    public function test_filtro_por_alumno_id_ignora_el_texto_de_busqueda(): void
    {
        [$alumno] = $this->contexto();
        $otro = Alumno::factory()->create([
            'nombres' => 'Otro',
            'apellidos' => 'Distinto',
        ]);
        $dece = User::factory()->dece()->create();

        foreach ([$alumno, $otro] as $estudiante) {
            DeceAtencion::create([
                'alumno_id' => $estudiante->id,
                'profesional_id' => $dece->id,
                'fecha_atencion' => now(),
                'motivo' => DeceAtencion::MOTIVOS[0],
                'historia_narrada' => 'Entrevista de prueba para el filtro por estudiante.',
                'estado' => DeceAtencion::ESTADO_ABIERTA,
            ]);
        }

        $this->actingAs($dece)
            ->get(route('dece.atenciones.index', [
                'alumno_id' => $alumno->id,
                'buscar' => 'Distinto',
            ]))
            ->assertOk()
            ->assertSee($alumno->nombreCompleto())
            ->assertDontSee($otro->nombreCompleto());
    }

    public function test_buscador_de_talleres_devuelve_coincidencias_por_titulo_facilitador_o_lugar(): void
    {
        $dece = User::factory()->dece()->create();

        $taller = DeceTaller::create([
            'titulo' => 'Escuela para familias',
            'tipo' => 'escuela_de_padres',
            'descripcion' => 'Encuentro con representantes para fortalecer el acompanamiento.',
            'fecha' => now()->toDateString(),
            'dirigido_a' => 'representantes',
            'facilitador' => 'Equipo DECE',
            'lugar' => 'Auditorio principal',
            'created_by' => $dece->id,
        ]);

        $this->actingAs($dece)
            ->getJson(route('dece.buscar.talleres', ['q' => 'auditorio']))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $taller->id)
            ->assertJsonPath('data.0.nombre', 'Escuela para familias');
    }

    public function test_derivacion_genera_documento_pdf_imprimible(): void
    {
        [$alumno] = $this->contexto();
        $dece = User::factory()->dece()->create();

        $derivacion = DeceDerivacion::create([
            'alumno_id' => $alumno->id,
            'tipo' => DeceDerivacion::TIPO_INTERNA,
            'motivo' => 'Seguimiento academico',
            'descripcion' => 'El equipo docente solicita acompanamiento del DECE para el estudiante.',
            'derivado_por' => $dece->id,
            'fecha_derivacion' => now()->toDateString(),
            'estado' => DeceDerivacion::ESTADO_PENDIENTE,
        ]);

        $this->actingAs($dece)
            ->get(route('dece.derivaciones.pdf', $derivacion))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this->assertDatabaseHas('dece_auditorias', [
            'auditable_type' => DeceDerivacion::class,
            'auditable_id' => $derivacion->id,
            'accion' => DeceAuditoria::ACCION_CONSULTA,
        ]);
    }

    public function test_taller_registra_asistencia_de_estudiantes(): void
    {
        [$alumno, , $curso] = $this->contexto();
        $dece = User::factory()->dece()->create();

        $this->actingAs($dece)->post(route('dece.talleres.store'), [
            'titulo' => 'Taller de prevencion del acoso escolar',
            'tipo' => 'prevencion',
            'descripcion' => 'Jornada de sensibilizacion con dinámicas grupales.',
            'fecha' => now()->toDateString(),
            'dirigido_a' => 'estudiantes',
            'curso_id' => $curso->id,
            'facilitador' => 'Equipo DECE',
            'lugar' => 'Auditorio',
        ])->assertRedirect();

        $taller = DeceTaller::firstOrFail();

        $this->actingAs($dece)->post(route('dece.talleres.asistencia', $taller), [
            'alumno_ids' => [$alumno->id],
        ])->assertRedirect();

        $this->assertDatabaseHas('dece_taller_asistentes', [
            'taller_id' => $taller->id,
            'alumno_id' => $alumno->id,
            'asistio' => true,
        ]);
        $this->assertDatabaseHas('dece_auditorias', [
            'auditable_type' => DeceTaller::class,
            'auditable_id' => $taller->id,
            'accion' => DeceAuditoria::ACCION_ACTUALIZACION,
        ]);
    }

    public function test_reportes_agregan_sin_exponer_relatorias(): void
    {
        [$alumno] = $this->contexto();
        $dece = User::factory()->dece()->create();
        $rectora = User::factory()->rectora()->create();

        DeceAtencion::create([
            'alumno_id' => $alumno->id,
            'profesional_id' => $dece->id,
            'fecha_atencion' => now(),
            'motivo' => DeceAtencion::MOTIVOS[0],
            'historia_narrada' => 'RELATO_CONFIDENCIAL_XYZ que no debe salir en el reporte agregado.',
            'estado' => DeceAtencion::ESTADO_ABIERTA,
        ]);

        $this->actingAs($rectora)
            ->get(route('dece.reportes.index'))
            ->assertOk()
            ->assertDontSee('RELATO_CONFIDENCIAL_XYZ');

        $this->actingAs($rectora)
            ->get(route('dece.reportes.pdf'))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_catalogo_de_diagnosticos_se_gestiona_solo_desde_el_dece(): void
    {
        $dece = User::factory()->dece()->create();
        $rectora = User::factory()->rectora()->create();

        $this->actingAs($dece)->post(route('dece.diagnosticos.store'), [
            'codigo' => 'F90.9',
            'nombre' => 'Trastorno hipercinetico no especificado',
            'categoria' => DeceDiagnostico::CATEGORIAS[0],
        ])->assertRedirect();

        $diagnostico = DeceDiagnostico::where('codigo', 'F90.9')->firstOrFail();
        $this->assertTrue($diagnostico->activo);

        $this->actingAs($dece)->post(route('dece.diagnosticos.toggle', $diagnostico))->assertRedirect();
        $this->assertFalse($diagnostico->fresh()->activo);

        $this->actingAs($rectora)->post(route('dece.diagnosticos.store'), [
            'codigo' => 'Z99',
            'nombre' => 'No permitido',
            'categoria' => DeceDiagnostico::CATEGORIAS[0],
        ])->assertForbidden();
    }

    /**
     * @return array{0: Alumno, 1: User, 2: Curso}
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

    private function acuerdo(Alumno $alumno, User $profesional): DeceAcuerdo
    {
        return DeceAcuerdo::create([
            'alumno_id' => $alumno->id,
            'profesional_id' => $profesional->id,
            'tipo' => DeceAcuerdo::TIPO_ACADEMICO,
            'descripcion' => 'El estudiante se compromete a cumplir con las tareas y asistir a refuerzo.',
            'compromiso_estudiante' => 'Asistir puntualmente a las clases de refuerzo.',
            'compromiso_representante' => 'Supervisar el cumplimiento diario en casa.',
            'fecha_inicio' => now()->toDateString(),
            'fecha_compromiso' => now()->addMonth()->toDateString(),
            'estado' => DeceAcuerdo::ESTADO_ACTIVO,
        ]);
    }
}
