<?php

namespace Tests\Feature\Admin;

use App\Models\Alumno;
use App\Models\Curso;
use App\Models\Matricula;
use App\Models\Paralelo;
use App\Models\PeriodoLectivo;
use App\Models\User;
use Tests\TestCase;

class MatriculaTest extends TestCase
{
    private User $admin;
    private Matricula $matricula;
    private Curso $curso;
    private Paralelo $paralelo;
    private PeriodoLectivo $periodo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
        $this->curso = Curso::factory()->create();
        $this->paralelo = Paralelo::factory()->create();
        $this->periodo = PeriodoLectivo::factory()->create(['activo' => true]);

        $alumno = Alumno::factory()->create();
        $this->matricula = Matricula::factory()->create([
            'alumno_id' => $alumno->id,
            'curso_id' => $this->curso->id,
            'paralelo_id' => $this->paralelo->id,
            'periodo_id' => $this->periodo->id,
        ]);
    }

    private function actingAsAdmin(): void
    {
        $this->actingAs($this->admin);
    }

    /** @test */
    public function edit_shows_form()
    {
        $this->actingAsAdmin();

        $response = $this->get(route('admin.matriculas.edit', $this->matricula->id));

        $response->assertStatus(200);
        $response->assertViewIs('admin.matriculas.edit');
        $response->assertSee($this->matricula->alumno->nombres);
    }

    /** @test */
    public function edit_requires_auth()
    {
        $response = $this->get(route('admin.matriculas.edit', $this->matricula->id));
        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function update_saves_alumno_and_matricula()
    {
        $this->actingAsAdmin();

        $response = $this->put(route('admin.matriculas.update', $this->matricula->id), [
            'nombres' => 'NombreActualizado',
            'apellidos' => 'ApellidoActualizado',
            'numero_cedula' => '1712345678',
            'genero' => 'masculino',
            'curso_id' => $this->curso->id,
            'paralelo_id' => $this->paralelo->id,
            'periodo_id' => $this->periodo->id,
        ]);

        $response->assertRedirect(route('admin.matriculas.edit', $this->matricula->id));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('alumnos', [
            'id' => $this->matricula->alumno->id,
            'nombres' => 'NombreActualizado',
            'apellidos' => 'ApellidoActualizado',
        ]);
    }

    /** @test */
    public function update_validates_required_fields()
    {
        $this->actingAsAdmin();

        $response = $this->put(route('admin.matriculas.update', $this->matricula->id), []);

        $response->assertSessionHasErrors([
            'nombres', 'apellidos', 'curso_id', 'paralelo_id', 'periodo_id',
        ]);
    }

    /** @test */
    public function update_saves_medical_and_housing_fields()
    {
        $this->actingAsAdmin();

        $this->put(route('admin.matriculas.update', $this->matricula->id), [
            'nombres' => 'Test',
            'apellidos' => 'Test',
            'padece_enfermedad' => true,
            'nombre_enfermedad' => 'Diabetes',
            'necesidades_educativas_especificas_nee' => true,
            'nombre_nee' => 'Dislexia',
            'casa_propia' => true,
            'tiene_agua' => true,
            'padre_nombres_apellidos' => 'Padre Test',
            'padre_es_facturador' => true,
            'madre_nombres_apellidos' => 'Madre Test',
            'madre_es_facturador' => false,
            'curso_id' => $this->curso->id,
            'paralelo_id' => $this->paralelo->id,
            'periodo_id' => $this->periodo->id,
        ]);

        $this->assertDatabaseHas('alumnos', [
            'id' => $this->matricula->alumno->id,
            'padece_enfermedad' => 1,
            'nombre_enfermedad' => 'Diabetes',
            'necesidades_educativas_especificas_nee' => 1,
            'nombre_nee' => 'Dislexia',
            'casa_propia' => 1,
            'tiene_agua' => 1,
            'padre_nombres_apellidos' => 'Padre Test',
            'padre_es_facturador' => 1,
            'madre_nombres_apellidos' => 'Madre Test',
            'madre_es_facturador' => 0,
        ]);
    }

    /** @test */
    public function update_saves_matricula_fields()
    {
        $this->actingAsAdmin();

        $nuevoCurso = Curso::factory()->create();
        $nuevoParalelo = Paralelo::factory()->create();

        $this->put(route('admin.matriculas.update', $this->matricula->id), [
            'nombres' => 'Test',
            'apellidos' => 'Test',
            'codigo_matricula' => 'MAT-NUEVO-001',
            'curso_id' => $nuevoCurso->id,
            'paralelo_id' => $nuevoParalelo->id,
            'periodo_id' => $this->periodo->id,
            'especializacion' => 'Informática',
            'firma_rectora_completada' => true,
            'firma_secretaria_completada' => false,
            'firma_representante_completada' => true,
        ]);

        $this->assertDatabaseHas('matriculas', [
            'id' => $this->matricula->id,
            'codigo_matricula' => 'MAT-NUEVO-001',
            'curso_id' => $nuevoCurso->id,
            'paralelo_id' => $nuevoParalelo->id,
            'especializacion' => 'Informática',
            'firma_rectora_completada' => 1,
            'firma_secretaria_completada' => 0,
            'firma_representante_completada' => 1,
        ]);
    }

    /** @test */
    public function update_handles_alternate_representative_parentesco_otro()
    {
        $this->actingAsAdmin();

        $this->put(route('admin.matriculas.update', $this->matricula->id), [
            'nombres' => 'Test',
            'apellidos' => 'Test',
            'representante_alterno_nombre' => 'Tía María',
            'representante_alterno_parentesco' => '',
            'representante_alterno_parentesco_otro' => 'Tía',
            'curso_id' => $this->curso->id,
            'paralelo_id' => $this->paralelo->id,
            'periodo_id' => $this->periodo->id,
        ]);

        $this->assertDatabaseHas('alumnos', [
            'id' => $this->matricula->alumno->id,
            'representante_alterno_nombre' => 'Tía María',
            'representante_alterno_parentesco' => 'Tía',
        ]);
    }

    /** @test */
    public function update_requires_authentication()
    {
        $response = $this->put(route('admin.matriculas.update', $this->matricula->id), [
            'nombres' => 'Test',
            'apellidos' => 'Test',
            'curso_id' => $this->curso->id,
            'paralelo_id' => $this->paralelo->id,
            'periodo_id' => $this->periodo->id,
        ]);

        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function non_admin_gets_403_on_update()
    {
        $docente = User::factory()->docente()->create();
        $this->actingAs($docente);

        $response = $this->put(route('admin.matriculas.update', $this->matricula->id), [
            'nombres' => 'Test',
            'apellidos' => 'Test',
            'curso_id' => $this->curso->id,
            'paralelo_id' => $this->paralelo->id,
            'periodo_id' => $this->periodo->id,
        ]);

        $response->assertForbidden();
    }

    /** @test */
    public function update_fails_with_inexistent_curso()
    {
        $this->actingAsAdmin();

        $response = $this->put(route('admin.matriculas.update', $this->matricula->id), [
            'nombres' => 'Test',
            'apellidos' => 'Test',
            'curso_id' => 9999,
            'paralelo_id' => $this->paralelo->id,
            'periodo_id' => $this->periodo->id,
        ]);

        $response->assertSessionHasErrors('curso_id');
    }
}
