<?php

namespace Tests\Feature\Directivo;

use App\Models\Alumno;
use App\Models\Asistencia;
use App\Models\ClaseDocente;
use App\Models\Curso;
use App\Models\Institucion;
use App\Models\Materia;
use App\Models\Matricula;
use App\Models\Nota;
use App\Models\Paralelo;
use App\Models\PeriodoLectivo;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

/**
 * Paneles propios de Rectorado y Vicerrectorado.
 *
 * Qué hace: comprueba el acceso por perfil activo, la redirección del
 * dashboard, el menú lateral, el rendimiento consolidado, los estudiantes en
 * riesgo y las exportaciones.
 * Conecta con: PanelRectorController, PanelVicerrectorController,
 * RendimientoController, EstudiantesRiesgoController y RoleMiddleware.
 * Rutas: /rector, /vicerrector y sus módulos.
 */
class PanelDirectivoTest extends TestCase
{
    private User $rectora;

    private User $vicerrectora;

    private PeriodoLectivo $periodo;

    private Curso $curso;

    private Paralelo $paralelo;

    private Materia $materia;

    private ClaseDocente $clase;

    private Alumno $alumnoRiesgo;

    private Alumno $alumnoAprobado;

    protected function setUp(): void
    {
        parent::setUp();

        Institucion::factory()->create(['sistema_evaluacion' => 'quimestre']);

        $this->rectora = User::factory()->rectora()->create();
        $this->vicerrectora = User::factory()->vicerrectora()->create();

        $this->periodo = PeriodoLectivo::factory()->create(['activo' => true]);
        $this->curso = Curso::factory()->create(['nombre' => 'DECIMO GRADO']);
        $this->paralelo = Paralelo::factory()->create(['nombre' => 'A']);
        $this->materia = Materia::factory()->create(['nombre' => 'MATEMATICAS']);

        $docente = User::factory()->docente()->create();

        $this->clase = ClaseDocente::create([
            'user_id' => $docente->id,
            'materia_id' => $this->materia->id,
            'curso_id' => $this->curso->id,
            'paralelo_id' => $this->paralelo->id,
        ]);

        $this->alumnoRiesgo = Alumno::factory()->create([
            'nombres' => 'ANA',
            'apellidos' => 'RIESGO',
        ]);

        $this->alumnoAprobado = Alumno::factory()->create([
            'nombres' => 'LUIS',
            'apellidos' => 'APROBADO',
        ]);

        $this->matricular($this->alumnoRiesgo, 5);
        $this->matricular($this->alumnoAprobado, 9);

        Asistencia::create([
            'curso_id' => $this->curso->id,
            'paralelo_id' => $this->paralelo->id,
            'alumno_id' => $this->alumnoRiesgo->id,
            'fecha' => $this->periodo->fecha_inicio->copy()->addDay(),
            'estado' => 'ausente',
        ]);
    }

    /** Matricula al estudiante y registra una nota del primer parcial. */
    private function matricular(Alumno $alumno, float $nota): Matricula
    {
        $matricula = Matricula::create([
            'codigo_matricula' => 'MAT-' . $alumno->id,
            'alumno_id' => $alumno->id,
            'curso_id' => $this->curso->id,
            'paralelo_id' => $this->paralelo->id,
            'periodo_id' => $this->periodo->id,
            'estado' => Matricula::ESTADO_DEFINITIVA,
        ]);

        Nota::create([
            'matricula_id' => $matricula->id,
            'alumno_id' => $alumno->id,
            'clase_docente_id' => $this->clase->id,
            'parcial' => 1,
            'tipo_nota' => 'parcial',
            'fii' => $nota,
            'ffi' => $nota,
            'fig' => $nota,
            'ffg' => $nota,
            'proyecto_integrador' => $nota,
            'evaluacion_parcial' => $nota,
        ]);

        return $matricula;
    }

    /** @test */
    public function la_rectora_ve_su_panel_institucional()
    {
        $response = $this->actingAs($this->rectora)->get('/rector');

        $response->assertOk();
        $response->assertViewIs('directivo.rector.panel');
        $response->assertSee('Panel institucional');
        $response->assertSee('Rectorado');
        $response->assertSee('Rendimiento académico');
        $response->assertSee('Estudiantes en riesgo');
    }

    /** @test */
    public function la_vicerrectora_ve_su_panel_academico()
    {
        $response = $this->actingAs($this->vicerrectora)->get('/vicerrector');

        $response->assertOk();
        $response->assertViewIs('directivo.vicerrector.panel');
        $response->assertSee('Panel académico');
        $response->assertSee('Vicerrectorado');
        $response->assertSee('Calificaciones pendientes');
    }

    /** @test */
    public function el_dashboard_redirige_a_cada_autoridad_a_su_panel()
    {
        $this->actingAs($this->rectora)
            ->get('/dashboard')
            ->assertRedirect(route('rector.panel'));

        $this->actingAs($this->vicerrectora)
            ->get('/dashboard')
            ->assertRedirect(route('vicerrector.panel'));
    }

    /** @test */
    public function cada_autoridad_solo_entra_a_su_propio_panel()
    {
        $this->actingAs($this->rectora)->get('/vicerrector')->assertForbidden();
        $this->actingAs($this->vicerrectora)->get('/rector')->assertForbidden();
        $this->actingAs(User::factory()->secretaria()->create())->get('/rector')->assertForbidden();
    }

    /** @test */
    public function el_menu_de_la_rectora_muestra_su_seccion_y_no_enlaces_rotos()
    {
        $response = $this->actingAs($this->rectora)->get('/rector');

        $response->assertOk();
        $response->assertSee(route('rector.rendimiento'), false);
        $response->assertSee(route('rector.estudiantes-riesgo'), false);
        $response->assertDontSee(route('admin.pre-registros.index'), false);
    }

    /** @test */
    public function el_menu_de_la_vicerrectora_muestra_su_seccion()
    {
        $response = $this->actingAs($this->vicerrectora)->get('/vicerrector');

        $response->assertOk();
        $response->assertSee(route('vicerrector.rendimiento'), false);
        $response->assertSee(route('vicerrector.estudiantes-riesgo'), false);
        $response->assertDontSee(route('admin.pre-registros.index'), false);
    }

    /** @test */
    public function el_menu_directivo_despliega_el_panel_y_marca_solo_el_modulo_activo()
    {
        $panel = $this->actingAs($this->vicerrectora)->get('/vicerrector');

        $panel->assertOk();
        $panel->assertSee('bi-chevron-down chevron-icon', false);
        $panel->assertSee('submenuPanelDirectivo', false);
        $panel->assertDontSee('justify-content-between align-items-center active', false);
        $panel->assertSee('nav-link text-white small py-1 active', false);
        $panel->assertSee('Dashboard');

        $rendimiento = $this->actingAs($this->vicerrectora)->get('/vicerrector/rendimiento');

        $rendimiento->assertOk();
        $rendimiento->assertDontSee('justify-content-between align-items-center active', false);
        $rendimiento->assertSee('nav-link text-white small py-1 active', false);
        $rendimiento->assertSee('Rendimiento académico');
    }

    /** @test */
    public function los_avisos_flash_se_muestran_con_sweetalert_en_los_paneles()
    {
        $response = $this->actingAs($this->rectora)
            ->withSession(['success' => 'Cambios guardados correctamente'])
            ->get('/rector');

        $response->assertOk();
        $response->assertSee('avisosSesion', false);
        $response->assertSee('Cambios guardados correctamente');
        $response->assertSee('toast: true', false);
    }

    /** @test */
    public function los_paneles_conservan_el_nombre_institucional_del_layout()
    {
        $institucion = Institucion::first();

        // En pruebas la institución se crea después del arranque de la app,
        // así que se refresca el valor compartido con las vistas.
        View::share('institucion', $institucion);

        $response = $this->actingAs($this->vicerrectora)->get('/vicerrector');

        $response->assertOk();
        $response->assertSee('<title>Panel académico - ' . $institucion->nombre . '</title>', false);
        $response->assertSee($institucion->nombre);
    }

    /** @test */
    public function el_rendimiento_muestra_promedios_y_resumen_por_asignatura()
    {
        $response = $this->actingAs($this->rectora)
            ->get('/rector/rendimiento?corte=p1');

        $response->assertOk();
        $response->assertViewIs('directivo.rendimiento.index');
        $response->assertSee('Rendimiento académico');
        $response->assertSee('MATEMATICAS');
        $response->assertSee('7,00');
        $response->assertSee('Promedio general');
    }

    /** @test */
    public function la_vicerrectora_tambien_consulta_el_rendimiento()
    {
        $response = $this->actingAs($this->vicerrectora)
            ->get('/vicerrector/rendimiento?corte=p1');

        $response->assertOk();
        $response->assertViewIs('directivo.rendimiento.index');
        $response->assertSee('MATEMATICAS');
    }

    /** @test */
    public function el_listado_de_riesgo_muestra_al_estudiante_con_promedio_bajo()
    {
        $response = $this->actingAs($this->rectora)
            ->get('/rector/estudiantes-riesgo?corte=p1');

        $response->assertOk();
        $response->assertViewIs('directivo.estudiantes-riesgo.index');
        $response->assertSee('RIESGO ANA');
        $response->assertDontSee('APROBADO LUIS');
        $response->assertSee('Nivel rojo');
    }

    /** @test */
    public function la_ficha_del_estudiante_muestra_notas_y_asistencia_sin_datos_clinicos()
    {
        $response = $this->actingAs($this->rectora)
            ->get('/rector/estudiantes-riesgo/' . $this->alumnoRiesgo->id . '?corte=p1');

        $response->assertOk();
        $response->assertViewIs('directivo.estudiantes-riesgo.show');
        $response->assertSee('RIESGO ANA');
        $response->assertSee('Calificaciones por asignatura');
        $response->assertSee('Asistencia');
        $response->assertSee('Los detalles clínicos del expediente son confidenciales del DECE.');
    }

    /** @test */
    public function las_exportaciones_de_rendimiento_responden_pdf_y_excel()
    {
        $pdf = $this->actingAs($this->rectora)
            ->get('/rector/rendimiento/pdf?corte=p1');

        $pdf->assertOk();
        $pdf->assertHeader('content-type', 'application/pdf');

        $excel = $this->actingAs($this->vicerrectora)
            ->get('/vicerrector/rendimiento/excel?corte=p1');

        $excel->assertOk();
        $excel->assertHeader(
            'content-type',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        );
    }

    /** @test */
    public function las_exportaciones_de_riesgo_responden_pdf_y_excel()
    {
        $pdf = $this->actingAs($this->vicerrectora)
            ->get('/vicerrector/estudiantes-riesgo/pdf?corte=p1');

        $pdf->assertOk();
        $pdf->assertHeader('content-type', 'application/pdf');

        $excel = $this->actingAs($this->rectora)
            ->get('/rector/estudiantes-riesgo/excel?corte=p1');

        $excel->assertOk();
        $excel->assertHeader(
            'content-type',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        );
    }

    /** @test */
    public function el_perfil_activo_decide_el_panel_de_una_cuenta_multirol()
    {
        $multirol = User::factory()->rectora()->create();
        UserRole::create(['user_id' => $multirol->id, 'rol' => 'VICERRECTORA']);

        $this->actingAs($multirol)
            ->withSession(['perfil_activo' => 'VICERRECTORA'])
            ->get('/vicerrector')
            ->assertOk()
            ->assertSee('Panel académico');

        $this->actingAs($multirol)
            ->withSession(['perfil_activo' => 'VICERRECTORA'])
            ->get('/rector')
            ->assertForbidden();

        $this->actingAs($multirol)
            ->withSession(['perfil_activo' => 'RECTORA'])
            ->get('/rector')
            ->assertOk()
            ->assertSee('Panel institucional');
    }

    /** @test */
    public function la_vicerrectora_puede_gestionar_circulares()
    {
        $this->actingAs($this->vicerrectora)
            ->get('/admin/circulares')
            ->assertOk();
    }
}
