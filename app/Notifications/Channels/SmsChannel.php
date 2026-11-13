<?php

namespace App\Notifications\Channels;

use App\Services\Sms\SmsSender;
use Illuminate\Notifications\Notification;

/**
 * Canal de notificación SMS.
 *
 * Qué hace: toma el teléfono registrado del usuario y envía el texto que la
 * notificación define en `toSms()`. Si no hay teléfono, no hace nada.
 * Conecta con: App\Services\Sms\SmsSender, App\Models\User::routeNotificationForSms
 * y App\Notifications\CircularRecibida.
 * Ruta: POST /mensajes (mensajes.store) cuando el mensaje es circular.
 */
class SmsChannel
{
    public function __construct(private readonly SmsSender $sms) {}

    public function send(object $notifiable, Notification $notification): void
    {
        $telefono = $notifiable->routeNotificationFor('sms', $notification);

        if (! $telefono || ! method_exists($notification, 'toSms')) {
            return;
        }

        $this->sms->enviar((string) $telefono, (string) $notification->toSms($notifiable));
    }
}
