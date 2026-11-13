<?php

namespace App\Services\Sms;

/**
 * Contrato del canal SMS.
 *
 * Qué hace: desacopla el envío de SMS del proveedor. El sistema usa `log`
 * mientras no haya una pasarela contratada y `webhook` para conectar cualquier
 * API externa (Twilio, Vonage, etc.) mediante una URL.
 * Conecta con: App\Notifications\Channels\SmsChannel y config/services.php.
 * Ruta: POST /mensajes (mensajes.store) cuando el mensaje es circular.
 */
interface SmsSender
{
    /** Envía un texto al teléfono indicado. */
    public function enviar(string $telefono, string $mensaje): void;
}
