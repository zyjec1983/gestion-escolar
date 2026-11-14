<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ComunidadMiembro;
use App\Models\HistorialCambio;
use Illuminate\Http\Request;
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
 */
class ComunidadController extends Controller
{
    public function index()
    {
        $miembros = ComunidadMiembro::orderBy('area')
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
