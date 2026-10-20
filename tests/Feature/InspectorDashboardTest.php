<?php

namespace Tests\Feature;

use App\Models\Alumno;
use App\Models\Cita;
use App\Models\Mensaje;
use App\Models\PeriodoLectivo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InspectorDashboardTest extends TestCase
{
    use RefreshDatabase;

    private function inspector(): User
    {
        return User::factory()->create(['rol' => 'INSPECCION']);
    }

    private function mensaje(User $emisor, User $destinatario, bool $leido = false): Mensaje
    {
        return Mensaje::create([
            'asunto' => 'Consulta',
            'cuerpo_contenido' => 'Hola',
            'carpeta' => 'enviados',
            'emisor_id' => $emisor->id,
            'emisor_type' => User::class,
            'destinatario_id' => $destinatario->id,
            'leido' => $leido,
            'fecha_envio' => now(),
        ]);
    }

    public function test_el_dashboard_del_inspector_muestra_sus_tres_tarjetas(): void
    {
        PeriodoLectivo::factory()->create(['activo' => true]);
        $inspector = $this->inspector();

        $this->actingAs($inspector)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Panel de Inspección')
            ->assertSee('Mensajes de representantes sin leer')
            ->assertSee('Citas agendadas para hoy')
            ->assertSee('Circulares publicadas');
    }

    public function test_solo_cuenta_mensajes_sin_leer_de_representantes(): void
    {
        PeriodoLectivo::factory()->create(['activo' => true]);
        $inspector = $this->inspector();
        $representante = User::factory()->representante()->create();
        $docente = User::factory()->docente()->create();

        // Cuenta: representante, sin leer.
        $this->mensaje($representante, $inspector, false);
        // No cuenta: docente, sin leer.
        $this->mensaje($docente, $inspector, false);
        // No cuenta: representante, ya leido.
        $this->mensaje($representante, $inspector, true);

        $this->actingAs($inspector)
            ->get('/dashboard')
            ->assertOk()
            ->assertViewHas('mensajes_representantes', 1);
    }

    public function test_cuenta_las_citas_de_hoy_del_inspector(): void
    {
        PeriodoLectivo::factory()->create(['activo' => true]);
        $inspector = $this->inspector();
        $otroDocente = User::factory()->docente()->create();
        $representante = User::factory()->representante()->create();
        $alumno = Alumno::factory()->create();

        // Cuenta: hoy, el inspector es quien cita.
        Cita::create([
            'solicitante_type' => User::class,
            'solicitante_id' => $inspector->id,
            'docente_id' => $inspector->id,
            'alumno_id' => $alumno->id,
            'fecha_inicio' => now()->setTime(9, 0),
            'fecha_fin' => now()->setTime(10, 0),
            'estado' => 'pendiente',
            'motivo' => 'Reunión de padres',
        ]);

        // Cuenta: hoy, el inspector la solicitó.
        Cita::create([
            'solicitante_type' => User::class,
            'solicitante_id' => $inspector->id,
            'docente_id' => $otroDocente->id,
            'alumno_id' => $alumno->id,
            'fecha_inicio' => now()->setTime(11, 0),
            'fecha_fin' => now()->setTime(12, 0),
            'estado' => 'pendiente',
            'motivo' => 'Seguimiento académico',
        ]);

        // No cuenta: hoy, pero de otro docente y otro solicitante.
        Cita::create([
            'solicitante_type' => User::class,
            'solicitante_id' => $representante->id,
            'docente_id' => $otroDocente->id,
            'alumno_id' => $alumno->id,
            'fecha_inicio' => now()->setTime(13, 0),
            'fecha_fin' => now()->setTime(14, 0),
            'estado' => 'pendiente',
            'motivo' => 'Otro',
        ]);

        // No cuenta: del inspector pero manana.
        Cita::create([
            'solicitante_type' => User::class,
            'solicitante_id' => $inspector->id,
            'docente_id' => $inspector->id,
            'alumno_id' => $alumno->id,
            'fecha_inicio' => now()->addDay()->setTime(9, 0),
            'fecha_fin' => now()->addDay()->setTime(10, 0),
            'estado' => 'pendiente',
            'motivo' => 'Reunión de padres',
        ]);

        $this->actingAs($inspector)
            ->get('/dashboard')
            ->assertOk()
            ->assertViewHas('citas_hoy', fn ($citas) => $citas->count() === 2)
            ->assertSee('Reunión de padres');
    }
}
