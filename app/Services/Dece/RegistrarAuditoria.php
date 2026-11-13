<?php

namespace App\Services\Dece;

use App\Models\DeceAuditoria;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

/**
 * Registra un asiento inmutable en la bitacora del DECE. Centralizar esta
 * responsabilidad evita repetir la captura de IP, user-agent y usuario en
 * cada controlador.
 */
class RegistrarAuditoria
{
    public function registrar(
        Request $request,
        Model $auditable,
        string $accion,
        ?string $detalle = null,
        ?array $anteriores = null,
        ?array $nuevos = null,
        ?int $alumnoId = null
    ): DeceAuditoria {
        $usuario = $request->user();

        return DeceAuditoria::create([
            'auditable_type' => $auditable::class,
            'auditable_id' => $auditable->getKey(),
            'alumno_id' => $alumnoId ?? $auditable->getAttribute('alumno_id'),
            'usuario_id' => $usuario?->id,
            'usuario_nombre' => $usuario?->nombreCompleto() ?? 'Sistema',
            'usuario_rol' => $usuario?->rol ?? 'SISTEMA',
            'accion' => $accion,
            'detalle' => $detalle,
            'datos_anteriores' => $anteriores,
            'datos_nuevos' => $nuevos,
            'ip' => $request->ip(),
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 1000, 'UTF-8'),
        ]);
    }
}
