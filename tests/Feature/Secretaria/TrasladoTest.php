<?php

namespace Tests\Feature\Secretaria;

use App\Models\Alumno;
use App\Models\Curso;
use App\Models\ExpedienteAuditoria;
use App\Models\ExpedienteDocumento;
use App\Models\ExpedienteTraslado;
use App\Models\HistorialExternoNota;
use App\Models\HistorialExternoPromocion;
use App\Models\Institucion;
use App\Models\Matricula;
use App\Models\Paralelo;
use App\Models\PeriodoLectivo;
use App\Models\User;
use App\Services\ChecklistTraslado;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

/**
 * Modulo de traslados e historial academico: checklist dinamico (RF-02),
 * reglas RN-01 a RN-05, documentos, homologacion, cierre inmutable y PDFs.
 */
class TrasladoTest extends TestCase
{
    use RefreshDatabase;

    private User $secretaria;

    private PeriodoLectivo $periodo;

    private Curso $curso;

    private Paralelo $paralelo;

    private Alumno $alumno;

    protected function setUp(): void
    {
        parent::setUp();

        Institucion::factory()->create();
        $this->periodo = PeriodoLectivo::factory()->create(['activo' => true]);
        $this->secretaria = User::factory()->secretaria()->create();

        $this->curso = Curso::factory()->create(['nombre' => 'NOVENO GRADO']);
        $this->paralelo = Paralelo::factory()->create();

        $representanteAdulto = User::factory()->representante()->create([
            'fecha_nacimiento' => now()->subYears(40)->toDateString(),
        ]);
        $this->alumno = Alumno::factory()->create([
            'representante_user_id' => $representanteAdulto->id,
        ]);
    }

    private function crearExpediente(array $cambios = []): ExpedienteTraslado
    {
        return ExpedienteTraslado::create(array_merge([
            'alumno_id' => $this->alumno->id,
            'curso_id' => $this->curso->id,
            'paralelo_id' => $this->paralelo->id,
            'tipo' => ExpedienteTraslado::TIPO_INGRESO,
            'momento' => ExpedienteTraslado::MOMENTO_INICIO,
            'fecha_traslado' => '2026-08-15',
            'institucion' => 'COLEGIO DE ORIGEN',
            'anio_lectivo' => '2026 - 2027',
            'estado' => ExpedienteTraslado::ESTADO_POR_REGULARIZAR,
            'documentos_completos' => false,
            'creado_por' => $this->secretaria->id,
        ], $cambios));
    }

    /** @test */
    public function solo_secretaria_y_admin_acceden_al_modulo_de_traslados(): void
    {
        $this->actingAs($this->secretaria)
            ->get(route('traslados.index'))
            ->assertOk()
            ->assertSee('Traslados e historial académico');

        $this->actingAs($this->secretaria)
            ->get(route('traslados.create'))
            ->assertOk()
            ->assertSee('Nuevo registro de traslado');

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('traslados.index'))
            ->assertOk();

        $this->actingAs(User::factory()->docente()->create())
            ->get(route('traslados.index'))
            ->assertForbidden();

        $this->actingAs(User::factory()->admin()->create(['rol' => 'RECTORA']))
            ->get(route('traslados.index'))
            ->assertForbidden();
    }

    /** @test */
    public function el_checklist_cambia_segun_momento_y_aplica_rn_01(): void
    {
        $checklist = app(ChecklistTraslado::class);

        $inicio = collect($checklist->requisitos('ingreso', 'inicio', null, true))->keyBy('clave');
        $this->assertTrue($inicio['pase_ano']['obligatorio']);
        $this->assertTrue($inicio['paz_salvo_origen']['obligatorio']);
        $this->assertArrayNotHasKey('libreta_parcial', $inicio->all());
        $this->assertArrayNotHasKey('registro_asistencia', $inicio->all());

        $conPeriodo = collect($checklist->requisitos('ingreso', 'transcurso', null, true))->keyBy('clave');
        $this->assertTrue($conPeriodo['libreta_parcial']['obligatorio']);
        $this->assertTrue($conPeriodo['registro_asistencia']['obligatorio']);

        $sinPeriodo = collect($checklist->requisitos('ingreso', 'transcurso', null, false))->keyBy('clave');
        $this->assertFalse($sinPeriodo['libreta_parcial']['obligatorio']);
        $this->assertFalse($sinPeriodo['registro_asistencia']['obligatorio']);
    }

    /** @test */
    public function octavo_ano_exige_certificado_de_terminacion_de_septimo(): void
    {
        $octavo = new Curso(['nombre' => 'OCTAVO GRADO']);

        $requisitos = collect(app(ChecklistTraslado::class)
            ->requisitos('ingreso', 'inicio', $octavo, true))->keyBy('clave');

        $this->assertArrayHasKey('certificado_terminacion_7mo', $requisitos->all());
        $this->assertTrue($requisitos['certificado_terminacion_7mo']['obligatorio']);

        $noveno = new Curso(['nombre' => 'NOVENO GRADO']);
        $sinOctavo = collect(app(ChecklistTraslado::class)
            ->requisitos('ingreso', 'inicio', $noveno, true))->keyBy('clave');
        $this->assertArrayNotHasKey('certificado_terminacion_7mo', $sinOctavo->all());
    }

    /** @test */
    public function el_formulario_usa_buscador_de_estudiantes_en_lugar_de_un_select(): void
    {
        $this->actingAs($this->secretaria)
            ->get(route('traslados.create'))
            ->assertOk()
            ->assertSee('Buscar estudiante')
            ->assertSee('id="buscarAlumno"', false)
            ->assertDontSee('<select name="alumno_id"', false);
    }

    /** @test */
    public function el_buscador_devuelve_coincidencias_y_exige_al_menos_dos_caracteres(): void
    {
        $otro = Alumno::factory()->create([
            'nombres' => 'Ana Lucía',
            'apellidos' => 'Paredes Molina',
            'numero_cedula' => '0102030405',
        ]);

        $this->actingAs($this->secretaria)
            ->getJson(route('traslados.buscar-alumnos', ['q' => 'paredes']))
            ->assertOk()
            ->assertJsonPath('data.0.id', $otro->id)
            ->assertJsonPath('data.0.nombre', 'Paredes Molina Ana Lucía')
            ->assertJsonPath('data.0.cedula', '0102030405');

        $this->actingAs($this->secretaria)
            ->getJson(route('traslados.buscar-alumnos', ['q' => '0102030405']))
            ->assertOk()
            ->assertJsonPath('data.0.id', $otro->id);

        // La busqueda ignora tildes y mayusculas en ambos sentidos.
        $acentuado = Alumno::factory()->create([
            'nombres' => 'Verónica Alejandra',
            'apellidos' => 'Muñoz Peña',
            'numero_cedula' => '1717171717',
        ]);

        $this->actingAs($this->secretaria)
            ->getJson(route('traslados.buscar-alumnos', ['q' => 'veronica']))
            ->assertOk()
            ->assertJsonPath('data.0.id', $acentuado->id);

        $this->actingAs($this->secretaria)
            ->getJson(route('traslados.buscar-alumnos', ['q' => 'MUNOZ']))
            ->assertOk()
            ->assertJsonPath('data.0.id', $acentuado->id);

        $this->actingAs($this->secretaria)
            ->getJson(route('traslados.buscar-alumnos', ['q' => 'Peña']))
            ->assertOk()
            ->assertJsonPath('data.0.id', $acentuado->id);

        $this->actingAs($this->secretaria)
            ->getJson(route('traslados.buscar-alumnos', ['q' => 'a']))
            ->assertOk()
            ->assertJsonCount(0, 'data');

        $this->actingAs($this->secretaria)
            ->getJson(route('traslados.buscar-alumnos', ['q' => '%_']))
            ->assertOk()
            ->assertJsonCount(0, 'data');

        $this->actingAs(User::factory()->docente()->create())
            ->getJson(route('traslados.buscar-alumnos', ['q' => 'paredes']))
            ->assertForbidden();
    }

    /** @test */
    public function el_registro_crea_expediente_matricula_provisional_y_bitacora(): void
    {
        $response = $this->actingAs($this->secretaria)
            ->post(route('traslados.store'), [
                'tipo' => 'ingreso',
                'momento' => 'inicio',
                'modo_alumno' => 'existente',
                'alumno_id' => $this->alumno->id,
                'fecha_traslado' => '2026-08-15',
                'institucion' => 'Colegio de Origen',
                'anio_lectivo' => '2026 - 2027',
                'curso_id' => $this->curso->id,
                'paralelo_id' => $this->paralelo->id,
                'observaciones' => 'Ingreso al inicio del año.',
            ])
            ->assertSessionHasNoErrors();

        $expediente = ExpedienteTraslado::firstOrFail();
        $response->assertRedirect(route('traslados.show', $expediente));

        $this->assertSame(ExpedienteTraslado::ESTADO_POR_REGULARIZAR, $expediente->estado);
        $this->assertFalse($expediente->documentos_completos);
        $this->assertSame($this->alumno->id, $expediente->alumno_id);
        $this->assertSame('COLEGIO DE ORIGEN', $expediente->institucion);

        $matricula = Matricula::where('alumno_id', $this->alumno->id)->where('periodo_id', $this->periodo->id)->first();
        $this->assertNotNull($matricula);
        $this->assertSame(Matricula::ESTADO_PROVISIONAL, $matricula->estado);

        $this->assertDatabaseHas('expediente_auditorias', [
            'expediente_id' => $expediente->id,
            'usuario_id' => $this->secretaria->id,
            'accion' => ExpedienteAuditoria::ACCION_CREACION,
        ]);
    }

    /** @test */
    public function los_documentos_se_almacenan_y_al_completar_el_expediente_pasa_a_definitiva(): void
    {
        Storage::fake('public');

        $expediente = $this->crearExpediente();
        $requisitos = app(ChecklistTraslado::class)
            ->requisitos('ingreso', 'inicio', $this->curso, true);

        foreach ($requisitos as $requisito) {
            $archivo = UploadedFile::fake()->create($requisito['clave'].'.pdf', 80, 'application/pdf');

            $this->actingAs($this->secretaria)
                ->post(route('traslados.documentos.store', $expediente), [
                    'tipo_documento' => $requisito['clave'],
                    'archivo' => $archivo,
                ])
                ->assertSessionHasNoErrors();
        }

        $expediente->refresh();
        $this->assertTrue($expediente->documentos_completos);
        $this->assertSame(ExpedienteTraslado::ESTADO_DEFINITIVA, $expediente->estado);
        $this->assertSame(count($requisitos), ExpedienteDocumento::count());

        foreach (ExpedienteDocumento::all() as $documento) {
            Storage::disk('public')->assertExists($documento->ruta);
        }

        $this->assertDatabaseHas('expediente_auditorias', [
            'expediente_id' => $expediente->id,
            'accion' => ExpedienteAuditoria::ACCION_DOCUMENTO_SUBIDO,
        ]);
    }

    /** @test */
    public function se_rechazan_archivos_invalidos_y_tipos_fuera_del_checklist(): void
    {
        Storage::fake('public');
        $expediente = $this->crearExpediente();

        $this->actingAs($this->secretaria)
            ->post(route('traslados.documentos.store', $expediente), [
                'tipo_documento' => 'pase_ano',
                'archivo' => UploadedFile::fake()->create('virus.exe', 10, 'application/octet-stream'),
            ])
            ->assertSessionHasErrors('archivo');

        $this->actingAs($this->secretaria)
            ->post(route('traslados.documentos.store', $expediente), [
                'tipo_documento' => 'tipo_inventado',
                'archivo' => UploadedFile::fake()->create('doc.pdf', 10, 'application/pdf'),
            ])
            ->assertSessionHasErrors('tipo_documento');

        $this->assertDatabaseCount('expediente_documentos', 0);
    }

    /** @test */
    public function no_se_puede_cerrar_un_expediente_con_documentos_pendientes(): void
    {
        $expediente = $this->crearExpediente();

        $this->actingAs($this->secretaria)
            ->post(route('traslados.cerrar', $expediente))
            ->assertSessionHasErrors('expediente');

        $expediente->refresh();
        $this->assertSame(ExpedienteTraslado::ESTADO_POR_REGULARIZAR, $expediente->estado);
        $this->assertNull($expediente->cerrado_at);
    }

    /** @test */
    public function al_cerrar_el_expediente_el_historial_es_inmutable(): void
    {
        $expediente = $this->crearExpediente([
            'documentos_completos' => true,
            'estado' => ExpedienteTraslado::ESTADO_DEFINITIVA,
        ]);

        $matricula = Matricula::factory()->create([
            'alumno_id' => $this->alumno->id,
            'curso_id' => $this->curso->id,
            'paralelo_id' => $this->paralelo->id,
            'periodo_id' => $this->periodo->id,
            'estado' => Matricula::ESTADO_PROVISIONAL,
        ]);
        $expediente->update(['matricula_id' => $matricula->id]);

        $promocion = HistorialExternoPromocion::create([
            'expediente_id' => $expediente->id,
            'anio_lectivo' => '2025 - 2026',
            'nivel' => 'NOVENO',
            'promedio' => 8.5,
            'escala' => '0-10',
        ]);

        // El cierre recalcula el checklist, por lo que los documentos deben
        // existir realmente en el expediente (no basta con el booleano).
        $requisitos = app(ChecklistTraslado::class)
            ->requisitos('ingreso', 'inicio', $this->curso, true);

        foreach ($requisitos as $requisito) {
            ExpedienteDocumento::create([
                'expediente_id' => $expediente->id,
                'tipo_documento' => $requisito['clave'],
                'nombre_original' => $requisito['clave'].'.pdf',
                'ruta' => 'expedientes/'.$this->alumno->id.'/'.$requisito['clave'].'.pdf',
                'mime' => 'application/pdf',
                'tamano' => 1024,
                'subido_por' => $this->secretaria->id,
            ]);
        }

        $this->actingAs($this->secretaria)
            ->post(route('traslados.cerrar', $expediente))
            ->assertSessionHasNoErrors();

        $expediente->refresh();
        $this->assertSame(ExpedienteTraslado::ESTADO_CERRADO, $expediente->estado);
        $this->assertNotNull($expediente->cerrado_at);
        $this->assertSame(Matricula::ESTADO_DEFINITIVA, $matricula->fresh()->estado);

        $this->actingAs($this->secretaria)
            ->put(route('traslados.update', $expediente), [
                'momento' => 'inicio',
                'fecha_traslado' => '2026-08-15',
                'institucion' => 'OTRA INSTITUCION',
                'anio_lectivo' => '2026 - 2027',
            ])
            ->assertForbidden();

        $this->actingAs($this->secretaria)
            ->post(route('traslados.promociones.store', $expediente), [
                'anio_lectivo' => '2025 - 2026',
                'nivel' => 'DECIMO',
                'promedio' => 9,
                'escala' => '0-10',
            ])
            ->assertForbidden();

        $this->assertThrows(
            fn () => $promocion->update(['promedio' => 10]),
            RuntimeException::class
        );
        $this->assertThrows(
            fn () => $promocion->delete(),
            RuntimeException::class
        );
    }

    /** @test */
    public function el_certificado_de_salida_se_bloquea_por_deudas_o_documentos_pendientes(): void
    {
        $expediente = $this->crearExpediente([
            'tipo' => ExpedienteTraslado::TIPO_SALIDA,
            'momento' => null,
            'institucion' => 'COLEGIO DE DESTINO',
            'documentos_completos' => true,
            'paz_y_salvo_financiero' => false,
            'paz_y_salvo_biblioteca' => true,
        ]);

        $this->actingAs($this->secretaria)
            ->get(route('traslados.certificado.pdf', $expediente))
            ->assertForbidden();

        $expediente->update(['paz_y_salvo_financiero' => true]);
        $expediente->update(['documentos_completos' => false]);

        $this->actingAs($this->secretaria)
            ->get(route('traslados.certificado.pdf', $expediente))
            ->assertForbidden();
    }

    /** @test */
    public function el_historial_y_el_certificado_se_generan_en_pdf(): void
    {
        $expediente = $this->crearExpediente([
            'tipo' => ExpedienteTraslado::TIPO_SALIDA,
            'momento' => null,
            'institucion' => 'COLEGIO DE DESTINO',
            'documentos_completos' => true,
            'paz_y_salvo_financiero' => true,
            'paz_y_salvo_biblioteca' => true,
        ]);

        HistorialExternoPromocion::create([
            'expediente_id' => $expediente->id,
            'anio_lectivo' => '2025 - 2026',
            'nivel' => 'NOVENO',
            'promedio' => 8.75,
            'escala' => '0-10',
        ]);

        $this->actingAs($this->secretaria)
            ->get(route('traslados.historial.pdf', $expediente))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->actingAs($this->secretaria)
            ->get(route('traslados.certificado.pdf', $expediente))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
    }

    /** @test */
    public function la_homologacion_conserva_escala_y_ponderacion_de_origen(): void
    {
        $expediente = $this->crearExpediente();

        $this->actingAs($this->secretaria)
            ->post(route('traslados.promociones.store', $expediente), [
                'anio_lectivo' => '2025 - 2026',
                'nivel' => 'NOVENO',
                'promedio' => '18,5',
                'escala' => '0-20',
                'observacion' => 'Acta legalizada de la institución de origen.',
            ])
            ->assertSessionHasNoErrors();

        $this->actingAs($this->secretaria)
            ->post(route('traslados.notas.store', $expediente), [
                'asignatura' => 'Matemática',
                'periodo' => 'Primer quimestre',
                'nota' => '17,75',
                'ponderacion' => '40',
                'escala' => '0-20',
                'asistencia_porcentaje' => '95',
            ])
            ->assertSessionHasNoErrors();

        $promocion = HistorialExternoPromocion::firstOrFail();
        $this->assertSame('0-20', $promocion->escala);
        $this->assertSame(18.5, round((float) $promocion->promedio, 2));

        $nota = HistorialExternoNota::firstOrFail();
        $this->assertSame('0-20', $nota->escala);
        $this->assertSame(40.0, round((float) $nota->ponderacion, 2));
        $this->assertSame(95.0, round((float) $nota->asistencia_porcentaje, 2));

        $this->assertDatabaseHas('expediente_auditorias', [
            'expediente_id' => $expediente->id,
            'accion' => ExpedienteAuditoria::ACCION_PROMOCION_AGREGADA,
        ]);
        $this->assertDatabaseHas('expediente_auditorias', [
            'expediente_id' => $expediente->id,
            'accion' => ExpedienteAuditoria::ACCION_NOTA_AGREGADA,
        ]);
    }

    /** @test */
    public function la_ficha_del_expediente_muestra_documentos_homologacion_y_bitacora(): void
    {
        $expediente = $this->crearExpediente();

        ExpedienteAuditoria::create([
            'expediente_id' => $expediente->id,
            'alumno_nombre' => $this->alumno->nombreCompleto(),
            'usuario_id' => $this->secretaria->id,
            'usuario_nombre' => $this->secretaria->nombreCompleto(),
            'usuario_rol' => 'SECRETARIA',
            'accion' => ExpedienteAuditoria::ACCION_CREACION,
            'detalle' => 'Expediente creado.',
            'ip' => '127.0.0.1',
        ]);

        $this->actingAs($this->secretaria)
            ->get(route('traslados.show', $expediente))
            ->assertOk()
            ->assertSee($this->alumno->apellidos)
            ->assertSee('Check-list documental')
            ->assertSee('Homologación')
            ->assertSee('Paz y salvo / cierre')
            ->assertSee('Bitácora del expediente')
            ->assertSee('Expediente creado');
    }

    /** @test */
    public function el_dashboard_de_secretaria_cuenta_los_expedientes_por_regularizar_sin_duplicar(): void
    {
        $representanteAdulto = User::factory()->representante()->create([
            'fecha_nacimiento' => now()->subYears(40)->toDateString(),
        ]);
        $representanteMenor = User::factory()->representante()->create([
            'fecha_nacimiento' => now()->subYears(10)->toDateString(),
        ]);

        // Ficha con representante menor de edad.
        Alumno::factory()->create(['representante_user_id' => $representanteMenor->id]);

        // Estudiante con expediente por regularizar y representante adulto.
        $conExpediente = Alumno::factory()->create(['representante_user_id' => $representanteAdulto->id]);
        $this->crearExpediente(['alumno_id' => $conExpediente->id]);

        // Estudiante que cumple ambas condiciones: debe contarse una sola vez.
        $ambas = Alumno::factory()->create(['representante_user_id' => $representanteMenor->id]);
        $this->crearExpediente(['alumno_id' => $ambas->id]);

        $response = $this->actingAs($this->secretaria)->get('/dashboard')->assertOk();

        $this->assertSame(3, $response->viewData('kpis')['fichas']);
    }
}
