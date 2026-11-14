<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HistorialCambio;
use App\Models\Observacion;
use App\Models\TipoObservacion;
use Illuminate\Http\Request;

/**
 * Depuración de observaciones enviadas por error.
 *
 * Qué hace: muestra el historial de observaciones con filtros por estudiante,
 * tipo y fecha, y permite eliminarlas dejando constancia en el historial de
 * cambios. Solo el perfil de Administración puede acceder.
 * Conecta con: App\Models\Observacion, TipoObservacion, HistorialCambio.
 *
 * Rutas:
 * - GET    /admin/observaciones                (admin.observaciones.index)
 * - DELETE /admin/observaciones/{observacion}  (admin.observaciones.destroy)
 */
class ObservacionAdminController extends Controller
{
    public function index(Request $request)
    {
        $buscar = trim((string) $request->query('buscar'));
        $tipoId = $request->query('tipo_observacion_id');
        $desde = $request->query('desde');
        $hasta = $request->query('hasta');

        $observaciones = Observacion::query()
            ->with(['alumno', 'tipoObservacion', 'profesor'])
            ->when($buscar !== '', function ($query) use ($buscar) {
                $query->where(function ($q) use ($buscar) {
                    $q->whereHas('alumno', function ($sub) use ($buscar) {
                        $sub->where('nombres', 'like', "%{$buscar}%")
                            ->orWhere('apellidos', 'like', "%{$buscar}%")
                            ->orWhere('cedula', 'like', "%{$buscar}%");
                    })->orWhere('descripcion_texto', 'like', "%{$buscar}%");
                });
            })
            ->when($tipoId, fn ($query) => $query->where('tipo_observacion_id', $tipoId))
            ->when($desde, fn ($query) => $query->whereDate('created_at', '>=', $desde))
            ->when($hasta, fn ($query) => $query->whereDate('created_at', '<=', $hasta))
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.observaciones.index', [
            'observaciones' => $observaciones,
            'tipos' => TipoObservacion::orderBy('nombre')->get(),
            'buscar' => $buscar,
            'tipoId' => $tipoId,
            'desde' => $desde,
            'hasta' => $hasta,
        ]);
    }

    /**
     * Elimina una observación enviada por error.
     *
     * Solo se elimina por orden de Rectorado y con el motivo escrito, que
     * queda en el historial de cambios junto con los datos de la observación.
     * Conecta con: Observacion, HistorialCambio.
     * Ruta: DELETE /admin/observaciones/{observacion}.
     */
    public function destroy(Request $request, Observacion $observacion)
    {
        $validated = $request->validate([
            'motivo' => 'required|string|max:500',
        ], [
            'motivo.required' => 'Escriba el motivo y la orden de Rectorado para eliminar la observación.',
        ]);

        $observacion->loadMissing(['alumno', 'tipoObservacion']);

        HistorialCambio::registrar(
            'Observaciones',
            'eliminar',
            'Observación eliminada de '.($observacion->alumno?->nombreCompleto() ?? 'un estudiante')
                .': '.($observacion->tipoObservacion?->nombre ?? 'sin tipo')
                .'. Motivo: '.$validated['motivo'],
            [
                'id' => $observacion->id,
                'alumno_id' => $observacion->alumno_id,
                'descripcion' => $observacion->descripcion_texto,
                'motivo' => $validated['motivo'],
                'por' => $request->user()->id,
            ]
        );

        $observacion->delete();

        return redirect()->route('admin.observaciones.index')
            ->with('success', 'Observación eliminada correctamente. El motivo quedó registrado.');
    }
}
