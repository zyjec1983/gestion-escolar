<?php

namespace App\Notifications;

use App\Models\PreRegistro;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SolicitudRechazada extends Notification
{
    use Queueable;

    public function __construct(
        public PreRegistro $preRegistro,
        public string $comentario,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Solicitud de matrícula - Unidad Educativa")
            ->greeting("Estimado/a {$this->preRegistro->rep_nombres},")
            ->line("Su solicitud de matrícula para <strong>{$this->preRegistro->nombre_alumno}</strong> <strong>NO</strong> ha sido aprobada.")
            ->line("**Motivo:**")
            ->line($this->comentario)
            ->line("Puede volver a intentarlo o contactar a la institución para más información.")
            ->action("Realizar una nueva solicitud", url('/pre-registro'))
            ->salutation("Atentamente, Unidad Educativa");
    }
}
