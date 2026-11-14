<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Circular;
use App\Models\HistorialCambio;
use App\Models\User;
use App\Services\Notificaciones\NotificadorCircular;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

/**
 * Carga y administración de circulares institucionales.
 *
 * Qué hace: publica circulares con número, fecha y archivo PDF. Si se marca
 * la casilla de aviso, envía correo y SMS al contacto registrado de los roles
 * seleccionados. También permite eliminar circulares cargadas por error.
 * Conecta con: App\Models\Circular, NotificadorCircular, CircularPublicada,
 * HistorialCambio y welcome.blade.php (listado de circulares recientes).
 *
 * Rutas:
 * - GET    /admin/circulares             (admin.circulares.index)
 * - POST   /admin/circulares             (admin.circulares.store)
 * - DELETE /admin/circulares/{circular}  (admin.circulares.destroy)
 */
class CircularController extends Controller
{
    public function __construct(private readonly NotificadorCircular $notificador) {}

    public function index()
    {
        return view('admin.circulares.index', [
            'circulares' => Circular::orderByDesc('fecha_publicacion')->orderByDesc('id')->paginate(15),
            'roles' => User::ROLES,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'titulo' => 'required|string|max:255',
            'numero' => 'nullable|string|max:30',
            'fecha_publicacion' => 'required|date',
            'archivo' => 'nullable|file|mimes:pdf|max:10240',
            'notificar' => 'nullable|boolean',
            'roles' => 'nullable|array',
            'roles.*' => [Rule::in(User::ROLES)],
        ], [
            'titulo.required' => 'El título de la circular es obligatorio.',
            'fecha_publicacion.required' => 'Indique la fecha de publicación.',
            'archivo.mimes' => 'La circular debe ser un archivo PDF.',
            'archivo.max' => 'El archivo puede pesar como máximo 10 MB.',
        ]);

        $datos = [
            'titulo' => $validated['titulo'],
            'numero' => $validated['numero'] ?? null,
            'fecha_publicacion' => $validated['fecha_publicacion'],
        ];

        if ($request->hasFile('archivo')) {
            $datos['archivo_url'] = Storage::url(
                $request->file('archivo')->store('circulares', 'public')
            );
        }

        $circular = Circular::create($datos);

        HistorialCambio::registrar(
            'Circulares',
            'crear',
            "Circular publicada: {$circular->titulo}",
            ['id' => $circular->id, 'numero' => $circular->numero]
        );

        $aviso = '';
        if ($request->boolean('notificar')) {
            $roles = $validated['roles'] ?? User::ROLES;

            $destinatarios = User::query()
                ->whereIn('rol', $roles)
                ->where(function ($query) {
                    $query->whereNotNull('email')->where('email', '!=', '')
                        ->orWhere(function ($q) {
                            $q->whereNotNull('telefono')->where('telefono', '!=', '');
                        });
                })
                ->get();

            $enviados = $this->notificador->enviarPublicacion($circular, $destinatarios);
            $aviso = " Se envió el aviso a {$enviados} contacto(s).";
        }

        return redirect()->route('admin.circulares.index')
            ->with('success', 'Circular publicada correctamente.'.$aviso);
    }

    public function destroy(Circular $circular)
    {
        if ($circular->archivo_url) {
            $ruta = str_replace('/storage/', '', $circular->archivo_url);
            Storage::disk('public')->delete($ruta);
        }

        HistorialCambio::registrar(
            'Circulares',
            'eliminar',
            "Circular eliminada: {$circular->titulo}",
            ['id' => $circular->id]
        );

        $circular->delete();

        return redirect()->route('admin.circulares.index')
            ->with('success', 'Circular eliminada correctamente.');
    }
}
