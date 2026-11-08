<?php

namespace Tests\Feature;

use App\Models\Alumno;
use App\Models\Cita;
use App\Models\Curso;
use App\Models\Matricula;
use App\Models\Paralelo;
use App\Models\PeriodoLectivo;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RectorCitaTest extends TestCase
{
    use RefreshDatabase;

    private User $rector;

    private User $representante;

    private Alumno $alumno;

    private Curso $curso;

    protected function setUp(): void
    {
        parent::setUp();

        $this->rector = User::factory()->create([
            'rol' => 'ADMIN',
            'name' => 'RECTOR',
            'apellidos' => 'PRUEBA',
        ]);
        $this->representante = User::factory()->representante()->create();
        $this->alumno = Alumno::factory()->create([
            'representante_user_id' => $this->representante->id,
        ]);

        $periodo = PeriodoLectivo::factory()->create();
        $this->curso = Curso::factory()->create();
        $paralelo = Paralelo::factory()->create();
        Matricula::factory()->create([
            'alumno_id' => $this->alumno->id,
            'curso_id' => $this->curso->id,
            'paralelo_id' => $paralelo->id,
            'periodo_id' => $periodo->id,
        ]);
    }

    private function citaDeRector(Carbon $fecha, string $motivo): Cita
    {
        return Cita::create([
            'solicitante_type' => User::class,
            'solicitante_id' => $this->rector->id,
            'docente_id' => $this->rector->id,
            'alumno_id' => $this->alumno->id,
            'fecha_inicio' => $fecha,
            'fecha_fin' => $fecha->copy()->addHour(),
            'estado' => 'pendiente',
            'motivo' => $motivo,
        ]);
    }

    public function test_rector_admin_puede_ver_agendar_e_historial(): void
    {
        $this->actingAs($this->rector)
            ->get(route('inspeccion.citas.agendar'))
            ->assertOk()
            ->assertSee('Agendar cita a representante');

        $this->actingAs($this->rector)
            ->get(route('inspeccion.citas.historial'))
            ->assertOk()
            ->assertSee('Historial de citas');
    }

    public function test_rector_agenda_cita_atribuida_a_su_nombre(): void
    {
        $response = $this->actingAs($this->rector)->post(route('inspeccion.citas.store'), [
            'alumno_id' => $this->alumno->id,
            'fecha' => Carbon::tomorrow()->format('Y-m-d'),
            'hora' => 10,
            'minuto' => 30,
            'motivo' => 'Seguimiento académico',
        ]);

        $response->assertRedirect()->assertSessionHas('success');

        $cita = Cita::where('alumno_id', $this->alumno->id)->firstOrFail();
        $this->assertSame($this->rector->id, $cita->docente_id);
        $this->assertSame($this->rector->id, $cita->solicitante_id);
    }

    public function test_vista_agendar_muestra_el_nombre_del_rector_no_el_de_inspeccion(): void
    {
        $this->actingAs($this->rector)
            ->get(route('inspeccion.citas.agendar'))
            ->assertOk()
            ->assertSee('RECTOR PRUEBA')
            ->assertDontSee('a nombre de inspección');
    }

    public function test_representante_ve_la_cita_del_rector_con_su_nombre(): void
    {
        $this->citaDeRector(Carbon::now()->addDays(2)->setTime(9, 0), 'Convocatoria oficial del rector');

        $this->actingAs($this->representante)
            ->get(route('representante.citas'))
            ->assertOk()
            ->assertSee('Citado por')
            ->assertSee('RECTOR PRUEBA');
    }

    public function test_historial_filtra_por_rango_de_fechas(): void
    {
        $this->citaDeRector(Carbon::parse('-10 days')->setTime(9, 0), 'MOTIVO_DENTRO_DEL_RANGO');
        $this->citaDeRector(Carbon::parse('-40 days')->setTime(9, 0), 'MOTIVO_FUERA_DEL_RANGO');

        $response = $this->actingAs($this->rector)->get(route('inspeccion.citas.historial', [
            'alumno' => $this->alumno->id,
            'fecha_desde' => Carbon::parse('-20 days')->format('Y-m-d'),
            'fecha_hasta' => Carbon::today()->format('Y-m-d'),
        ]));

        $response->assertOk()
            ->assertSee('MOTIVO_DENTRO_DEL_RANGO')
            ->assertDontSee('MOTIVO_FUERA_DEL_RANGO');
    }

    public function test_secretaria_tiene_acceso_al_modulo_de_citas(): void
    {
        $secretaria = User::factory()->create(['rol' => 'SECRETARIA']);

        $this->actingAs($secretaria)->get(route('inspeccion.citas.agendar'))->assertOk();
        $this->actingAs($secretaria)->get(route('inspeccion.citas.historial'))->assertOk();
    }
}
