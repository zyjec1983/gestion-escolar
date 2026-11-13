<?php

namespace App\Services\Sms;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Driver SMS por webhook.
 *
 * Qué hace: envía el SMS a la URL configurada en SMS_WEBHOOK_URL con el
 * teléfono y el texto. Sirve para conectar cualquier pasarela externa sin
 * acoplar el sistema a un proveedor específico.
 * Conecta con: config/services.php (services.sms) y SmsChannel.
 * Ruta: POST /mensajes (mensajes.store) cuando el mensaje es circular.
 */
class WebhookSmsSender implements SmsSender
{
    public function enviar(string $telefono, string $mensaje): void
    {
        $url = config('services.sms.webhook_url');

        if (! $url) {
            throw new RuntimeException('Falta configurar SMS_WEBHOOK_URL para el driver webhook.');
        }

        Http::withToken((string) config('services.sms.token'))
            ->timeout(10)
            ->post($url, [
                'to' => $telefono,
                'message' => $mensaje,
            ])
            ->throw();
    }
}
