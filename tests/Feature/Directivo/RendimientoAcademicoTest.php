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
use App\Services\RendimientoAcademico;
use Tests\TestCase;

/**
 * Cálculos del rendimiento académico consolidado.
 *
 * Qué hace: valida notas por corte, agrupaciones, distribución, riesgo,
 * asistencia y ficha reutilizando las reglas de las actas (7/10).
 * Conecta con: RendimientoAcademico y CalculoNotas.
 * Rutas: alimenta /rector/rendimiento y /vicerrector/rendimiento.
 */
class RendimientoAcademicoTest extends TestCase
{
    private RendimientoAcademico $servicio;

    private PeriodoLectivo $periodo;

    private ClaseDocente $clase;

    private Curso $curso;

    private Paralelo $paralelo;

    /** @var array<string, Matricula> */
    private array $matriculas = [];

    /** @var array<string, Alumno> */
    private array $alumnos = [];

    protected function setUp(): void
    {
        parent::setUp();

        Institucion::factory()->create(['sistema_evaluacion' => 'quimestre']);

        $this->servicio = app(RendimientoAcademico::class);
        $this->periodo = PeriodoLectivo::factory()->create(['activo' => true]);
        $this->curso = Curso::factory()->create(['nombre' => 'DECIMO GRADO']);
        $this->paralelo = Paralelo::factory()->create(['nombre' => 'A']);
        $materia = Materia::factory()->create(['nombre' => 'MATEMATICAS']);
        $docente = User::factory()->docente()->create();

        $this->clase = ClaseDocente::create([
            'user_id' => $docente->id,
            'materia_id' => $materia->id,
            'curso_id' => $this->curso->id,
            'paralelo_id' => $this->paralelo->id,
        ]);

        $this->crearEstudiante('bajo', 5.0);
        $this->crearEstudiante('aprobado', 7.0);
        $this->crearEstudiante('alto', 9.5);
    }

    private function crearEstudiante(string $llave, float $nota): void
    {
        $alumno = Alumno::factory()->create();
        $this->alumnos[$llave] = $alumno;

        $matricula = Matricula::create([
            'codigo_matricula' => 'MAT-' . $alumno->id,
            'alumno_id' => $alumno->id,
            'curso_id' => $this->curso->id,
            'paralelo_id' => $this->paralelo->id,
            'periodo_id' => $this->periodo->id,
            'estado' => Matricula::ESTADO_DEFINITIVA,
        ]);

        $this->matriculas[$llave] = $matricula;

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
    }

    /** @test */
    public function calcula_las_notas_del_parcial_por_clase()
    {
        $filas = $this->servicio->notasPorClase($this->periodo->id, 'p1');

        $this->assertCount(3, $filas);
        $this->assertSame(5.0, $filas->firstWhere('alumno.id', $this->alumnos['bajo']->id)['nota']);
        $this->assertSame(7.0, $filas->firstWhere('alumno.id', $this->alumnos['aprobado']->id)['nota']);
        $this->assertSame(9.5, $filas->firstWhere('alumno.id', $this->alumnos['alto']->id)['nota']);
    }

    /** @test */
    public function agrupa_por_nivel_y_calcula_aprobados_y_reprobados()
    {
        $filas = $this->servicio->notasPorClase($this->periodo->id, 'p1');
        $resumen = $this->servicio->resumenAgrupado($filas, 'nivel');

        $this->assertCount(1, $resumen);
        $this->assertSame('DECIMO GRADO', $resumen->first()['etiqueta']);
        $this->assertSame(3, $resumen->first()['evaluados']);
        $this->assertSame(2, $resumen->first()['aprobados']);
        $this->assertSame(1, $resumen->first()['reprobados']);
        $this->assertSame(7.17, $resumen->first()['promedio']);
    }

    /** @test */
    public function calcula_la_distribucion_por_rangos()
    {
        $filas = $this->servicio->notasPorClase($this->periodo->id, 'p1');
        $distribucion = collect($this->servicio->distribucion($filas))->keyBy('etiqueta');

        $this->assertSame(1, $distribucion['Insuficiente']['total']);
        $this->assertSame(1, $distribucion['Regular']['total']);
        $this->assertSame(0, $distribucion['Bueno']['total']);
        $this->assertSame(0, $distribucion['Muy bueno']['total']);
        $this->assertSame(1, $distribucion['Excelente']['total']);
    }

    /** @test */
    public function identifica_estudiantes_en_riesgo_por_promedio()
    {
        $riesgo = $this->servicio->estudiantesEnRiesgo($this->periodo->id, 'p1');

        $this->assertCount(1, $riesgo);
        $this->assertSame($this->alumnos['bajo']->id, $riesgo->first()['alumno']->id);
        $this->assertSame('rojo', $riesgo->first()['nivel']);
        $this->assertContains('Promedio general menor a 7', $riesgo->first()['motivos']);
    }

    /** @test */
    public function marca_riesgo_por_asistencia_menor_al_85_por_ciento()
    {
        Asistencia::create([
            'curso_id' => $this->curso->id,
            'paralelo_id' => $this->paralelo->id,
            'alumno_id' => $this->alumnos['alto']->id,
            'fecha' => $this->periodo->fecha_inicio->copy()->addDay(),
            'estado' => 'ausente',
        ]);

        $riesgo = $this->servicio->estudiantesEnRiesgo($this->periodo->id, 'p1');
        $alto = $riesgo->firstWhere('alumno.id', $this->alumnos['alto']->id);

        $this->assertNotNull($alto);
        $this->assertContains('Asistencia menor al 85 %', $alto['motivos']);
    }

    /** @test */
    public function calcula_la_asistencia_efectiva_por_estudiante()
    {
        $alumno = $this->alumnos['bajo'];

        foreach (['presente', 'justificada', 'atraso', 'ausente'] as $indice => $estado) {
            Asistencia::create([
                'curso_id' => $this->curso->id,
                'paralelo_id' => $this->paralelo->id,
                'alumno_id' => $alumno->id,
                'fecha' => $this->periodo->fecha_inicio->copy()->addDays($indice + 1),
                'estado' => $estado,
            ]);
        }

        $porcentajes = $this->servicio->asistenciaPorAlumno($this->periodo->id);

        $this->assertSame(75.0, $porcentajes[$alumno->id]);
    }

    /** @test */
    public function la_ficha_del_estudiante_reutiliza_las_mismas_reglas()
    {
        $ficha = $this->servicio->fichaEstudiante($this->alumnos['bajo'], $this->periodo->id, 'p1');

        $this->assertSame($this->alumnos['bajo']->id, $ficha['alumno']->id);
        $this->assertSame(5.0, $ficha['promedio']);
        $this->assertSame(1, $ficha['reprobadas']);
        $this->assertSame(1, $ficha['materias']);
        $this->assertCount(1, $ficha['filas']);
    }

    /** @test */
    public function sin_notas_no_inventa_promedios()
    {
        $alumno = Alumno::factory()->create();

        Matricula::create([
            'codigo_matricula' => 'MAT-' . $alumno->id,
            'alumno_id' => $alumno->id,
            'curso_id' => $this->curso->id,
            'paralelo_id' => $this->paralelo->id,
            'periodo_id' => $this->periodo->id,
            'estado' => Matricula::ESTADO_DEFINITIVA,
        ]);

        $filas = $this->servicio->notasPorClase($this->periodo->id, 'p1');
        $fila = $filas->firstWhere('alumno.id', $alumno->id);

        $this->assertNotNull($fila);
        $this->assertNull($fila['nota']);
        $this->assertNull($fila['aprobado']);

        $riesgo = $this->servicio->estudiantesEnRiesgo($this->periodo->id, 'p1');
        $this->assertNull($riesgo->firstWhere('alumno.id', $alumno->id));
    }
}
