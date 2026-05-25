<?php

namespace Tests\Feature;

use App\Models\Curso;
use App\Models\Institucion;
use App\Models\Paralelo;
use App\Models\PeriodoLectivo;
use Barryvdh\DomPDF\Facade\Pdf;
use Tests\TestCase;

class PreRegistroTest extends TestCase
{
    private Institucion $institucion;
    private Curso $curso;
    private Paralelo $paralelo;
    private PeriodoLectivo $periodo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->institucion = Institucion::factory()->create(['pre_registro_activo' => true]);
        $this->curso = Curso::factory()->create();
        $this->paralelo = Paralelo::factory()->create();
        $this->periodo = PeriodoLectivo::factory()->create(['activo' => true]);
    }

    private function validFormData(): array
    {
        return [
            'tipo_solicitud' => 'nuevo_ingreso',
            'rep_tipo_documento' => 'cedula',
            'rep_numero_documento' => '1712345678',
            'rep_nombres' => 'Carlos',
            'rep_apellidos' => 'Pérez',
            'rep_email' => 'carlos@example.com',
            'rep_telefono' => '0999999999',
            'alumno_tipo_documento' => 'cedula',
            'alumno_numero_documento' => '1723456789',
            'alumno_nombres' => 'Juanito',
            'alumno_apellidos' => 'Pérez',
            'alumno_numero_cedula' => '1723456789',
            'alumno_fecha_nacimiento' => '2014-05-15',
            'alumno_genero' => 'masculino',
            'alumno_tipo_sangre' => 'O+',
            'curso_id' => $this->curso->id,
            'paralelo_id' => $this->paralelo->id,
        ];
    }

    public function test_muestra_formulario_cuando_toggle_activo(): void
    {
        $response = $this->get('/pre-registro');

        $response->assertStatus(200);
        $response->assertViewIs('pre-registro.form');
    }

    public function test_muestra_cerrado_cuando_toggle_inactivo(): void
    {
        $this->institucion->update(['pre_registro_activo' => false]);

        $response = $this->get('/pre-registro');

        $response->assertStatus(200);
        $response->assertViewIs('pre-registro.cerrado');
    }

    public function test_store_redirige_a_cerrado_si_toggle_inactivo(): void
    {
        $this->institucion->update(['pre_registro_activo' => false]);

        $response = $this->post('/pre-registro', $this->validFormData());

        $response->assertRedirect(route('pre-registro.create'));
        $response->assertSessionHas('error');
    }

    public function test_store_crea_pre_registro_y_descarga_pdf(): void
    {
        Pdf::shouldReceive('loadView')
            ->once()
            ->andReturnSelf();
        Pdf::shouldReceive('save')
            ->once()
            ->andReturnSelf();
        Pdf::shouldReceive('download')
            ->once()
            ->andReturn(response('pdf content', 200, ['Content-Type' => 'application/pdf']));

        $response = $this->post('/pre-registro', $this->validFormData());

        $response->assertStatus(200);
        $this->assertDatabaseHas('pre_registros', [
            'rep_email' => 'carlos@example.com',
            'estado' => 'pendiente',
            'tipo_solicitud' => 'nuevo_ingreso',
        ]);
    }

    public function test_store_falla_sin_datos_requeridos(): void
    {
        $response = $this->post('/pre-registro', []);

        $response->assertSessionHasErrors([
            'tipo_solicitud', 'rep_tipo_documento', 'rep_numero_documento',
            'rep_nombres', 'rep_apellidos', 'rep_email',
            'alumno_tipo_documento', 'alumno_numero_documento',
            'alumno_nombres', 'alumno_apellidos',
            'curso_id', 'paralelo_id',
        ]);
    }

    public function test_store_guarda_todos_los_campos(): void
    {
        Pdf::shouldReceive('loadView')->once()->andReturnSelf();
        Pdf::shouldReceive('save')->once()->andReturnSelf();
        Pdf::shouldReceive('download')->once()->andReturn(response('pdf', 200));

        $data = $this->validFormData();
        $data['alumno_etnia'] = 'Mestiza';
        $data['alumno_nacionalidad'] = 'Ecuatoriana';
        $data['alumno_padece_enfermedad'] = true;
        $data['alumno_nombre_enfermedad'] = 'Asma';
        $data['vivienda_casa_propia'] = true;
        $data['padre_nombres_apellidos'] = 'Carlos Pérez Sr.';

        $this->post('/pre-registro', $data);

        $this->assertDatabaseHas('pre_registros', [
            'alumno_etnia' => 'Mestiza',
            'alumno_nacionalidad' => 'Ecuatoriana',
            'alumno_padece_enfermedad' => 1,
            'alumno_nombre_enfermedad' => 'Asma',
            'vivienda_casa_propia' => 1,
            'padre_nombres_apellidos' => 'Carlos Pérez Sr.',
        ]);
    }

    public function test_store_falla_con_email_invalido(): void
    {
        $data = $this->validFormData();
        $data['rep_email'] = 'no-es-un-email';

        $response = $this->post('/pre-registro', $data);

        $response->assertSessionHasErrors('rep_email');
    }

    public function test_store_falla_con_curso_inexistente(): void
    {
        $data = $this->validFormData();
        $data['curso_id'] = 9999;

        $response = $this->post('/pre-registro', $data);

        $response->assertSessionHasErrors('curso_id');
    }
}
