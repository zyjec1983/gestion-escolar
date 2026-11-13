<?php

namespace App\Services\Dece;

use App\Models\DeceCita;
use App\Models\Mensaje;
use App\Models\User;

/**
 * Envia al representante una notificacion interna cuando el DECE programa una
 * cita. Reutiliza la bandeja de mensajes existente para no duplicar canales.
 */
class NotificarRepresentante
{
    public function notificarCita(DeceCita $cita, User $emisor): ?Mensaje
    {
        $representante = $cita->representante;

        if (! $representante) {
            return null;
        }

        $alumno = $cita->alumno?->nombreCompleto() ?? 'su representado';
        $fecha = $cita->fecha_hora->format('d/m/Y H:i');

        return Mensaje::create([
            'asunto' => 'Citación del DECE — '.$alumno,
            'cuerpo_contenido' => "Estimado(a) {$representante->nombreCompleto()}:\n\n"
                ."El Departamento de Consejería Estudiantil le convoca a una entrevista para tratar el siguiente motivo: {$cita->motivo}.\n\n"
                ."Fecha y hora: {$fecha}.\n"
                ."Duración estimada: {$cita->duracion_minutos} minutos.\n\n"
                ."Su asistencia es importante para acompañar el proceso de {$alumno}.",
            'carpeta' => 'no_leidos',
            'emisor_id' => $emisor->id,
            'emisor_type' => User::class,
            'destinatario_id' => $representante->id,
            'contexto_estudiante_id' => $cita->alumno_id,
            'leido' => false,
            'fecha_envio' => now(),
            'borrado_emisor' => false,
            'borrado_destinatario' => false,
        ]);
    }
}
