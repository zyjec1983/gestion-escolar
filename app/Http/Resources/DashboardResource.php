<?php

namespace App\Http\Resources;

use App\Models\Circular;
use App\Models\PeriodoLectivo;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DashboardResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $user = $this->resource;
        $periodoActivo = PeriodoLectivo::where('activo', true)->first();

        return [
            'institucion' => [
                'nombre' => $user->institucion?->nombre ?? 'Unidad Educativa',
                'subtitulo' => $user->institucion?->subtitulo ?? 'Institución Educativa',
                'plataforma_proveedor' => $user->institucion?->plataforma_proveedor ?? 'Comunidad',
            ],
            'usuario' => [
                'id' => $user->id,
                'nombre' => strtoupper($user->name),
                'nombre_corto' => $this->formatNombre(strtoupper($user->name)),
                'rol' => $user->perfilActivo(),
                'email' => $user->email,
                'periodo_lectivo' => $periodoActivo?->nombre ?? '2026 - 2027',
                'notificaciones_pendientes' => [
                    'mensajes_nuevos' => $user->mensajesRecibidos()->where('leido', false)->count(),
                    'alertas' => 0,
                ],
            ],
            'tarjetas_resumen' => [
                'mensajes' => [
                    'contador' => $user->mensajesRecibidos()->where('leido', false)->count(),
                    'url_accion' => '/mensajes',
                ],
                'agendas_activas' => [
                    'contador' => null,
                    'url_accion' => '/agendas',
                ],
                'clases' => [
                    'contador' => $user->clasesDocentes()->count(),
                    'url_accion' => '/clases',
                ],
                'observaciones' => [
                    'contador' => null,
                    'url_accion' => '/observaciones',
                ],
                'estudiantes' => [
                    'contador' => null,
                    'url_accion' => '/estudiantes',
                ],
            ],
            'circulares' => CircularResource::collection(
                Circular::latest('fecha_publicacion')->take(10)->get()
            ),
        ];
    }

    private function formatNombre(string $nombre): string
    {
        return nombreCorto($nombre);
    }
}
