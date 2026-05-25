<?php

namespace App\Notifications;

use App\Models\PreRegistro;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SolicitudAprobada extends Notification
{
    use Queueable;

    public function __construct(
        public PreRegistro $preRegistro,
        public string $email,
        public string $password,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $pdfPath = $this->preRegistro->pdf_path
            ? storage_path("app/{$this->preRegistro->pdf_path}")
            : null;

        $mail = (new MailMessage)
            ->subject("Solicitud de matrícula aprobada - Unidad Educativa")
            ->greeting("Estimado/a {$this->preRegistro->rep_nombres},")
            ->line("Su solicitud de matrícula para <strong>{$this->preRegistro->nombre_alumno}</strong> ha sido <strong>APROBADA</strong>.")
            ->line("Sus credenciales de acceso al sistema son:")
            ->line("**Usuario:** {$this->email}")
            ->line("**Contraseña:** {$this->password}")
            ->action("Ingresar al Sistema", url('/login'))
            ->line("Adjunto encontrará su ficha de inscripción digital.")
            ->salutation("Atentamente, Unidad Educativa");

        if ($pdfPath && file_exists($pdfPath)) {
            $mail->attach($pdfPath, [
                'as' => "solicitud_{$this->preRegistro->codigo_solicitud}.pdf",
                'mime' => 'application/pdf',
            ]);
        }

        return $mail;
    }
}
