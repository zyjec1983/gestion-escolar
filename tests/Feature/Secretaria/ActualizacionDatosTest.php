<?php

namespace Tests\Feature\Secretaria;

use App\Models\Alumno;
use App\Models\RepresentanteCambio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActualizacionDatosTest extends TestCase
{
    use RefreshDatabase;

    private User $secretaria;

    private User $representante;

    private User $otroRepresentante;

    private Alumno $alumno;

    protected function setUp(): void
    {
        parent::setUp();

        $this->secretaria = User::factory()->create(['rol' => 'SECRETARIA']);

        $this->representante = User::factory()->representante()->create([
            'name' => 'Rosa',
            'apellidos' => 'Quishpe',
            'email' => 'rosa@correo.test',
            'numero_cedula' => '1711111111',
            'fecha_nacimiento' => '1985-04-12',
        ]);

        $this->otroRepresentante = User::factory()->representante()->create([
            'name' => 'Juan',
            'apellidos' => 'Cruz',
            'email' => 'juan@correo.test',
            'numero_cedula' => '1722222222',
            'fecha_nacimiento' => '1980-09-30',
        ]);

        $this->alumno = Alumno::factory()->create([
            'nombres' => 'Ana',
            'apellidos' => 'Betancourt',
            'numero_cedula' => '1712345678',
            'representante_user_id' => $this->representante->id,
        ]);
    }

    /** Datos minimos validos para guardar la ficha. */
    private function payload(array $extra = []): array
    {
        return array_merge([
            'nombres' => 'Ana',
            'apellidos' => 'Betancourt',
        ], $extra);
    }

    public function test_la_secretaria_accede_al_buscador(): void
    {
        $this->actingAs($this->secretaria)
            ->get(route('actualizacion.index'))
            ->assertOk()
            ->assertSee('Actualizaci&oacute;n de datos', false);
    }

    public function test_el_buscador_encuentra_al_alumno_por_cedula(): void
    {
        $this->actingAs($this->secretaria)
            ->get(route('actualizacion.index', ['cedula' => '1712345678']))
            ->assertOk()
            ->assertSee('Betancourt');
    }

    public function test_el_buscador_encuentra_al_alumno_por_apellidos(): void
    {
        $this->actingAs($this->secretaria)
            ->get(route('actualizacion.index', ['apellidos' => 'Betanc']))
            ->assertOk()
            ->assertSee('Ana');
    }

    public function test_el_buscador_informa_cuando_no_encuentra_nada(): void
    {
        $this->actingAs($this->secretaria)
            ->get(route('actualizacion.index', ['cedula' => '0000000000']))
            ->assertOk()
            ->assertSee('No se encontraron alumnos');
    }

    public function test_la_secretaria_ve_la_ficha_del_alumno(): void
    {
        $this->actingAs($this->secretaria)
            ->get(route('actualizacion.edit', $this->alumno->id))
            ->assertOk()
            ->assertSee('Betancourt')
            ->assertSee('Rosa');
    }

    public function test_el_tipo_de_sangre_se_elige_de_una_lista(): void
    {
        $response = $this->actingAs($this->secretaria)
            ->get(route('actualizacion.edit', $this->alumno->id));

        $response->assertOk();
        $response->assertSee('<select name="tipo_sangre"', false);
        $response->assertDontSee('<input type="text" name="tipo_sangre"', false);

        foreach (Alumno::TIPOS_SANGRE as $tipo) {
            $response->assertSee('<option value="' . $tipo . '"', false);
        }
    }

    public function test_guarda_el_tipo_de_sangre_del_catalogo(): void
    {
        $this->actingAs($this->secretaria)
            ->put(route('actualizacion.update', $this->alumno->id), $this->payload([
                'tipo_sangre' => 'AB-',
            ]))
            ->assertSessionHas('success');

        $this->assertSame('AB-', $this->alumno->refresh()->tipo_sangre);
    }

    public function test_no_guarda_un_tipo_de_sangre_fuera_del_catalogo(): void
    {
        $this->actingAs($this->secretaria)
            ->put(route('actualizacion.update', $this->alumno->id), $this->payload([
                'tipo_sangre' => 'O positivo',
            ]))
            ->assertSessionHasErrors('tipo_sangre');

        $this->assertNull($this->alumno->refresh()->tipo_sangre);
    }

    public function test_los_tipos_de_sangre_no_dependen_de_las_mayusculas_tecleadas(): void
    {
        $this->actingAs($this->secretaria)
            ->put(route('actualizacion.update', $this->alumno->id), $this->payload([
                'tipo_sangre' => 'o+',
            ]))
            ->assertSessionHasErrors('tipo_sangre');
    }

    public function test_actualiza_cualquier_dato_del_alumno(): void
    {
        $this->actingAs($this->secretaria)
            ->put(route('actualizacion.update', $this->alumno->id), $this->payload([
                'nombres' => 'Ana Maria',
                'apellidos' => 'Betancourt Vega',
                'celular' => '0999999999',
                'padece_enfermedad' => '1',
                'nombre_enfermedad' => 'Asma',
                'representante_user_id' => $this->representante->id,
            ]))
            ->assertRedirect(route('actualizacion.edit', $this->alumno->id))
            ->assertSessionHas('success');

        $this->alumno->refresh();

        $this->assertSame('Ana Maria', $this->alumno->nombres);
        $this->assertSame('Betancourt Vega', $this->alumno->apellidos);
        $this->assertSame('0999999999', $this->alumno->celular);
        $this->assertTrue((bool) $this->alumno->padece_enfermedad);
        $this->assertSame('Asma', $this->alumno->nombre_enfermedad);
    }

    public function test_los_campos_no_enviados_se_dejan_como_estan(): void
    {
        $this->actingAs($this->secretaria)
            ->put(route('actualizacion.update', $this->alumno->id), $this->payload([
                'nombres' => 'Ana',
                'apellidos' => 'Betancourt',
                'representante_user_id' => $this->representante->id,
            ]))
            ->assertRedirect();

        $this->assertFalse((bool) $this->alumno->fresh()->padece_enfermedad);
    }

    public function test_actualiza_los_datos_del_representante_sin_registrar_cambio(): void
    {
        $this->actingAs($this->secretaria)
            ->put(route('actualizacion.update', $this->alumno->id), $this->payload([
                'representante_user_id' => $this->representante->id,
                'representante_nombres' => 'Rosa Elena',
                'representante_apellidos' => 'Quishpe',
                'representante_cedula' => '1711111111',
                'representante_telefono' => '0988888888',
                'representante_email' => 'rosa.elena@correo.test',
                'representante_fecha_nacimiento' => '1985-04-12',
            ]))
            ->assertRedirect();

        $this->representante->refresh();

        $this->assertSame('Rosa Elena', $this->representante->name);
        $this->assertSame('0988888888', $this->representante->telefono);
        $this->assertSame('rosa.elena@correo.test', $this->representante->email);
        $this->assertSame($this->representante->id, $this->alumno->fresh()->representante_user_id);
        $this->assertSame(0, RepresentanteCambio::count());
    }

    public function test_cambia_al_representante_legal_y_queda_registrado(): void
    {
        $this->actingAs($this->secretaria)
            ->put(route('actualizacion.update', $this->alumno->id), $this->payload([
                'representante_user_id' => $this->otroRepresentante->id,
            ]))
            ->assertRedirect(route('actualizacion.edit', $this->alumno->id));

        $this->assertSame($this->otroRepresentante->id, $this->alumno->fresh()->representante_user_id);

        $this->assertSame(1, RepresentanteCambio::count());

        $cambio = RepresentanteCambio::first();
        $this->assertSame($this->alumno->id, $cambio->alumno_id);
        $this->assertSame($this->representante->id, $cambio->representante_anterior_id);
        $this->assertSame($this->otroRepresentante->id, $cambio->representante_nuevo_id);
        $this->assertSame($this->secretaria->id, $cambio->registrado_por);
    }

    public function test_crea_la_cuenta_del_representante_nuevo_y_lo_asigna(): void
    {
        $this->actingAs($this->secretaria)
            ->put(route('actualizacion.update', $this->alumno->id), $this->payload([
                'crear_representante' => '1',
                'representante_nombres' => 'Marta',
                'representante_apellidos' => 'Nunes',
                'representante_cedula' => '1733333333',
                'representante_telefono' => '0977777777',
                'representante_email' => 'marta@correo.test',
                'representante_fecha_nacimiento' => '1979-02-20',
            ]))
            ->assertRedirect();

        $nuevo = User::where('email', 'marta@correo.test')->first();

        $this->assertNotNull($nuevo);
        $this->assertSame('REPRESENTANTE', $nuevo->rol);
        $this->assertSame('Nunes', $nuevo->apellidos);
        $this->assertSame($nuevo->id, $this->alumno->fresh()->representante_user_id);
        $this->assertSame($nuevo->id, RepresentanteCambio::first()->representante_nuevo_id);
    }

    public function test_no_acepta_un_representante_nuevo_menor_de_edad(): void
    {
        $this->actingAs($this->secretaria)
            ->put(route('actualizacion.update', $this->alumno->id), $this->payload([
                'crear_representante' => '1',
                'representante_nombres' => 'Pablo',
                'representante_apellidos' => ' menor',
                'representante_email' => 'pablo@correo.test',
                'representante_fecha_nacimiento' => '2012-05-05',
            ]))
            ->assertSessionHasErrors('representante_fecha_nacimiento');

        $this->assertNull(User::where('email', 'pablo@correo.test')->first());
        $this->assertSame($this->representante->id, $this->alumno->fresh()->representante_user_id);
    }

    public function test_exige_fecha_de_nacimiento_para_crear_al_representante(): void
    {
        $this->actingAs($this->secretaria)
            ->put(route('actualizacion.update', $this->alumno->id), $this->payload([
                'crear_representante' => '1',
                'representante_nombres' => 'Pablo',
                'representante_email' => 'pablo@correo.test',
            ]))
            ->assertSessionHasErrors('representante_fecha_nacimiento');
    }

    public function test_no_asigna_como_representante_legal_a_un_menor_de_edad(): void
    {
        $menor = User::factory()->representante()->create([
            'fecha_nacimiento' => '2010-03-03',
        ]);

        $this->actingAs($this->secretaria)
            ->put(route('actualizacion.update', $this->alumno->id), $this->payload([
                'representante_user_id' => $menor->id,
            ]))
            ->assertSessionHasErrors('representante_user_id');

        $this->assertSame($this->representante->id, $this->alumno->fresh()->representante_user_id);
    }

    public function test_no_asigna_como_representante_legal_a_una_cuenta_sin_fecha_de_nacimiento(): void
    {
        $sinFecha = User::factory()->representante()->create(['fecha_nacimiento' => null]);

        $this->actingAs($this->secretaria)
            ->put(route('actualizacion.update', $this->alumno->id), $this->payload([
                'representante_user_id' => $sinFecha->id,
            ]))
            ->assertSessionHasErrors('representante_user_id');
    }

    public function test_permite_conservar_al_representante_actual_y_completar_su_fecha(): void
    {
        $this->representante->update(['fecha_nacimiento' => null]);

        $this->actingAs($this->secretaria)
            ->put(route('actualizacion.update', $this->alumno->id), $this->payload([
                'representante_user_id' => $this->representante->id,
                'representante_nombres' => 'Rosa',
                'representante_apellidos' => 'Quishpe',
                'representante_cedula' => '1711111111',
                'representante_email' => 'rosa@correo.test',
                'representante_fecha_nacimiento' => '1985-04-12',
            ]))
            ->assertRedirect();

        $this->assertSame('1985-04-12', $this->representante->fresh()->fecha_nacimiento->format('Y-m-d'));
        $this->assertSame($this->representante->id, $this->alumno->fresh()->representante_user_id);
        $this->assertSame(0, RepresentanteCambio::count());
    }

    public function test_no_acepta_una_cedula_ya_registrada_en_otro_representante(): void
    {
        $this->actingAs($this->secretaria)
            ->put(route('actualizacion.update', $this->alumno->id), $this->payload([
                'representante_user_id' => $this->representante->id,
                'representante_nombres' => 'Rosa',
                'representante_email' => 'rosa@correo.test',
                'representante_cedula' => '1722222222',
            ]))
            ->assertSessionHasErrors('representante_cedula');

        $this->assertSame('1711111111', $this->representante->fresh()->numero_cedula);
    }

    public function test_no_acepta_una_cedula_ya_registrada_al_crear_el_representante(): void
    {
        $this->actingAs($this->secretaria)
            ->put(route('actualizacion.update', $this->alumno->id), $this->payload([
                'crear_representante' => '1',
                'representante_nombres' => 'Marta',
                'representante_email' => 'marta@correo.test',
                'representante_cedula' => '1722222222',
                'representante_fecha_nacimiento' => '1979-02-20',
            ]))
            ->assertSessionHasErrors('representante_cedula');
    }

    public function test_no_acepta_un_correo_ya_registrado_al_crear_el_representante(): void
    {
        $this->actingAs($this->secretaria)
            ->put(route('actualizacion.update', $this->alumno->id), $this->payload([
                'crear_representante' => '1',
                'representante_nombres' => 'Marta',
                'representante_email' => 'juan@correo.test',
                'representante_fecha_nacimiento' => '1979-02-20',
            ]))
            ->assertSessionHasErrors('representante_email');
    }

    public function test_no_acepta_como_representante_a_alguien_que_no_lo_es(): void
    {
        $docente = User::factory()->docente()->create();

        $this->actingAs($this->secretaria)
            ->put(route('actualizacion.update', $this->alumno->id), $this->payload([
                'representante_user_id' => $docente->id,
            ]))
            ->assertSessionHasErrors('representante_user_id');

        $this->assertSame($this->representante->id, $this->alumno->fresh()->representante_user_id);
    }

    public function test_el_docente_no_puede_entrar_al_modulo(): void
    {
        $this->actingAs(User::factory()->docente()->create())
            ->get(route('actualizacion.index'))
            ->assertForbidden();
    }

    public function test_el_representante_no_puede_entrar_al_modulo(): void
    {
        $this->actingAs($this->representante)
            ->get(route('actualizacion.index'))
            ->assertForbidden();
    }

    public function test_se_exige_iniciar_sesion(): void
    {
        $this->get(route('actualizacion.index'))->assertRedirect(route('login'));
    }

    public function test_la_ficha_muestra_el_historial_de_cambios(): void
    {
        RepresentanteCambio::create([
            'alumno_id' => $this->alumno->id,
            'representante_anterior_id' => $this->otroRepresentante->id,
            'representante_nuevo_id' => $this->representante->id,
            'registrado_por' => $this->secretaria->id,
        ]);

        $this->actingAs($this->secretaria)
            ->get(route('actualizacion.edit', $this->alumno->id))
            ->assertOk()
            ->assertSee('Cambios de representante legal')
            ->assertSee('Juan');
    }

    public function test_las_fichas_por_regularizar_no_exigen_cedula_ni_apellido(): void
    {
        $this->representante->update(['fecha_nacimiento' => null]);

        $this->actingAs($this->secretaria)
            ->get(route('actualizacion.index', ['pendientes' => 1]))
            ->assertOk()
            ->assertSee('Betancourt')
            ->assertSee('falta fecha de nacimiento');
    }

    public function test_las_fichas_por_regularizar_detectan_menores_de_edad(): void
    {
        $this->representante->update(['fecha_nacimiento' => '2015-01-01']);

        $this->actingAs($this->secretaria)
            ->get(route('actualizacion.index', ['pendientes' => 1]))
            ->assertOk()
            ->assertSee('no es mayor de edad');
    }

    public function test_una_ficha_al_dia_no_aparece_entre_las_pendientes(): void
    {
        $otroAlumno = Alumno::factory()->create([
            'apellidos' => 'Zuniga',
            'representante_user_id' => $this->otroRepresentante->id,
        ]);

        $this->actingAs($this->secretaria)
            ->get(route('actualizacion.index', ['pendientes' => 1]))
            ->assertOk()
            ->assertDontSee('Zuniga');
    }

    public function test_regularizar_la_ficha_saca_al_alumno_de_las_pendientes(): void
    {
        $this->representante->update(['fecha_nacimiento' => null]);

        $this->actingAs($this->secretaria)
            ->put(route('actualizacion.update', $this->alumno->id), $this->payload([
                'representante_user_id' => $this->representante->id,
                'representante_nombres' => 'Rosa',
                'representante_apellidos' => 'Quishpe',
                'representante_cedula' => '1711111111',
                'representante_email' => 'rosa@correo.test',
                'representante_fecha_nacimiento' => '1985-04-12',
            ]))
            ->assertRedirect();

        $this->actingAs($this->secretaria)
            ->get(route('actualizacion.index', ['pendientes' => 1]))
            ->assertOk()
            ->assertSee('No hay fichas por regularizar');
    }
}
