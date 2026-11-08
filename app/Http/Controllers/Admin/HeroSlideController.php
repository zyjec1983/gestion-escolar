<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HeroSlide;
use App\Models\Institucion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class HeroSlideController extends Controller
{
    public function index()
    {
        // La gestión del carrusel vive ahora dentro de Configuración.
        return redirect()->route('admin.configuracion.index');
    }

    public function create()
    {
        $ultimoOrden = HeroSlide::max('orden') ?? 0;

        return view('admin.hero-slides.form', [
            'slide' => null,
            'ultimoOrden' => $ultimoOrden + 1,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'titulo' => 'nullable|string|max:255',
            'subtitulo' => 'nullable|string|max:255',
            'imagen' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'orden' => 'nullable|integer|min:0',
            'activo' => 'boolean',
            'link' => 'nullable|url|max:255',
        ]);

        $path = $request->file('imagen')->store('hero-slides', 'public');

        HeroSlide::create([
            'titulo' => $validated['titulo'] ?? null,
            'subtitulo' => $validated['subtitulo'] ?? null,
            'imagen_path' => $path,
            'orden' => $validated['orden'] ?? 0,
            'activo' => $request->boolean('activo', true),
            'link' => $validated['link'] ?? null,
        ]);

        return redirect()->to('/admin/configuracion#panel-carrusel')
            ->with('success', 'Slide creado correctamente.');
    }

    public function edit($id)
    {
        $slide = HeroSlide::findOrFail($id);

        return view('admin.hero-slides.form', compact('slide'));
    }

    public function update(Request $request, $id)
    {
        $slide = HeroSlide::findOrFail($id);

        $validated = $request->validate([
            'titulo' => 'nullable|string|max:255',
            'subtitulo' => 'nullable|string|max:255',
            'imagen' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'orden' => 'nullable|integer|min:0',
            'activo' => 'boolean',
            'link' => 'nullable|url|max:255',
        ]);

        $data = [
            'titulo' => $validated['titulo'] ?? $slide->titulo,
            'subtitulo' => $validated['subtitulo'] ?? $slide->subtitulo,
            'orden' => $validated['orden'] ?? $slide->orden,
            'activo' => $request->boolean('activo', $slide->activo),
            'link' => $validated['link'] ?? $slide->link,
        ];

        if ($request->hasFile('imagen')) {
            if ($slide->imagen_path && Storage::disk('public')->exists($slide->imagen_path)) {
                Storage::disk('public')->delete($slide->imagen_path);
            }
            $data['imagen_path'] = $request->file('imagen')->store('hero-slides', 'public');
        }

        $slide->update($data);

        return redirect()->to('/admin/configuracion#panel-carrusel')
            ->with('success', 'Slide actualizado correctamente.');
    }

    public function destroy($id)
    {
        $slide = HeroSlide::findOrFail($id);
        if ($slide->imagen_path && Storage::disk('public')->exists($slide->imagen_path)) {
            Storage::disk('public')->delete($slide->imagen_path);
        }
        $slide->delete();

        return redirect()->to('/admin/configuracion#panel-carrusel')
            ->with('success', 'Slide eliminado correctamente.');
    }

    public function updateVelocidad(Request $request)
    {
        $validated = $request->validate([
            'carousel_interval' => 'required|integer|min:1000|max:30000',
        ]);

        $institucion = Institucion::firstOrFail();
        $institucion->update($validated);

        return back()->with('success', 'Velocidad del carrusel actualizada.');
    }
}
