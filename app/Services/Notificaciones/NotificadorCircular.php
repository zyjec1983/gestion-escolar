<?php

namespace App\Services\Notificaciones;

use App\Models\Circular;
use App\Models\User;
use App\Notifications\CircularPublicada;
use App\Notifications\CircularRecibida;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Dispara los avisos de una circular.
 *
 * Qué hace: envía la notificación por los canales disponibles (correo y SMS)
 * sin interrumpir el guardado de la circular si un proveedor externo falla.
 * Los errores quedan registrados en el log para revisarlos después.
 * Conecta con: MensajeController, CircularController, CircularRecibida,
 * CircularPublicada, SmsChannel y User.
 * Rutas: POST /mensajes y POST /admin/circulares.
 */
class NotificadorCircular
{
    public function enviar(?User $destinatario, $mensaje): void
    {
        if (! $destinatario) {
            return;
        }

        try {
            $destinatario->notify(new CircularRecibida($mensaje));
        } catch (Throwable $e) {
            Log::error('No se pudo enviar el aviso de la circular.', [
                'mensaje_id' => $mensaje->id ?? null,
                'destinatario_id' => $destinatario->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Avisa a varios contactos que se publicó una circular.
     *
     * @param  iterable<int, User>  $destinatarios
     */
    public function enviarPublicacion(Circular $circular, iterable $destinatarios): int
    {
        $enviados = 0;

        foreach ($destinatarios as $destinatario) {
            if (empty($destinatario->email) && empty($destinatario->telefono)) {
                continue;
            }

            try {
                $destinatario->notify(new CircularPublicada($circular));
                $enviados++;
            } catch (Throwable $e) {
                Log::error('No se pudo avisar la circular publicada.', [
                    'circular_id' => $circular->id,
                    'destinatario_id' => $destinatario->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $enviados;
    }
}
