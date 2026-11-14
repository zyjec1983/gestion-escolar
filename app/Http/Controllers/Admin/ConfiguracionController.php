<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HeroSlide;
use App\Models\Institucion;
use App\Support\Tema;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ConfiguracionController extends Controller
{
    /**
     * Panel de configuración del centro.
     *
     * Qué hace: carga la institución, los desfases horarios, el carrusel y la
     * apariencia global (paletas del aside y tamaño de fuente).
     * Conecta con: layouts/app (aplica el tema) y layouts/principal (aside).
     * Ruta: GET /admin/configuracion (admin.configuracion.index).
     */
    public function index()
    {
        return view('admin.configuracion.index', [
            'institucion' => Institucion::firstOrFail(),
            'desfases' => desfasesHorariosDisponibles(),
            'slides' => HeroSlide::orderBy('orden')->orderBy('id')->get(),
            'tema' => Tema::actual(),
            'paletas' => Tema::paletas(),
            'fuentes' => Tema::fuentes(),
        ]);
    }

    /**
     * Guarda nombre, subtítulo y logo institucional.
     *
     * Qué hace: valida los datos y reemplaza el logo en el disco público.
     * El nombre y el logo alimentan el inicio, el login, el menú lateral y el
     * favicon de las pestañas (Institucion::logoUrl con cache-busting).
     * Conecta con: App\Models\Institucion y AppServiceProvider (vista compartida).
     * Ruta: POST /admin/configuracion/institucion (admin.configuracion.institucion).
     */
    public function updateInstitucion(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'subtitulo' => 'nullable|string|max:255',
            'logo' => 'nullable|image|mimes:png,jpg,jpeg,webp|max:2048',
            'quitar_logo' => 'boolean',
        ], [
            'nombre.required' => 'El nombre de la institución es obligatorio.',
            'logo.image' => 'El logo debe ser una imagen PNG, JPG o WEBP.',
            'logo.max' => 'El logo no puede superar los 2 MB.',
        ]);

        $institucion = Institucion::firstOrFail();

        $data = [
            'nombre' => $validated['nombre'],
            'subtitulo' => $validated['subtitulo'] ?? null,
        ];

        if ($request->hasFile('logo')) {
            if ($institucion->logo_path) {
                Storage::disk('public')->delete($institucion->logo_path);
            }
            $data['logo_path'] = $request->file('logo')->store('institucion', 'public');
        } elseif ($request->boolean('quitar_logo') && $institucion->logo_path) {
            Storage::disk('public')->delete($institucion->logo_path);
            $data['logo_path'] = null;
        }

        $institucion->update($data);

        return back()->with(
            'success',
            'Datos de la institución actualizados. El nombre y el logo se aplican al inicio, al login y a las pestañas.'
        );
    }

    public function updateSistemaEvaluacion(Request $request)
    {
        $validated = $request->validate([
            'sistema_evaluacion' => 'required|in:quimestre,trimestre',
        ]);

        $institucion = Institucion::firstOrFail();
        $institucion->update($validated);

        $label = $validated['sistema_evaluacion'] === 'quimestre' ? 'Quimestre' : 'Trimestre';

        return back()->with('success', "Sistema de evaluación actualizado a {$label}.");
    }

    /**
     * Cambia el desfase horario del centro. Aplica a todas las fechas que
     * registra la aplicación, incluidas las horas de envío de los mensajes.
     */
    public function updateZonaHoraria(Request $request)
    {
        $permitidos = array_map(
            fn (array $d) => valorDesfaseHorario($d['minutos']),
            desfasesHorariosDisponibles()
        );

        $validated = $request->validate([
            'zona_horaria' => ['required', 'string', Rule::in($permitidos)],
        ], [
            'zona_horaria.in' => 'El desfase seleccionado no es válido.',
        ]);

        Institucion::firstOrFail()->update($validated);

        $texto = formatearDesfase(minutosDesfaseZona($validated['zona_horaria']));

        return back()->with('success', "Zona horaria actualizada a {$texto}.");
    }

    /**
     * Guarda la apariencia elegida por el administrador.
     *
     * Qué hace: valida una de las 10 paletas del aside y uno de los tamaños de
     * fuente tipo Word; luego los persiste en `ajustes` para TODOS los roles.
     * Conecta con: App\Support\Tema, App\Models\Ajuste y layouts/app.
     * Ruta: POST /admin/configuracion/apariencia (admin.configuracion.apariencia).
     */
    public function updateApariencia(Request $request)
    {
        $validated = $request->validate([
            'tema' => ['required', 'string', Rule::in(array_keys(Tema::paletas()))],
            'fuente_pt' => ['required', 'string', Rule::in(array_keys(Tema::fuentes()))],
        ], [
            'tema.in' => 'La combinación de colores seleccionada no es válida.',
            'fuente_pt.in' => 'El tamaño de fuente seleccionado no es válido.',
        ]);

        Tema::guardar($validated['tema'], $validated['fuente_pt']);

        $nombre = Tema::paletas()[$validated['tema']]['nombre'];

        return back()->with(
            'success',
            "Apariencia actualizada: {$nombre}, fuente de {$validated['fuente_pt']} pt. Se aplica a todos los roles."
        );
    }
}
