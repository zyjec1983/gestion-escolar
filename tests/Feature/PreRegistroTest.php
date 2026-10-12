<?php

namespace Tests\Feature;

use App\Models\Alumno;
use App\Models\Curso;
use App\Models\Institucion;
use App\Models\Paralelo;
use App\Models\PeriodoLectivo;
use App\Models\PreRegistro;
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
        $this->curso = Curso::factory()->create(['nombre' => 'OCTAVO GRADO', 'codigo' => 'OCTA1']);
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
            'rep_fecha_nacimiento' => '1985-03-10',
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
            'grado' => 'OCTAVO',
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

    public function test_los_cursos_salen_en_orden_escolar_y_no_alfabetico(): void
    {
        // Se parte de cero para que la secuencia sea exacta y no dependa del
        // curso que crea el setUp.
        Curso::query()->delete();

        foreach ([
            'SEGUNDO DE BACHILLERATO - CIENCIAS' => '2BC',
            'DECIMO GRADO' => 'DEC',
            'OCTAVO GRADO' => 'OCT',
            'SEGUNDO DE BACHILLERATO - FISICO MATEMATICA' => '2BF',
            'CURSOS SUELTOS' => 'ZZZ',
            'NOVENO GRADO' => 'NOV',
            'TERCERO DE BACHILLERATO' => '3BT',
            'PRIMERO DE BACHILLERATO' => 'P1B',
        ] as $nombre => $codigo) {
            Curso::factory()->create(['nombre' => $nombre, 'codigo' => $codigo]);
        }

        $html = $this->get('/pre-registro')->assertOk()->getContent();

        preg_match('/<select name="grado".*?<\/select>/s', $html, $select);
        preg_match_all('/<option value="([^"]*)"[^>]*>([^<]+)<\/option>/', $select[0], $m, PREG_SET_ORDER);

        $opciones = [];
        foreach ($m as $opcion) {
            if ($opcion[1] === '') {
                continue;
            }
            $opciones[$opcion[1]] = trim($opcion[2]);
        }

        $this->assertSame(
            ['OCTAVO', 'NOVENO', 'DECIMO', 'PRIMERO', 'SEGUNDO', 'TERCERO'],
            array_keys($opciones),
            'El listado va en orden escolar, de basico a bachillerato.'
        );

        $this->assertSame(
            ['Octavo', 'Noveno', 'Décimo', 'Primero', 'Segundo', 'Tercero'],
            array_values($opciones),
            'Se muestra el grado, no el nombre completo del curso.'
        );

        // Cada grado apunta a los cursos que de verdad lo representan, y un
        // curso sin grado reconocible no se ofrece.
        $porGrado = Curso::porGrado();

        $this->assertNull($porGrado['OCTAVO']['cursos'][0]['especializacion']);
        $this->assertSame(
            ['Ciencias', 'Físico-Matemática'],
            array_column($porGrado['SEGUNDO']['cursos'], 'especializacion'),
            'Los dos cursos de Segundo se ofrecen con la especializacion del catalogo.'
        );
        $this->assertSame(1, count($porGrado['TERCERO']['cursos']));
    }

    public function test_la_especializacion_aparece_como_select_deshabilitado_hasta_que_haya_grado(): void
    {
        $this->get('/pre-registro')
            ->assertOk()
            ->assertSee('<select name="especializacion"', false)
            ->assertDontSee('<input type="text" name="especializacion"', false)
            ->assertSee('id="curso_id"', false)
            ->assertSee('id="curso_grado"', false);
    }

    public function test_acepta_una_especializacion_del_catalogo_en_segundo(): void
    {
        Pdf::shouldReceive('loadView')->once()->andReturnSelf();
        Pdf::shouldReceive('save')->once()->andReturnSelf();
        Pdf::shouldReceive('download')->once()->andReturn(response('pdf', 200));

        $data = $this->validFormData();
        $data['curso_id'] = Curso::factory()->create([
            'nombre' => 'SEGUNDO DE BACHILLERATO - CONTABILIDAD',
            'codigo' => '2BC',
        ])->id;
        $data['grado'] = 'SEGUNDO';
        $data['especializacion'] = 'Contabilidad';

        $this->post('/pre-registro', $data)->assertSessionHasNoErrors();

        $this->assertDatabaseHas('pre_registros', ['especializacion' => 'Contabilidad']);
    }

    public function test_rechaza_una_especializacion_que_no_corresponde_al_curso_elegido(): void
    {
        $data = $this->validFormData();
        $data['curso_id'] = Curso::factory()->create([
            'nombre' => 'SEGUNDO DE BACHILLERATO - CIENCIAS',
            'codigo' => '2BC',
        ])->id;
        $data['grado'] = 'SEGUNDO';
        $data['especializacion'] = 'Contabilidad';

        $this->post('/pre-registro', $data)->assertSessionHasErrors('especializacion');
        $this->assertDatabaseCount('pre_registros', 0);
    }

    public function test_rechaza_un_curso_que_no_corresponde_al_grado_elegido(): void
    {
        $data = $this->validFormData();
        $data['grado'] = 'PRIMERO';

        $this->post('/pre-registro', $data)->assertSessionHasErrors('curso_id');
    }

    public function test_segundo_exige_una_especializacion(): void
    {
        $data = $this->validFormData();
        $data['curso_id'] = Curso::factory()->create([
            'nombre' => 'SEGUNDO DE BACHILLERATO - CIENCIAS',
            'codigo' => '2BC',
        ])->id;
        $data['grado'] = 'SEGUNDO';

        $this->post('/pre-registro', $data)->assertSessionHasErrors('especializacion');
    }

    public function test_rechaza_una_especializacion_que_no_esta_en_el_catalogo(): void
    {
        $data = $this->validFormData();
        $data['curso_id'] = Curso::factory()->create([
            'nombre' => 'SEGUNDO DE BACHILLERATO - CIENCIAS',
            'codigo' => '2BC',
        ])->id;
        $data['grado'] = 'SEGUNDO';
        $data['especializacion'] = 'Astronomia';

        $this->post('/pre-registro', $data)->assertSessionHasErrors('especializacion');
        $this->assertDatabaseCount('pre_registros', 0);
    }

    public function test_rechaza_una_especializacion_en_un_grado_que_no_la_pide(): void
    {
        $data = $this->validFormData();
        $data['especializacion'] = 'Contabilidad';

        $this->post('/pre-registro', $data)->assertSessionHasErrors('especializacion');
        $this->assertDatabaseCount('pre_registros', 0);
    }

    public function test_el_curso_solo_pide_especializacion_en_segundo_y_tercero(): void
    {
        $this->assertFalse(Curso::factory()->make(['nombre' => 'OCTAVO GRADO'])->pideEspecializacion());
        $this->assertFalse(Curso::factory()->make(['nombre' => 'NOVENO GRADO'])->pideEspecializacion());
        $this->assertFalse(Curso::factory()->make(['nombre' => 'DECIMO GRADO'])->pideEspecializacion());
        $this->assertFalse(Curso::factory()->make(['nombre' => 'PRIMERO DE BACHILLERATO'])->pideEspecializacion());
        $this->assertTrue(Curso::factory()->make(['nombre' => 'SEGUNDO DE BACHILLERATO - COMERCIO'])->pideEspecializacion());
        $this->assertTrue(Curso::factory()->make(['nombre' => 'TERCERO DE BACHILLERATO - CONTABILIDAD'])->pideEspecializacion());
    }

    public function test_los_nombres_llevan_patron_de_solo_letters_y_las_cedulas_de_texto(): void
    {
        $html = $this->get('/pre-registro')->assertOk()->getContent();

        // Nombres y apellidos: el navegador rechaza el campo si lleva cifras.
        foreach (['rep_nombres', 'rep_apellidos', 'padre_nombres_apellidos', 'madre_nombres_apellidos', 'facturador_nombre', 'alterno_nombre'] as $campo) {
            $this->assertMatchesRegularExpression(
                '/<input[^>]*name="' . $campo . '"[^>]*pattern="[^"]*"/',
                $html,
                "El campo {$campo} deberia validar con pattern que solo admite letras."
            );
        }

        // Cedula y pasaporte: numeros o texto, sin simbolos.
        foreach (['rep_numero_documento', 'alumno_numero_documento', 'alumno_numero_cedula', 'padre_cedula', 'madre_cedula', 'facturador_cedula', 'alterno_cedula'] as $campo) {
            $this->assertMatchesRegularExpression(
                '/<input[^>]*name="' . $campo . '"[^>]*pattern="\[A-Za-z0-9\]\{5,20\}"/',
                $html,
                "El campo {$campo} deberia admitir solo numeros o texto."
            );
        }
    }

    public function test_el_servidor_acepta_una_cedula_con_letras(): void
    {
        Pdf::shouldReceive('loadView')->once()->andReturnSelf();
        Pdf::shouldReceive('save')->once()->andReturnSelf();
        Pdf::shouldReceive('download')->once()->andReturn(response('pdf', 200));

        $data = $this->validFormData();
        $data['alumno_numero_documento'] = 'AB1234567';

        $this->post('/pre-registro', $data)->assertStatus(200);

        $this->assertDatabaseHas('pre_registros', ['alumno_numero_documento' => 'AB1234567']);
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

        $guardado = PreRegistro::where('rep_email', 'carlos@example.com')->firstOrFail();
        $this->assertSame('1985-03-10', $guardado->rep_fecha_nacimiento->format('Y-m-d'));
    }

    public function test_store_exige_la_fecha_de_nacimiento_del_representante(): void
    {
        Pdf::shouldReceive('loadView')->shouldNotReceive('loadView');
        Pdf::shouldReceive('save')->shouldNotReceive('save');
        Pdf::shouldReceive('download')->shouldNotReceive('download');

        $data = $this->validFormData();
        unset($data['rep_fecha_nacimiento']);

        $this->post('/pre-registro', $data)
            ->assertSessionHasErrors('rep_fecha_nacimiento');

        $this->assertDatabaseMissing('pre_registros', ['rep_email' => 'carlos@example.com']);
    }

    public function test_store_no_acepta_un_representante_menor_de_edad(): void
    {
        Pdf::shouldReceive('loadView')->shouldNotReceive('loadView');
        Pdf::shouldReceive('save')->shouldNotReceive('save');
        Pdf::shouldReceive('download')->shouldNotReceive('download');

        $data = $this->validFormData();
        $data['rep_fecha_nacimiento'] = '2012-01-01';

        $this->post('/pre-registro', $data)
            ->assertSessionHasErrors('rep_fecha_nacimiento');

        $this->assertDatabaseMissing('pre_registros', ['rep_email' => 'carlos@example.com']);
    }

    public function test_store_acepta_representante_que_justo_cumple_diesiocho_anos(): void
    {
        Pdf::shouldReceive('loadView')->once()->andReturnSelf();
        Pdf::shouldReceive('save')->once()->andReturnSelf();
        Pdf::shouldReceive('download')->once()->andReturn(response('pdf', 200));

        $data = $this->validFormData();
        $data['rep_fecha_nacimiento'] = now()->subYears(18)->toDateString();

        $this->post('/pre-registro', $data)->assertStatus(200);

        $this->assertDatabaseHas('pre_registros', ['rep_email' => 'carlos@example.com']);
    }

    public function test_store_falla_sin_datos_requeridos(): void
    {
        $response = $this->post('/pre-registro', []);

        $response->assertSessionHasErrors([
            'tipo_solicitud', 'rep_tipo_documento', 'rep_numero_documento',
            'rep_nombres', 'rep_apellidos', 'rep_fecha_nacimiento', 'rep_email',
            'alumno_tipo_documento', 'alumno_numero_documento',
            'alumno_nombres', 'alumno_apellidos',
            'grado', 'curso_id', 'paralelo_id',
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

    public function test_el_formulario_muestra_el_tipo_de_sangre_como_lista_y_no_como_texto(): void
    {
        $response = $this->get('/pre-registro');

        $response->assertOk();
        $response->assertSee('<select name="alumno_tipo_sangre"', false);
        $response->assertDontSee('<input type="text" name="alumno_tipo_sangre"', false);

        foreach (Alumno::TIPOS_SANGRE as $tipo) {
            $response->assertSee('<option value="' . $tipo . '"', false);
        }
    }

    public function test_store_acepta_cada_tipo_de_sangre_del_catalogo(): void
    {
        $i = 0;

        foreach (Alumno::TIPOS_SANGRE as $tipo) {
            $data = $this->validFormData();
            $data['alumno_numero_documento'] = '172345670' . $i++;
            $data['alumno_tipo_sangre'] = $tipo;

            $this->post('/pre-registro', $data)->assertSessionHasNoErrors();

            $this->assertDatabaseHas('pre_registros', ['alumno_tipo_sangre' => $tipo]);
        }

        $this->assertDatabaseCount('pre_registros', count(Alumno::TIPOS_SANGRE));
    }

    public function test_store_rechaza_un_tipo_de_sangre_escrito_a_mano(): void
    {
        $data = $this->validFormData();
        $data['alumno_tipo_sangre'] = 'O positivo';

        $response = $this->post('/pre-registro', $data);

        $response->assertSessionHasErrors('alumno_tipo_sangre');
        $this->assertDatabaseCount('pre_registros', 0);
    }

    public function test_store_rechaza_un_tipo_de_sangre_con_ruido(): void
    {
        $data = $this->validFormData();
        $data['alumno_tipo_sangre'] = 'a+';

        $this->post('/pre-registro', $data)->assertSessionHasErrors('alumno_tipo_sangre');
    }
}
