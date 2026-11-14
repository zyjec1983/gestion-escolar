<?php

namespace App\Http\Controllers;

use App\Models\Circular;
use App\Models\ComunidadMiembro;
use App\Models\DescargaDocumento;
use App\Models\HeroSlide;
use App\Models\HistoriaHito;
use App\Models\Institucion;
use App\Models\UtilItem;

/**
 * Portal institucional público.
 *
 * Qué hace: reúne los contenidos administrables del sitio (textos e imágenes
 * del CMS, carrusel, historia, comunidad, útiles, descargas y circulares) y
 * los entrega a welcome.blade.php.
 * Conecta con: App\Models\SiteContent (a través de PortalContenido),
 * HeroSlide, HistoriaHito, ComunidadMiembro, UtilItem, DescargaDocumento y
 * Circular.
 * Ruta: GET / (welcome).
 */
class PortalController extends Controller
{
    public function index()
    {
        return view('welcome', [
            'institucion' => Institucion::first(),
            'slides' => HeroSlide::where('activo', true)
                ->paraUbicacion('inicio')
                ->orderBy('orden')
                ->orderBy('id')
                ->get(),
            'hitos' => HistoriaHito::orderBy('orden')->orderBy('id')->get(),
            'miembros' => ComunidadMiembro::where('activo', true)
                ->orderBy('area')
                ->orderBy('orden')
                ->orderBy('id')
                ->get()
                ->groupBy('area'),
            'utiles' => UtilItem::orderBy('orden')->orderBy('id')->get()->groupBy('nivel'),
            'descargas' => DescargaDocumento::where('activo', true)
                ->orderBy('orden')
                ->orderBy('id')
                ->get()
                ->groupBy('categoria'),
            'circulares' => Circular::orderByDesc('fecha_publicacion')
                ->orderByDesc('id')
                ->limit(6)
                ->get(),
        ]);
    }
}
