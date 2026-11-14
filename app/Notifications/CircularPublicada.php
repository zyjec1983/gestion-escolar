<?php

namespace App\Notifications;

use App\Models\Circular;
use App\Notifications\Channels\SmsChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Aviso de una circular publicada.
 *
 * Qué hace: envía correo y SMS al contacto registrado de cada destinatario
 * cuando el administrador, la rectora o la secretaría cargan una circular y
 * marcan la opción de notificar. No adjunta el archivo: invita a revisar el
 * portal institucional.
 * Conecta con: App\Services\Notificaciones\NotificadorCircular, SmsChannel,
 * App\Models\Circular y Admin\CircularController.
 * Ruta: POST /admin/circulares (admin.circulares.store).
 */
class CircularPublicada extends Notification
{
    use Queueable;

    public const TEXTO = 'Saludos, la institución ha publicado una nueva circular. Ingrese al portal para revisarla. Paz y Bien.';

    public function __construct(public Circular $circular) {}

    /**
     * Canales disponibles según los datos del destinatario.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $canales = [];

        if (! empty($notifiable->email)) {
            $canales[] = 'mail';
        }

        if (! empty($notifiable->telefono)) {
            $canales[] = SmsChannel::class;
        }

        return $canales;
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Nueva circular institucional: '.$this->circular->titulo)
            ->greeting('Saludos,')
            ->line('La institución ha publicado una nueva circular.')
            ->line('**'.$this->circular->titulo.'**')
            ->line('Fecha de publicación: '.$this->circular->fecha_publicacion?->format('d/m/Y'))
            ->action('Revisar el portal', url('/#circulares'))
            ->salutation('Paz y Bien.');
    }

    public function toSms(object $notifiable): string
    {
        return self::TEXTO;
    }
}
