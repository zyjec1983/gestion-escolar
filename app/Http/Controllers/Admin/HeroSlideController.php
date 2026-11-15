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
        // La gestión del carrusel vive ahora en Configuración del sistema.
        return redirect()->route('admin.configuracion.carrusel');
    }

    public function create()
    {
        $ultimoOrden = HeroSlide::max('orden') ?? 0;

        // Si el administrador pulsa "Nuevo Slide" desde la pestaña del login,
        // el formulario llega con la ubicación preseleccionada.
        $ubicacion = request('ubicacion');
        if (! array_key_exists($ubicacion ?? '', HeroSlide::UBICACIONES)) {
            $ubicacion = 'inicio';
        }

        return view('admin.hero-slides.form', [
            'slide' => null,
            'ultimoOrden' => $ultimoOrden + 1,
            'ubicacion' => $ubicacion,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'titulo' => 'nullable|string|max:255',
            'subtitulo' => 'nullable|string|max:255',
            'etiqueta' => 'nullable|string|max:120',
            'imagen' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'orden' => 'nullable|integer|min:0',
            'activo' => 'boolean',
            'ubicacion' => 'nullable|in:inicio,login,ambos',
            'link' => 'nullable|url|max:255',
        ]);

        $path = $request->file('imagen')->store('hero-slides', 'public');

        HeroSlide::create([
            'titulo' => $validated['titulo'] ?? null,
            'subtitulo' => $validated['subtitulo'] ?? null,
            'etiqueta' => $validated['etiqueta'] ?? null,
            'imagen_path' => $path,
            'orden' => $validated['orden'] ?? 0,
            'activo' => $request->boolean('activo', true),
            'ubicacion' => $validated['ubicacion'] ?? 'inicio',
            'link' => $validated['link'] ?? null,
        ]);

        return redirect()->to($this->urlConfiguracion($validated['ubicacion'] ?? 'inicio'))
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
            'etiqueta' => 'nullable|string|max:120',
            'imagen' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'orden' => 'nullable|integer|min:0',
            'activo' => 'boolean',
            'ubicacion' => 'nullable|in:inicio,login,ambos',
            'link' => 'nullable|url|max:255',
        ]);

        $data = [
            'titulo' => $validated['titulo'] ?? $slide->titulo,
            'subtitulo' => $validated['subtitulo'] ?? $slide->subtitulo,
            'etiqueta' => $validated['etiqueta'] ?? $slide->etiqueta,
            'orden' => $validated['orden'] ?? $slide->orden,
            'activo' => $request->boolean('activo', $slide->activo),
            'ubicacion' => $validated['ubicacion'] ?? $slide->ubicacion,
            'link' => $validated['link'] ?? $slide->link,
        ];

        if ($request->hasFile('imagen')) {
            if ($slide->imagen_path && Storage::disk('public')->exists($slide->imagen_path)) {
                Storage::disk('public')->delete($slide->imagen_path);
            }
            $data['imagen_path'] = $request->file('imagen')->store('hero-slides', 'public');
        }

        $slide->update($data);

        return redirect()->to($this->urlConfiguracion($data['ubicacion']))
            ->with('success', 'Slide actualizado correctamente.');
    }

    public function destroy($id)
    {
        $slide = HeroSlide::findOrFail($id);
        if ($slide->imagen_path && Storage::disk('public')->exists($slide->imagen_path)) {
            Storage::disk('public')->delete($slide->imagen_path);
        }
        $ubicacion = $slide->ubicacion;
        $slide->delete();

        return redirect()->to($this->urlConfiguracion($ubicacion))
            ->with('success', 'Slide eliminado correctamente.');
    }

    /**
     * Devuelve la vista de Configuración que corresponde a una ubicación.
     *
     * Qué hace: los slides de inicio y de login se administran en vistas
     * independientes; tras crear, editar o eliminar volvemos a la correcta.
     * Conecta con: admin.configuracion.carrusel y
     * admin.configuracion.carrusel-login.
     * Rutas: GET /admin/configuracion/carrusel|carrusel-login.
     */
    private function urlConfiguracion(?string $ubicacion): string
    {
        $ruta = $ubicacion === 'login'
            ? 'admin.configuracion.carrusel-login'
            : 'admin.configuracion.carrusel';

        return route($ruta);
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
