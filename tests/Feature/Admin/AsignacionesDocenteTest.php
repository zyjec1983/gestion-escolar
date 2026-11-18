<?php

namespace Tests\Feature\Admin;

use App\Models\Agenda;
use App\Models\ClaseDocente;
use App\Models\Curso;
use App\Models\CursoMateria;
use App\Models\Institucion;
use App\Models\Materia;
use App\Models\Paralelo;
use App\Models\TipoActividad;
use App\Models\User;
use App\Services\CargaHorariaDocente;
use Tests\TestCase;

/**
 * Distributivo docente y carga horaria.
 *
 * Qué hace: comprueba la asignación de materias de la malla a un docente, el
 * cambio de paralelo, el cálculo de períodos y horas reloj, y la protección
 * del historial cuando la asignación ya tiene agendas, notas o materiales.
 * Conecta con: Admin\AsignacionDocenteController, CargaHorariaDocente,
 * ClaseDocente, CursoMateria y Agenda.
 * Rutas: GET /admin/asignaciones, POST /admin/asignaciones y sus acciones.
 */
class AsignacionesDocenteTest extends TestCase
{
    private function admin(): User
    {
        $institucion = Institucion::factory()->create();

        return User::factory()->admin()->create(['institucion_id' => $institucion->id]);
    }

    /** @test */
    public function el_admin_asigna_una_materia_de_la_malla_a_un_docente()
    {
        $admin = $this->admin();
        $docente = User::factory()->docente()->create(['horas_contrato' => 40]);
        $curso = Curso::factory()->create();
        $materia = Materia::factory()->create();
        $paralelo = Paralelo::factory()->create();

        CursoMateria::factory()->create([
            'curso_id' => $curso->id,
            'materia_id' => $materia->id,
            'periodos_semanales' => 5,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.asignaciones.store'), [
                'user_id' => $docente->id,
                'curso_id' => $curso->id,
                'materia_id' => $materia->id,
                'paralelo_id' => $paralelo->id,
            ])
            ->assertRedirect(route('admin.asignaciones.index', ['docente' => $docente->id]))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('clases_docentes', [
            'user_id' => $docente->id,
            'curso_id' => $curso->id,
            'materia_id' => $materia->id,
            'paralelo_id' => $paralelo->id,
        ]);
    }

    /** @test */
    public function no_permite_repetir_la_misma_materia_en_el_mismo_ano_y_paralelo()
    {
        $admin = $this->admin();
        $docente = User::factory()->docente()->create();
        $curso = Curso::factory()->create();
        $materia = Materia::factory()->create();
        $paralelo = Paralelo::factory()->create();

        CursoMateria::factory()->create([
            'curso_id' => $curso->id,
            'materia_id' => $materia->id,
        ]);

        ClaseDocente::create([
            'user_id' => $docente->id,
            'curso_id' => $curso->id,
            'materia_id' => $materia->id,
            'paralelo_id' => $paralelo->id,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.asignaciones.store'), [
                'user_id' => $docente->id,
                'curso_id' => $curso->id,
                'materia_id' => $materia->id,
                'paralelo_id' => $paralelo->id,
            ])
            ->assertSessionHas('error');

        $this->assertSame(1, ClaseDocente::count());
    }

    /** @test */
    public function no_permite_asignar_una_materia_que_no_esta_en_la_malla_del_ano()
    {
        $admin = $this->admin();
        $docente = User::factory()->docente()->create();
        $curso = Curso::factory()->create();
        $materia = Materia::factory()->create();
        $paralelo = Paralelo::factory()->create();

        $this->actingAs($admin)
            ->post(route('admin.asignaciones.store'), [
                'user_id' => $docente->id,
                'curso_id' => $curso->id,
                'materia_id' => $materia->id,
                'paralelo_id' => $paralelo->id,
            ])
            ->assertSessionHas('error');

        $this->assertSame(0, ClaseDocente::count());
    }

    /** @test */
    public function calcula_los_periodos_y_las_horas_reloj_del_docente()
    {
        $docente = User::factory()->docente()->create([
            'horas_contrato' => 40,
            'periodos_atencion_padres' => 2,
            'periodos_reunion_area' => 1,
            'periodos_tutoria' => 1,
        ]);

        $paralelo = Paralelo::factory()->create();

        foreach ([5, 6] as $indice => $periodos) {
            $curso = Curso::factory()->create();
            $materia = Materia::factory()->create();

            CursoMateria::factory()->create([
                'curso_id' => $curso->id,
                'materia_id' => $materia->id,
                'periodos_semanales' => $periodos,
            ]);

            ClaseDocente::create([
                'user_id' => $docente->id,
                'curso_id' => $curso->id,
                'materia_id' => $materia->id,
                'paralelo_id' => $paralelo->id,
            ]);
        }

        $carga = CargaHorariaDocente::calcular($docente);

        $this->assertSame(11, $carga['periodos_clase']);
        $this->assertSame(15, $carga['total_periodos']);
        $this->assertSame(10.0, $carga['horas_plantel']);
        $this->assertSame(30.0, $carga['max_horas_plantel']);
        $this->assertSame(20.0, $carga['horas_contingencia']);
        $this->assertFalse($carga['excede']);
    }

    /** @test */
    public function un_contrato_de_20_horas_tiene_la_mitad_de_carga_de_plantel()
    {
        $docente = User::factory()->docente()->create([
            'horas_contrato' => 20,
        ]);

        $carga = CargaHorariaDocente::calcular($docente);

        $this->assertSame(15.0, $carga['max_horas_plantel']);
        $this->assertSame(5.0, $carga['horas_autonomas']);
        $this->assertSame(22.5, $carga['max_periodos']);
    }

    /** @test */
    public function un_contrato_de_30_horas_calcula_la_carga_proporcional()
    {
        $docente = User::factory()->docente()->create([
            'horas_contrato' => 30,
        ]);

        $carga = CargaHorariaDocente::calcular($docente);

        $this->assertSame(22.5, $carga['max_horas_plantel']);
        $this->assertSame(7.5, $carga['horas_autonomas']);
        $this->assertSame(33.75, $carga['max_periodos']);
        $this->assertSame('22,5', CargaHorariaDocente::formatearHoras($carga['max_horas_plantel']));
    }

    /** @test */
    public function el_admin_cambia_el_paralelo_de_una_asignacion()
    {
        $admin = $this->admin();
        $docente = User::factory()->docente()->create();
        $curso = Curso::factory()->create();
        $materia = Materia::factory()->create();
        $paraleloA = Paralelo::factory()->create(['nombre' => 'A']);
        $paraleloB = Paralelo::factory()->create(['nombre' => 'B']);

        $clase = ClaseDocente::create([
            'user_id' => $docente->id,
            'curso_id' => $curso->id,
            'materia_id' => $materia->id,
            'paralelo_id' => $paraleloA->id,
        ]);

        $this->actingAs($admin)
            ->put(route('admin.asignaciones.update', $clase), [
                'paralelo_id' => $paraleloB->id,
            ])
            ->assertRedirect(route('admin.asignaciones.index', ['docente' => $docente->id]))
            ->assertSessionHas('success');

        $this->assertSame($paraleloB->id, (int) $clase->refresh()->paralelo_id);
    }

    /** @test */
    public function retira_una_asignacion_sin_historial()
    {
        $admin = $this->admin();
        $docente = User::factory()->docente()->create();
        $curso = Curso::factory()->create();
        $materia = Materia::factory()->create();
        $paralelo = Paralelo::factory()->create();

        $clase = ClaseDocente::create([
            'user_id' => $docente->id,
            'curso_id' => $curso->id,
            'materia_id' => $materia->id,
            'paralelo_id' => $paralelo->id,
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.asignaciones.destroy', $clase))
            ->assertRedirect(route('admin.asignaciones.index', ['docente' => $docente->id]))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('clases_docentes', ['id' => $clase->id]);
    }

    /** @test */
    public function no_retira_una_asignacion_con_agendas_y_conserva_el_historial()
    {
        $admin = $this->admin();
        $docente = User::factory()->docente()->create();
        $curso = Curso::factory()->create();
        $materia = Materia::factory()->create();
        $paralelo = Paralelo::factory()->create();

        $clase = ClaseDocente::create([
            'user_id' => $docente->id,
            'curso_id' => $curso->id,
            'materia_id' => $materia->id,
            'paralelo_id' => $paralelo->id,
        ]);

        $tipo = TipoActividad::create(['codigo' => 'TAREA', 'nombre' => 'Tarea']);

        Agenda::create([
            'clase_docente_id' => $clase->id,
            'tipo_actividad_id' => $tipo->id,
            'titulo_agenda' => 'Actividad de prueba',
            'fecha_inicio' => now(),
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.asignaciones.destroy', $clase))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('clases_docentes', ['id' => $clase->id]);
        $this->assertDatabaseHas('agendas', ['clase_docente_id' => $clase->id]);
    }

    /** @test */
    public function el_admin_actualiza_la_carga_complementaria_del_docente()
    {
        $admin = $this->admin();
        $docente = User::factory()->docente()->create();

        $this->actingAs($admin)
            ->put(route('admin.asignaciones.carga', $docente), [
                'horas_contrato' => 30,
                'es_tutor' => '1',
                'periodos_atencion_padres' => 3,
                'periodos_reunion_area' => 2,
                'periodos_tutoria' => 1,
            ])
            ->assertRedirect(route('admin.asignaciones.index', ['docente' => $docente->id]))
            ->assertSessionHas('success');

        $docente->refresh();

        $this->assertSame(30, $docente->horas_contrato);
        $this->assertTrue($docente->es_tutor);
        $this->assertSame(3, $docente->periodos_atencion_padres);
        $this->assertSame(2, $docente->periodos_reunion_area);
        $this->assertSame(1, $docente->periodos_tutoria);
    }

    /** @test */
    public function el_formulario_de_cuenta_puede_continuar_en_el_distributivo()
    {
        $admin = $this->admin();

        $response = $this->actingAs($admin)
            ->post(route('admin.usuarios.store'), [
                'numero_cedula' => '1718459203',
                'name' => 'Carlos Alberto',
                'apellidos' => 'Mendoza Ruiz',
                'titulo' => 'Lcdo. en Ciencias de la Educación',
                'horas_contrato' => 20,
                'email' => 'carlos.mendoza@colegio.edu.ec',
                'telefono' => '0991234567',
                'rol' => 'DOCENTE',
                'password' => 'Docente2026*',
                'password_confirmation' => 'Docente2026*',
                'destino' => 'asignaciones',
            ]);

        $response->assertSessionHas('success');

        $docente = User::where('email', 'carlos.mendoza@colegio.edu.ec')->firstOrFail();

        $response->assertRedirect(route('admin.asignaciones.index', ['docente' => $docente->id]));

        $this->assertSame('Lcdo. en Ciencias de la Educación', $docente->titulo);
        $this->assertSame(20, $docente->horas_contrato);
        $this->assertTrue($docente->debe_cambiar_password);
        $this->assertDatabaseHas('users', ['id' => $docente->id, 'numero_cedula' => '1718459203']);
    }

    /** @test */
    public function solo_el_admin_entra_al_distributivo()
    {
        $docente = User::factory()->docente()->create();

        $this->actingAs($docente)
            ->get(route('admin.asignaciones.index'))
            ->assertForbidden();
    }
}
