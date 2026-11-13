<?php

namespace Tests\Feature;

use App\Models\Alumno;
use App\Models\Mensaje;
use App\Models\User;
use App\Notifications\Channels\SmsChannel;
use App\Notifications\CircularRecibida;
use App\Services\Sms\SmsSender;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Pruebas del envío de circulares.
 *
 * Verifican que un mensaje marcado como circular dispare correo y SMS, que el
 * texto institucional sea el acordado y que un mensaje normal no notifique.
 * Ruta: POST /mensajes (mensajes.store).
 */
class CircularNotificacionTest extends TestCase
{
    use RefreshDatabase;

    public function test_circular_envia_correo_y_sms_al_contacto_registrado(): void
    {
        Notification::fake();

        $docente = User::factory()->docente()->create();
        $estudiante = User::factory()->estudiante()->create(['telefono' => '0999999999']);

        $this->actingAs($docente)
            ->post(route('mensajes.store'), [
                'asunto' => 'Circular de matrícula',
                'cuerpo_contenido' => 'Estimadas familias, revise el proceso.',
                'destinatario_id' => $estudiante->id,
                'es_circular' => 1,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('mensajes', [
            'destinatario_id' => $estudiante->id,
            'es_circular' => true,
        ]);

        Notification::assertSentTo(
            $estudiante,
            CircularRecibida::class,
            function (CircularRecibida $notificacion, array $canales) use ($estudiante) {
                return in_array('mail', $canales, true)
                    && in_array(SmsChannel::class, $canales, true)
                    && str_contains($notificacion->toSms($estudiante), 'notificación por parte de la institución');
            }
        );
    }

    public function test_mensaje_normal_no_envia_notificacion(): void
    {
        Notification::fake();

        $docente = User::factory()->docente()->create();
        $estudiante = User::factory()->estudiante()->create(['telefono' => '0999999999']);

        $this->actingAs($docente)
            ->post(route('mensajes.store'), [
                'asunto' => 'Consulta interna',
                'cuerpo_contenido' => 'Mensaje sin aviso externo.',
                'destinatario_id' => $estudiante->id,
            ])
            ->assertRedirect();

        Notification::assertNothingSent();
    }

    public function test_difusion_como_circular_avisa_a_cada_destinatario(): void
    {
        Notification::fake();

        $inspector = User::factory()->create(['rol' => 'INSPECCION']);
        $estudiante = User::factory()->estudiante()->create(['telefono' => '0988888888']);
        $representante = User::factory()->representante()->create(['telefono' => '0977777777']);
        $alumno = Alumno::factory()->create([
            'user_id' => $estudiante->id,
            'representante_user_id' => $representante->id,
        ]);

        $this->actingAs($inspector)
            ->post(route('mensajes.store'), [
                'asunto' => 'Comunicado general',
                'cuerpo_contenido' => 'Información para la familia.',
                'alcance' => 'estudiante',
                'alumno_id' => $alumno->id,
                'es_circular' => 1,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('mensajes', ['destinatario_id' => $estudiante->id, 'es_circular' => true]);
        $this->assertDatabaseHas('mensajes', ['destinatario_id' => $representante->id, 'es_circular' => true]);

        Notification::assertSentTo($estudiante, CircularRecibida::class);
        Notification::assertSentTo($representante, CircularRecibida::class);
    }

    public function test_canal_sms_usa_el_telefono_del_usuario(): void
    {
        $docente = User::factory()->docente()->create();
        $estudiante = User::factory()->estudiante()->create(['telefono' => '0966666666']);
        $mensaje = Mensaje::create([
            'asunto' => 'Circular',
            'cuerpo_contenido' => 'Contenido',
            'carpeta' => 'enviados',
            'emisor_id' => $docente->id,
            'emisor_type' => User::class,
            'destinatario_id' => $estudiante->id,
            'leido' => false,
            'es_circular' => true,
            'fecha_envio' => now(),
        ]);

        $fake = new class implements SmsSender
        {
            public array $enviados = [];

            public function enviar(string $telefono, string $mensaje): void
            {
                $this->enviados[] = compact('telefono', 'mensaje');
            }
        };

        (new SmsChannel($fake))->send($estudiante, new CircularRecibida($mensaje));

        $this->assertCount(1, $fake->enviados);
        $this->assertSame('0966666666', $fake->enviados[0]['telefono']);
        $this->assertSame(CircularRecibida::TEXTO, $fake->enviados[0]['mensaje']);
    }
}
