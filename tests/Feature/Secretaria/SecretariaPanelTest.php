<?php

namespace Tests\Feature\Secretaria;

use App\Models\Alumno;
use App\Models\Curso;
use App\Models\Institucion;
use App\Models\Matricula;
use App\Models\Paralelo;
use App\Models\PeriodoLectivo;
use App\Models\User;
use Database\Seeders\AdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecretariaPanelTest extends TestCase
{
    use RefreshDatabase;

    private User $secretaria;

    private User $rector;

    protected function setUp(): void
    {
        parent::setUp();

        Institucion::firstOrCreate([], [
            'nombre' => 'Unidad Educativa',
            'zona_horaria' => '-05:00',
        ]);

        $this->secretaria = User::factory()->secretaria()->create();
        $this->rector = User::factory()->admin()->create();
    }

    /** @test */
    public function el_dashboard_de_secretaria_es_distinto_al_de_rector()
    {
        $response = $this->actingAs($this->secretaria)->get('/dashboard');

        $response->assertOk();
        $response->assertViewIs('dashboard.secretaria');
        $response->assertSee('Panel de Secretaría');
        $response->assertSee('Fichas por regularizar');
        $response->assertDontSee('Panel de Rectorado');
    }

    /** @test */
    public function el_rector_conserva_su_dashboard()
    {
        $this->actingAs($this->rector)
            ->get('/dashboard')
            ->assertOk()
            ->assertViewIs('dashboard.rector')
            ->assertSee('Panel de Rectorado');
    }

    /** @test */
    public function secretaria_revisa_solicitudes_y_matriculas()
    {
        $this->actingAs($this->secretaria)
            ->get(route('admin.pre-registros.index'))
            ->assertOk();

        $matricula = Matricula::factory()->create();

        $this->actingAs($this->secretaria)
            ->get(route('admin.matriculas.edit', $matricula->id))
            ->assertOk();
    }

    /** @test */
    public function secretaria_no_entra_a_la_configuracion_del_sistema()
    {
        $this->actingAs($this->secretaria)
            ->get(route('admin.configuracion.index'))
            ->assertForbidden();
    }

    /** @test */
    public function secretaria_consulta_observaciones_pero_no_las_crea()
    {
        $this->actingAs($this->secretaria)
            ->get(route('inspeccion.observaciones.reporte.alumno'))
            ->assertOk();

        $this->actingAs($this->secretaria)
            ->get(route('inspeccion.observaciones.estadisticas'))
            ->assertOk();

        $this->actingAs($this->secretaria)
            ->post(route('inspeccion.observaciones.store'), [])
            ->assertForbidden();

        $this->actingAs($this->secretaria)
            ->get(route('inspeccion.observaciones.tipos.index'))
            ->assertForbidden();
    }

    /** @test */
    public function secretaria_consulta_asistencias_pero_no_las_toma()
    {
        $this->actingAs($this->secretaria)
            ->get(route('inspeccion.asistencia.estadisticas'))
            ->assertOk();

        $this->actingAs($this->secretaria)
            ->get(route('inspeccion.asistencia.index'))
            ->assertForbidden();
    }

    /** @test */
    public function secretaria_envia_mensajes_masivos_a_los_representantes()
    {
        $curso = Curso::factory()->create();
        $paralelo = Paralelo::factory()->create();
        $periodo = PeriodoLectivo::factory()->create(['activo' => true]);

        $estudiante = User::factory()->estudiante()->create();
        $representante = User::factory()->representante()->create();
        $alumno = Alumno::factory()->create([
            'user_id' => $estudiante->id,
            'representante_user_id' => $representante->id,
        ]);

        Matricula::factory()->create([
            'alumno_id' => $alumno->id,
            'curso_id' => $curso->id,
            'paralelo_id' => $paralelo->id,
            'periodo_id' => $periodo->id,
        ]);

        $this->actingAs($this->secretaria)
            ->post(route('mensajes.store'), [
                'asunto' => 'Circular de secretaría',
                'cuerpo_contenido' => 'Recordatorio de matrícula.',
                'alcance' => 'curso',
                'curso_id' => $curso->id,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('mensajes', [
            'emisor_id' => $this->secretaria->id,
            'destinatario_id' => $estudiante->id,
            'asunto' => 'Circular de secretaría',
        ]);

        $this->assertDatabaseHas('mensajes', [
            'emisor_id' => $this->secretaria->id,
            'destinatario_id' => $representante->id,
            'asunto' => 'Circular de secretaría',
        ]);
    }

    /** @test */
    public function el_seeder_crea_la_cuenta_de_secretaria_con_su_rol()
    {
        $this->seed(AdminSeeder::class);

        $this->assertDatabaseHas('users', [
            'email' => 'secretaria@fake.edu.ec',
            'rol' => 'SECRETARIA',
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'rector@fake.edu.ec',
            'rol' => 'ADMIN',
        ]);
    }

    /** @test */
    public function secretaria_dirige_una_cita_a_una_autoridad()
    {
        $alumno = Alumno::factory()->create();

        $this->actingAs($this->secretaria)
            ->post(route('inspeccion.citas.store'), [
                'alumno_id' => $alumno->id,
                'fecha' => now()->addDay()->format('Y-m-d'),
                'hora' => 9,
                'minuto' => 0,
                'motivo' => 'Reunión de padres',
                'dirigido_a_id' => $this->rector->id,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('citas', [
            'alumno_id' => $alumno->id,
            'docente_id' => $this->rector->id,
            'solicitante_id' => $this->secretaria->id,
        ]);
    }
}
