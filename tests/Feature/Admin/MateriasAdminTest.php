<?php

namespace Tests\Feature\Admin;

use App\Models\ClaseDocente;
use App\Models\Curso;
use App\Models\CursoMateria;
use App\Models\Institucion;
use App\Models\Materia;
use App\Models\Paralelo;
use App\Models\User;
use Tests\TestCase;

/**
 * Malla curricular: catálogo de materias y materias por año.
 *
 * Qué hace: comprueba que el ADMIN agregue materias al catálogo, las asocie a
 * un año con sus períodos semanales, cambie los períodos y bloquee años o
 * materias sin borrar el historial. También valida que otros roles no entren.
 * Conecta con: Admin\MateriaController, Curso, Materia, CursoMateria y
 * ClaseDocente.
 * Rutas: GET /admin/materias, POST /admin/materias/catalogo,
 *        POST /admin/materias/{curso}/malla y acciones de estado.
 */
class MateriasAdminTest extends TestCase
{
    private function admin(): User
    {
        $institucion = Institucion::factory()->create();

        return User::factory()->admin()->create(['institucion_id' => $institucion->id]);
    }

    /** @test */
    public function el_admin_agrega_materias_al_catalogo_y_a_la_malla_del_ano()
    {
        $admin = $this->admin();
        $curso = Curso::factory()->create(['nombre' => 'NOVENO GRADO']);

        $this->actingAs($admin)
            ->post(route('admin.materias.catalogo.store'), [
                'nombre' => 'Biología',
                'area' => 'Ciencias Naturales',
                'codigo' => 'BIO-01',
                'curso' => $curso->id,
            ])
            ->assertRedirect(route('admin.materias.index', ['curso' => $curso->id]))
            ->assertSessionHas('success');

        $materia = Materia::firstOrFail();

        $this->assertSame('Biología', $materia->nombre);
        $this->assertSame('Ciencias Naturales', $materia->area);
        $this->assertTrue($materia->activo);

        $this->actingAs($admin)
            ->post(route('admin.materias.malla.store', $curso), [
                'materia_id' => $materia->id,
                'periodos_semanales' => 5,
                'orden' => 1,
            ])
            ->assertRedirect(route('admin.materias.index', ['curso' => $curso->id]))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('curso_materias', [
            'curso_id' => $curso->id,
            'materia_id' => $materia->id,
            'periodos_semanales' => 5,
            'orden' => 1,
        ]);
    }

    /** @test */
    public function el_admin_actualiza_los_periodos_semanales_de_la_malla()
    {
        $admin = $this->admin();
        $curso = Curso::factory()->create();
        $fila = CursoMateria::factory()->create([
            'curso_id' => $curso->id,
            'periodos_semanales' => 3,
        ]);

        $this->actingAs($admin)
            ->put(route('admin.materias.malla.update', [$curso, $fila]), [
                'periodos_semanales' => 7,
                'orden' => 2,
            ])
            ->assertRedirect(route('admin.materias.index', ['curso' => $curso->id]))
            ->assertSessionHas('success');

        $fila->refresh();

        $this->assertSame(7, (int) $fila->periodos_semanales);
        $this->assertSame(2, (int) $fila->orden);
    }

    /** @test */
    public function un_ano_bloqueado_no_permite_agregar_materias()
    {
        $admin = $this->admin();
        $curso = Curso::factory()->create(['activo' => false]);
        $materia = Materia::factory()->create();

        $this->actingAs($admin)
            ->post(route('admin.materias.malla.store', $curso), [
                'materia_id' => $materia->id,
                'periodos_semanales' => 4,
            ])
            ->assertSessionHas('error');

        $this->assertDatabaseMissing('curso_materias', [
            'curso_id' => $curso->id,
            'materia_id' => $materia->id,
        ]);
    }

    /** @test */
    public function una_materia_bloqueada_no_se_agrega_a_la_malla()
    {
        $admin = $this->admin();
        $curso = Curso::factory()->create();
        $materia = Materia::factory()->inactiva()->create();

        $this->actingAs($admin)
            ->post(route('admin.materias.malla.store', $curso), [
                'materia_id' => $materia->id,
                'periodos_semanales' => 4,
            ])
            ->assertSessionHas('error');

        $this->assertDatabaseMissing('curso_materias', [
            'curso_id' => $curso->id,
            'materia_id' => $materia->id,
        ]);
    }

    /** @test */
    public function bloquear_una_materia_o_un_ano_conserva_el_historial()
    {
        $admin = $this->admin();
        $curso = Curso::factory()->create();
        $materia = Materia::factory()->create();

        $this->actingAs($admin)
            ->post(route('admin.materias.catalogo.estado', $materia))
            ->assertSessionHas('success');

        $this->actingAs($admin)
            ->post(route('admin.cursos.estado', $curso))
            ->assertSessionHas('success');

        $this->assertFalse($materia->refresh()->activo);
        $this->assertFalse($curso->refresh()->activo);
        $this->assertDatabaseHas('materias', ['id' => $materia->id]);
        $this->assertDatabaseHas('cursos', ['id' => $curso->id]);
    }

    /** @test */
    public function no_se_quita_de_la_malla_una_materia_con_docentes_asignados()
    {
        $admin = $this->admin();
        $curso = Curso::factory()->create();
        $materia = Materia::factory()->create();
        $paralelo = Paralelo::factory()->create();
        $docente = User::factory()->docente()->create();

        $fila = CursoMateria::factory()->create([
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
            ->delete(route('admin.materias.malla.destroy', [$curso, $fila]))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('curso_materias', ['id' => $fila->id]);
    }

    /** @test */
    public function el_admin_retira_de_la_malla_una_materia_sin_docentes()
    {
        $admin = $this->admin();
        $curso = Curso::factory()->create();
        $fila = CursoMateria::factory()->create(['curso_id' => $curso->id]);

        $this->actingAs($admin)
            ->delete(route('admin.materias.malla.destroy', [$curso, $fila]))
            ->assertRedirect(route('admin.materias.index', ['curso' => $curso->id]))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('curso_materias', ['id' => $fila->id]);
    }

    /** @test */
    public function un_ano_bloqueado_no_permite_actualizar_la_malla()
    {
        $admin = $this->admin();
        $curso = Curso::factory()->create(['activo' => false]);
        $fila = CursoMateria::factory()->create([
            'curso_id' => $curso->id,
            'periodos_semanales' => 3,
        ]);

        $this->actingAs($admin)
            ->put(route('admin.materias.malla.update', [$curso, $fila]), [
                'periodos_semanales' => 7,
                'orden' => 2,
            ])
            ->assertSessionHas('error');

        $this->assertSame(3, (int) $fila->refresh()->periodos_semanales);
    }

    /** @test */
    public function un_ano_bloqueado_no_permite_quitar_materias_de_la_malla()
    {
        $admin = $this->admin();
        $curso = Curso::factory()->create(['activo' => false]);
        $fila = CursoMateria::factory()->create(['curso_id' => $curso->id]);

        $this->actingAs($admin)
            ->delete(route('admin.materias.malla.destroy', [$curso, $fila]))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('curso_materias', ['id' => $fila->id]);
    }

    /** @test */
    public function solo_el_admin_entra_a_la_malla_curricular()
    {
        $docente = User::factory()->docente()->create();

        $this->actingAs($docente)
            ->get(route('admin.materias.index'))
            ->assertForbidden();
    }
}
