<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Alumno;
use App\Models\Mensaje;
use App\Models\Observacion;
use App\Models\TipoObservacion;
use App\Models\User;

/**
 * El aviso al representante vive aqui para que la pantalla y la API escriban
 * exactamente el mismo mensaje. Si se duplica en cada controlador, uno de los
 * dos termina avisando distinto al otro.
 */
trait NotificaObservacionAlRepresentante
{
    /**
     * El representante recibe la observacion en su bandeja de la portal, como un
     * mensaje nuevo con el alumno como contexto. Devuelve false si el alumno no
     * tiene representante, para que quienllama lo diga con todas sus letras.
     */
    protected function avisarAlRepresentante(User $emisor, Alumno $alumno, TipoObservacion $tipo, Observacion $observacion): bool
    {
        if (!$alumno->representante) {
            return false;
        }

        Mensaje::create([
            'asunto' => 'Observación registrada: ' . trim("{$alumno->nombres} {$alumno->apellidos}"),
            'cuerpo_contenido' => $tipo->nombre . "\n\n" . $observacion->descripcion_texto
                . "\n\nRegistrado por: " . trim("{$emisor->name} {$emisor->apellidos}") . ' (' . $emisor->rol . ')',
            'carpeta' => 'enviados',
            'emisor_id' => $emisor->id,
            'emisor_type' => User::class,
            'destinatario_id' => $alumno->representante->id,
            'contexto_estudiante_id' => $alumno->id,
            'leido' => false,
            'fecha_envio' => now(),
        ]);

        return true;
    }
}
