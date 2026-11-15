<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ComunidadMiembro;
use App\Models\HistorialCambio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Administración de la Comunidad Educativa.
 *
 * Qué hace: permite crear, editar y eliminar integrantes con su cargo, foto,
 * nombres, responsabilidad y área. El portal agrupa las tarjetas por área
 * (Rectorado, DECE, Matemáticas, Inglés, etc.) y admite filas ilimitadas.
 * Conecta con: App\Models\ComunidadMiembro, HistorialCambio y welcome.blade.php.
 *
 * Rutas:
 * - GET    /admin/comunidad                (admin.comunidad.index)
 * - POST   /admin/comunidad                (admin.comunidad.store)
 * - PUT    /admin/comunidad/{miembro}      (admin.comunidad.update)
 * - DELETE /admin/comunidad/{miembro}      (admin.comunidad.destroy)
 * - POST   /admin/comunidad/areas/reordenar
 *                                          (admin.comunidad.areas.reordenar)
 */
class ComunidadController extends Controller
{
    public function index()
    {
        $miembros = ComunidadMiembro::orderBy('area_orden')
            ->orderBy('area')
            ->orderBy('orden')
            ->orderBy('id')
            ->get();

        return view('admin.comunidad.index', [
            'miembros' => $miembros,
            'areas' => $miembros->pluck('area')->unique()->values(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validar($request);
        $validated['area_orden'] = $this->ordenDeArea($validated['area']);

        if ($request->hasFile('foto')) {
            $validated['foto_path'] = $request->file('foto')->store('portal/comunidad', 'public');
        }

        $miembro = ComunidadMiembro::create($validated);

        HistorialCambio::registrar(
            'Comunidad educativa',
            'crear',
            "Integrante agregado: {$miembro->nombre} ({$miembro->cargo})",
            ['id' => $miembro->id, 'area' => $miembro->area]
        );

        return redirect()->route('admin.comunidad.index')
            ->with('success', 'Integrante agregado a la comunidad educativa.');
    }

    public function update(Request $request, ComunidadMiembro $miembro)
    {
        $validated = $this->validar($request);
        $validated['area_orden'] = $this->ordenDeArea($validated['area'], $miembro);

        if ($request->hasFile('foto')) {
            $this->borrarFoto($miembro->foto_path);
            $validated['foto_path'] = $request->file('foto')->store('portal/comunidad', 'public');
        } elseif ($request->boolean('quitar_foto')) {
            $this->borrarFoto($miembro->foto_path);
            $validated['foto_path'] = null;
        }

        $miembro->update($validated);

        HistorialCambio::registrar(
            'Comunidad educativa',
            'actualizar',
            "Integrante actualizado: {$miembro->nombre} ({$miembro->cargo})",
            ['id' => $miembro->id, 'area' => $miembro->area]
        );

        return redirect()->route('admin.comunidad.index')
            ->with('success', 'Integrante actualizado correctamente.');
    }

    public function destroy(ComunidadMiembro $miembro)
    {
        $this->borrarFoto($miembro->foto_path);

        HistorialCambio::registrar(
            'Comunidad educativa',
            'eliminar',
            "Integrante eliminado: {$miembro->nombre} ({$miembro->cargo})",
            ['id' => $miembro->id]
        );

        $miembro->delete();

        return redirect()->route('admin.comunidad.index')
            ->with('success', 'Integrante eliminado de la comunidad educativa.');
    }

    /**
     * Guarda el orden de los bloques de área que el administrador arrastra.
     *
     * Qué hace: recibe las áreas en el orden visual y actualiza `area_orden` en
     * todos sus integrantes. Las áreas que no lleguen en la lista se conservan
     * al final para no perder ningún grupo.
     * Conecta con: ComunidadMiembro y HistorialCambio.
     * Ruta: POST /admin/comunidad/areas/reordenar.
     */
    public function reordenarAreas(Request $request)
    {
        $validated = $request->validate([
            'areas' => 'required|array|min:1',
            'areas.*' => 'required|string|max:120|distinct',
        ], [
            'areas.required' => 'No se recibió el orden de las áreas.',
        ]);

        $ordenadas = array_values(array_unique($validated['areas']));

        $restantes = ComunidadMiembro::query()
            ->whereNotIn('area', $ordenadas)
            ->distinct()
            ->orderBy('area')
            ->pluck('area')
            ->all();

        $areas = array_merge($ordenadas, $restantes);

        DB::transaction(function () use ($areas) {
            foreach ($areas as $indice => $area) {
                ComunidadMiembro::where('area', $area)->update(['area_orden' => $indice]);
            }
        });

        HistorialCambio::registrar(
            'Comunidad educativa',
            'reordenar',
            'Se actualizó el orden de las áreas: '.implode(', ', $areas),
            ['areas' => $areas]
        );

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'areas' => $areas]);
        }

        return redirect()->route('admin.comunidad.index')
            ->with('success', 'Orden de las áreas actualizado.');
    }

    /**
     * Devuelve el orden que corresponde a un área.
     *
     * Si el área ya existe reutiliza su número; si es nueva la coloca al final.
     * Al editar un integrante sin cambiar de área conserva el orden actual.
     */
    private function ordenDeArea(string $area, ?ComunidadMiembro $actual = null): int
    {
        if ($actual && $actual->area === $area) {
            return $actual->area_orden;
        }

        $existente = ComunidadMiembro::query()
            ->where('area', $area)
            ->when($actual, fn ($consulta) => $consulta->whereKeyNot($actual->getKey()))
            ->value('area_orden');

        if ($existente !== null) {
            return (int) $existente;
        }

        $maximo = ComunidadMiembro::max('area_orden');

        return $maximo === null ? 0 : ((int) $maximo) + 1;
    }

    /** @return array<string, mixed> */
    private function validar(Request $request): array
    {
        $validated = $request->validate([
            'cargo' => 'required|string|max:120',
            'nombre' => 'required|string|max:255',
            'responsabilidad' => 'nullable|string|max:2000',
            'area' => 'required|string|max:120',
            'orden' => 'nullable|integer|min:0|max:999',
            'activo' => 'nullable|boolean',
            'foto' => 'nullable|image|mimes:png,jpg,jpeg,webp|max:4096',
        ], [
            'cargo.required' => 'El cargo es obligatorio.',
            'nombre.required' => 'Los nombres son obligatorios.',
            'area.required' => 'Indique el área o grupo al que pertenece.',
            'foto.image' => 'La fotografía debe ser una imagen PNG, JPG o WEBP.',
            'foto.max' => 'La fotografía puede pesar como máximo 4 MB.',
        ]);

        $validated['orden'] = $validated['orden'] ?? 0;
        $validated['activo'] = $request->boolean('activo', true);

        return $validated;
    }

    /** Borra la fotografía anterior del disco público. */
    private function borrarFoto(?string $ruta): void
    {
        if ($ruta && ! str_starts_with($ruta, 'http://') && ! str_starts_with($ruta, 'https://')) {
            Storage::disk('public')->delete($ruta);
        }
    }
}
