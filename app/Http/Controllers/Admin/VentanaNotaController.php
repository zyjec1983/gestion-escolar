<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HistorialCambio;
use App\Models\VentanaNota;
use App\Services\ConfiguracionNotas;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Fechas de subida de notas.
 *
 * Qué hace: habilita o deshabilita la carga de notas por parcial definiendo
 * una fecha de inicio y otra de cierre. Administración y Secretaría pueden
 * crear, editar, activar/desactivar y eliminar ventanas. Si un parcial no
 * tiene ventana configurada se mantiene el comportamiento anterior.
 * Conecta con: App\Models\VentanaNota, ConfiguracionNotas y
 * Docente\NotaController@store.
 *
 * Rutas:
 * - GET    /admin/ventanas-notas                  (admin.ventanas-notas.index)
 * - POST   /admin/ventanas-notas                  (admin.ventanas-notas.store)
 * - PUT    /admin/ventanas-notas/{ventana}        (admin.ventanas-notas.update)
 * - POST   /admin/ventanas-notas/{ventana}/estado (admin.ventanas-notas.estado)
 * - DELETE /admin/ventanas-notas/{ventana}        (admin.ventanas-notas.destroy)
 */
class VentanaNotaController extends Controller
{
    public function __construct(private readonly ConfiguracionNotas $configuracion) {}

    public function index()
    {
        $sistema = $this->configuracion->sistema();

        return view('admin.ventanas-notas.index', [
            'ventanas' => VentanaNota::with('usuario')->orderByDesc('fecha_inicio')->paginate(15),
            'opciones' => $this->configuracion->opciones($sistema),
            'sistema' => $sistema,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validar($request);
        $validated['user_id'] = $request->user()->id;

        $ventana = VentanaNota::create($validated);

        HistorialCambio::registrar(
            'Fechas de notas',
            'crear',
            'Ventana creada para '.$this->etiqueta($ventana->parcial),
            ['id' => $ventana->id, 'activo' => $ventana->activo]
        );

        return redirect()->route('admin.ventanas-notas.index')
            ->with('success', 'Ventana de notas creada correctamente.');
    }

    public function update(Request $request, VentanaNota $ventana)
    {
        $ventana->update($this->validar($request));

        HistorialCambio::registrar(
            'Fechas de notas',
            'actualizar',
            'Ventana actualizada para '.$this->etiqueta($ventana->parcial),
            ['id' => $ventana->id, 'activo' => $ventana->activo]
        );

        return redirect()->route('admin.ventanas-notas.index')
            ->with('success', 'Ventana de notas actualizada.');
    }

    /** Habilita o deshabilita la carga sin borrar las fechas. */
    public function estado(VentanaNota $ventana)
    {
        $ventana->update(['activo' => ! $ventana->activo]);

        HistorialCambio::registrar(
            'Fechas de notas',
            $ventana->activo ? 'habilitar' : 'deshabilitar',
            ($ventana->activo ? 'Habilitada' : 'Deshabilitada').' la carga de '
                .$this->etiqueta($ventana->parcial),
            ['id' => $ventana->id]
        );

        return redirect()->route('admin.ventanas-notas.index')
            ->with('success', $ventana->activo
                ? 'Carga de notas habilitada.'
                : 'Carga de notas deshabilitada.');
    }

    public function destroy(VentanaNota $ventana)
    {
        HistorialCambio::registrar(
            'Fechas de notas',
            'eliminar',
            'Ventana eliminada para '.$this->etiqueta($ventana->parcial),
            ['id' => $ventana->id]
        );

        $ventana->delete();

        return redirect()->route('admin.ventanas-notas.index')
            ->with('success', 'Ventana de notas eliminada.');
    }

    /** @return array<string, mixed> */
    private function validar(Request $request): array
    {
        $sistema = $this->configuracion->sistema();
        $parciales = array_map('strval', $this->configuracion->parcialesValidos($sistema));

        $validated = $request->validate([
            'parcial' => ['required', Rule::in(array_merge(['general'], $parciales))],
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after:fecha_inicio',
            'activo' => 'nullable|boolean',
            'descripcion' => 'nullable|string|max:255',
        ], [
            'parcial.required' => 'Seleccione el parcial al que aplica la ventana.',
            'fecha_inicio.required' => 'Indique la fecha y hora de apertura.',
            'fecha_fin.required' => 'Indique la fecha y hora de cierre.',
            'fecha_fin.after' => 'La fecha de cierre debe ser posterior a la de apertura.',
        ]);

        // "general" se guarda como NULL para que aplique a todos los parciales.
        $validated['parcial'] = $validated['parcial'] === 'general'
            ? null
            : (int) $validated['parcial'];
        $validated['activo'] = $request->boolean('activo', true);

        return $validated;
    }

    /** Etiqueta visible del parcial, incluyendo la ventana general. */
    private function etiqueta(?int $parcial): string
    {
        return $parcial === null
            ? 'todos los parciales'
            : $this->configuracion->etiquetaParcial($parcial);
    }
}
