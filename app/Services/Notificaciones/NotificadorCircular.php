<?php

namespace App\Services\Notificaciones;

use App\Models\Mensaje;
use App\Models\User;
use App\Notifications\CircularRecibida;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Dispara los avisos de una circular.
 *
 * Qué hace: envía la notificación por los canales disponibles (correo y SMS)
 * sin interrumpir el envío del mensaje si un proveedor externo falla. Los
 * errores quedan registrados en el log para revisarlos después.
 * Conecta con: MensajeController, CircularRecibida, SmsChannel y User.
 * Ruta: POST /mensajes (mensajes.store).
 */
class NotificadorCircular
{
    public function enviar(?User $destinatario, Mensaje $mensaje): void
    {
        if (! $destinatario) {
            return;
        }

        try {
            $destinatario->notify(new CircularRecibida($mensaje));
        } catch (Throwable $e) {
            Log::error('No se pudo enviar el aviso de la circular.', [
                'mensaje_id' => $mensaje->id,
                'destinatario_id' => $destinatario->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
