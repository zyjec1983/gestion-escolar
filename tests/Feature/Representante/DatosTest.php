<?php

namespace Tests\Feature\Representante;

use App\Models\Alumno;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Formulario de datos para representantes ya matriculados.
 *
 * Qué hace: comprueba que cada representante vea y actualice solo las fichas
 * de sus representados, con los campos completos del alumno y sus datos de
 * contacto.
 * Conecta con: App\Http\Controllers\Representante\DatosController.
 * Rutas: GET /mis-datos, GET /mis-datos/{alumno}, PUT /mis-datos/{alumno}.
 */
class DatosTest extends TestCase
{
    use RefreshDatabase;

    private User $representante;

    private User $otroRepresentante;

    private Alumno $alumno;

    protected function setUp(): void
    {
        parent::setUp();

        $this->representante = User::factory()->representante()->create([
            'name' => 'Rosa',
            'apellidos' => 'Quishpe',
            'email' => 'rosa@correo.test',
            'numero_cedula' => '1711111111',
        ]);

        $this->otroRepresentante = User::factory()->representante()->create([
            'name' => 'Juan',
            'apellidos' => 'Cruz',
            'email' => 'juan@correo.test',
            'numero_cedula' => '1722222222',
        ]);

        $this->alumno = Alumno::factory()->create([
            'nombres' => 'Ana',
            'apellidos' => 'Betancourt',
            'numero_cedula' => '1712345678',
            'representante_user_id' => $this->representante->id,
        ]);
    }

    /** Datos mínimos válidos para guardar la ficha. */
    private function payload(array $extra = []): array
    {
        return array_merge([
            'nombres' => 'Ana',
            'apellidos' => 'Betancourt',
            'representante_nombres' => 'Rosa',
            'representante_apellidos' => 'Quishpe',
            'representante_email' => 'rosa@correo.test',
        ], $extra);
    }

    public function test_el_representante_ve_sus_estudiantes(): void
    {
        $this->actingAs($this->representante)
            ->get(route('representante.datos.index'))
            ->assertOk()
            ->assertViewIs('representante.datos.index')
            ->assertSee('Betancourt')
            ->assertSee('Completar datos');
    }

    public function test_el_representante_abre_la_ficha_de_su_representado(): void
    {
        $this->actingAs($this->representante)
            ->get(route('representante.datos.edit', $this->alumno->id))
            ->assertOk()
            ->assertSee('Mis datos:')
            ->assertSee('BETANCOURT')
            ->assertSee('name="tipo_sangre"', false)
            ->assertSee('name="padre_nombres_apellidos"', false)
            ->assertSee('name="madre_nombres_apellidos"', false);
    }

    public function test_el_representante_no_abre_una_ficha_ajena(): void
    {
        $this->actingAs($this->otroRepresentante)
            ->get(route('representante.datos.edit', $this->alumno->id))
            ->assertForbidden();
    }

    public function test_el_representante_actualiza_la_ficha_y_sus_datos_de_contacto(): void
    {
        $this->actingAs($this->representante)
            ->put(route('representante.datos.update', $this->alumno->id), $this->payload([
                'tipo_sangre' => 'O+',
                'padece_enfermedad' => 1,
                'nombre_enfermedad' => 'Asma',
                'padre_nombres_apellidos' => 'Pedro Betancourt',
                'madre_nombres_apellidos' => 'Lucía Vera',
                'representante_telefono' => '0999999999',
                'representante_email' => 'nueva@correo.test',
            ]))
            ->assertRedirect(route('representante.datos.edit', $this->alumno->id))
            ->assertSessionHas('success');

        $this->alumno->refresh();
        $this->representante->refresh();

        $this->assertSame('O+', $this->alumno->tipo_sangre);
        $this->assertTrue((bool) $this->alumno->padece_enfermedad);
        $this->assertSame('ASMA', $this->alumno->nombre_enfermedad);
        $this->assertSame('PEDRO BETANCOURT', $this->alumno->padre_nombres_apellidos);
        $this->assertSame('LUCÍA VERA', $this->alumno->madre_nombres_apellidos);
        $this->assertSame('0999999999', $this->representante->telefono);
        $this->assertSame('nueva@correo.test', $this->representante->email);
    }

    public function test_el_representante_no_actualiza_una_ficha_ajena(): void
    {
        $this->actingAs($this->otroRepresentante)
            ->put(route('representante.datos.update', $this->alumno->id), $this->payload())
            ->assertForbidden();

        $this->assertSame('Ana', $this->alumno->fresh()->nombres);
    }
}
