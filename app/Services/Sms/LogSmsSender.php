<?php

namespace App\Services\Sms;

use Illuminate\Support\Facades\Log;

/**
 * Driver SMS de desarrollo.
 *
 * Qué hace: escribe el SMS en el log en lugar de enviarlo. Permite probar las
 * circulares sin contratar un proveedor ni gastar saldo.
 * Conecta con: SmsChannel y el binding de AppServiceProvider.
 * Ruta: POST /mensajes (mensajes.store) cuando el mensaje es circular.
 */
class LogSmsSender implements SmsSender
{
    public function enviar(string $telefono, string $mensaje): void
    {
        Log::info('SMS simulado (driver log).', [
            'telefono' => $telefono,
            'mensaje' => $mensaje,
        ]);
    }
}
