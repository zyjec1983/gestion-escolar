<?php

namespace App\Notifications;

use App\Models\Mensaje;
use App\Notifications\Channels\SmsChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Aviso de circular recibida.
 *
 * Qué hace: envía un correo y un SMS al contacto registrado cuando un mensaje
 * se marca como circular. El texto es institucional y no incluye datos
 * sensibles: solo invita a leer la notificación en la plataforma.
 * Conecta con: App\Services\Notificaciones\NotificadorCircular,
 * SmsChannel, App\Models\User (email y teléfono) y MensajeController.
 * Ruta: POST /mensajes (mensajes.store).
 */
class CircularRecibida extends Notification
{
    use Queueable;

    /** Texto único para correo y SMS, tal como lo definió la institución. */
    public const TEXTO = 'Saludos, Por esta vía le informamos que usted ha recibido una notificación por parte de la institución. Le invitamos a leerla y si tiene alguna duda, gustosos le ayudaremos a aclararla. Saludos cordiales';

    public function __construct(public Mensaje $mensaje) {}

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

    /** Correo institucional simple con el asunto de la circular. */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Notificación institucional: '.$this->mensaje->asunto)
            ->greeting('Saludos,')
            ->line('Por esta vía le informamos que usted ha recibido una notificación por parte de la institución.')
            ->line('Le invitamos a leerla y si tiene alguna duda, gustosos le ayudaremos a aclararla.')
            ->salutation('Saludos cordiales');
    }

    /** Texto que consume el canal SMS. */
    public function toSms(object $notifiable): string
    {
        return self::TEXTO;
    }
}
