<?php

namespace Tests\Feature\Admin;

use App\Models\Curso;
use App\Models\Institucion;
use App\Models\Paralelo;
use App\Models\PeriodoLectivo;
use App\Models\PreRegistro;
use App\Models\User;
use App\Notifications\SolicitudAprobada;
use App\Notifications\SolicitudRechazada;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PreRegistroAdminTest extends TestCase
{
    private User $admin;
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
    }

    private function actingAsAdmin(): void
    {
        $this->actingAs($this->admin);
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

    /** @test */
    public function unauthenticated_user_cannot_access_admin()
    {
        $response = $this->get(route('admin.pre-registros.index'));
        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function non_admin_user_gets_403()
    {
        $docente = User::factory()->docente()->create();
        $this->actingAs($docente);

        $response = $this->get(route('admin.pre-registros.index'));
        $response->assertForbidden();
    }

    /** @test */
    public function index_shows_pending_pre_registros()
    {
        $this->actingAsAdmin();
        $pre = PreRegistro::factory()->create();

        $response = $this->get(route('admin.pre-registros.index'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.pre-registros.index');
        $response->assertSee($pre->rep_nombres);
    }

    /** @test */
    public function index_filters_by_status()
    {
        $this->actingAsAdmin();
        $aprobado = PreRegistro::factory()->aprobado()->create();
        $pendiente = PreRegistro::factory()->create();

        $response = $this->get(route('admin.pre-registros.index', ['filtro' => 'aprobado']));

        $response->assertStatus(200);
        $response->assertSee($aprobado->rep_nombres);
        $response->assertDontSee($pendiente->rep_nombres);
    }

    /** @test */
    public function show_displays_pre_registro_detail()
    {
        $this->actingAsAdmin();
        $pre = PreRegistro::factory()->create();

        $response = $this->get(route('admin.pre-registros.show', $pre->id));

        $response->assertStatus(200);
        $response->assertViewIs('admin.pre-registros.show');
        $response->assertSee($pre->rep_nombres);
    }

    /** @test */
    public function toggle_activates_and_deactivates_registration()
    {
        $this->actingAsAdmin();
        $institucion = Institucion::first();
        $institucion->update(['pre_registro_activo' => false]);

        $response = $this->post(route('admin.pre-registros.toggle'));
        $response->assertSessionHas('success');
        $this->assertTrue($institucion->fresh()->pre_registro_activo);

        $response = $this->post(route('admin.pre-registros.toggle'));
        $this->assertFalse($institucion->fresh()->pre_registro_activo);
    }

    /** @test */
    public function create_form_shows_admin_mode()
    {
        $this->actingAsAdmin();

        $response = $this->get(route('admin.pre-registros.create'));

        $response->assertStatus(200);
        $response->assertViewIs('pre-registro.form');
        $response->assertViewHas('adminMode', true);
    }

    /** @test */
    public function store_manual_creates_pre_registro()
    {
        $this->actingAsAdmin();

        Pdf::shouldReceive('loadView')->once()->andReturnSelf();
        Pdf::shouldReceive('save')->once()->andReturnSelf();

        $response = $this->post(route('admin.pre-registros.store-manual'), $this->validFormData());

        $response->assertRedirect();
        $this->assertDatabaseHas('pre_registros', [
            'rep_email' => 'carlos@example.com',
            'registrado_por' => $this->admin->id,
        ]);
    }

    /** @test */
    public function store_manual_validates_required_fields()
    {
        $this->actingAsAdmin();

        $response = $this->post(route('admin.pre-registros.store-manual'), []);

        $response->assertSessionHasErrors([
            'tipo_solicitud', 'rep_tipo_documento', 'rep_numero_documento',
            'rep_nombres', 'rep_apellidos', 'rep_email',
        ]);
    }

    /** @test */
    public function aprobar_creates_user_alumno_y_matricula()
    {
        $this->actingAsAdmin();
        Notification::fake();

        $pre = PreRegistro::factory()->create([
            'rep_email' => 'representante@test.com',
            'rep_nombres' => 'Carlos',
            'rep_apellidos' => 'Pérez',
            'alumno_nombres' => 'Juanito',
            'alumno_apellidos' => 'Pérez',
        ]);

        $response = $this->post(route('admin.pre-registros.aprobar', $pre->id));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'email' => 'representante@test.com',
            'rol' => 'REPRESENTANTE',
        ]);

        $user = User::where('email', 'representante@test.com')->first();
        $this->assertNotNull($user);

        $this->assertDatabaseHas('alumnos', [
            'nombres' => 'Juanito',
            'apellidos' => 'Pérez',
            'representante_user_id' => $user->id,
        ]);

        $alumno = $user->representados()->first();
        $this->assertNotNull($alumno);

        $this->assertDatabaseHas('matriculas', [
            'alumno_id' => $alumno->id,
            'curso_id' => $pre->curso_id,
            'paralelo_id' => $pre->paralelo_id,
        ]);

        $this->assertDatabaseHas('pre_registros', [
            'id' => $pre->id,
            'estado' => 'aprobado',
            'aprobado_por' => $this->admin->id,
        ]);

        Notification::assertSentTo(
            $user,
            SolicitudAprobada::class
        );
    }

    /** @test */
    public function aprobar_reuses_existing_user_by_email()
    {
        $this->actingAsAdmin();
        Notification::fake();

        $existingUser = User::factory()->create([
            'email' => 'existente@test.com',
        ]);

        $pre = PreRegistro::factory()->create([
            'rep_email' => 'existente@test.com',
            'rep_nombres' => 'Carlos',
            'rep_apellidos' => 'Pérez',
        ]);

        $this->post(route('admin.pre-registros.aprobar', $pre->id));

        $this->assertDatabaseCount('users', 2);

        Notification::assertSentTo(
            $existingUser,
            SolicitudAprobada::class
        );
    }

    /** @test */
    public function aprobar_fails_if_already_processed()
    {
        $this->actingAsAdmin();
        $pre = PreRegistro::factory()->aprobado()->create();

        $response = $this->post(route('admin.pre-registros.aprobar', $pre->id));

        $response->assertSessionHas('error');
        $this->assertDatabaseHas('pre_registros', [
            'id' => $pre->id,
            'estado' => 'aprobado',
        ]);
    }

    /** @test */
    public function rechazar_updates_status_and_sends_notification()
    {
        $this->actingAsAdmin();
        Notification::fake();

        $pre = PreRegistro::factory()->create([
            'rep_email' => 'rechazado@test.com',
        ]);

        $response = $this->post(route('admin.pre-registros.rechazar', $pre->id), [
            'comentario_rechazo' => 'Documentación incompleta',
        ]);

        $response->assertSessionHas('success');

        $this->assertDatabaseHas('pre_registros', [
            'id' => $pre->id,
            'estado' => 'rechazado',
            'comentario_rechazo' => 'Documentación incompleta',
        ]);

        $notifiable = new AnonymousNotifiable;
        $notifiable->route('mail', 'rechazado@test.com');
        Notification::assertSentTo(
            $notifiable,
            SolicitudRechazada::class
        );
    }

    /** @test */
    public function rechazar_requires_comment()
    {
        $this->actingAsAdmin();
        $pre = PreRegistro::factory()->create();

        $response = $this->post(route('admin.pre-registros.rechazar', $pre->id), []);

        $response->assertSessionHasErrors('comentario_rechazo');
    }

    /** @test */
    public function rechazar_fails_if_already_processed()
    {
        $this->actingAsAdmin();
        $pre = PreRegistro::factory()->rechazado()->create();

        $response = $this->post(route('admin.pre-registros.rechazar', $pre->id), [
            'comentario_rechazo' => 'Ya está rechazado',
        ]);

        $response->assertSessionHas('error');
    }

    /** @test */
    public function pdf_download_works()
    {
        $this->actingAsAdmin();

        Pdf::shouldReceive('loadView')->once()->andReturnSelf();
        Pdf::shouldReceive('download')->once()->andReturn(response('pdf', 200));

        $pre = PreRegistro::factory()->create();

        $response = $this->get(route('admin.pre-registros.pdf', $pre->id));

        $response->assertStatus(200);
    }
}
