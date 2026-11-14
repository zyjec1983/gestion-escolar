<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HistorialCambio;
use Illuminate\Http\Request;

/**
 * Historial de cambios del sistema.
 *
 * Qué hace: muestra quién creó, editó, habilitó o eliminó información en los
 * módulos administrables, con filtros por módulo, acción, usuario y fecha.
 * Conecta con: App\Models\HistorialCambio y todos los controladores del panel.
 * Ruta: GET /admin/historial (admin.historial.index).
 */
class HistorialController extends Controller
{
    public function index(Request $request)
    {
        $buscar = trim((string) $request->query('buscar'));
        $modulo = (string) $request->query('modulo');
        $accion = (string) $request->query('accion');
        $desde = $request->query('desde');
        $hasta = $request->query('hasta');

        $cambios = HistorialCambio::query()
            ->with('usuario')
            ->when($buscar !== '', function ($query) use ($buscar) {
                $query->where(function ($q) use ($buscar) {
                    $q->where('descripcion', 'like', "%{$buscar}%")
                        ->orWhereHas('usuario', function ($sub) use ($buscar) {
                            $sub->where('name', 'like', "%{$buscar}%")
                                ->orWhere('apellidos', 'like', "%{$buscar}%")
                                ->orWhere('email', 'like', "%{$buscar}%");
                        });
                });
            })
            ->when($modulo !== '', fn ($query) => $query->where('modulo', $modulo))
            ->when($accion !== '', fn ($query) => $query->where('accion', $accion))
            ->when($desde, fn ($query) => $query->whereDate('created_at', '>=', $desde))
            ->when($hasta, fn ($query) => $query->whereDate('created_at', '<=', $hasta))
            ->orderByDesc('created_at')
            ->paginate(25)
            ->withQueryString();

        return view('admin.historial.index', [
            'cambios' => $cambios,
            'modulos' => HistorialCambio::query()->distinct()->orderBy('modulo')->pluck('modulo'),
            'acciones' => HistorialCambio::query()->distinct()->orderBy('accion')->pluck('accion'),
            'buscar' => $buscar,
            'modulo' => $modulo,
            'accion' => $accion,
            'desde' => $desde,
            'hasta' => $hasta,
        ]);
    }
}
