<?php

namespace App\Http\Controllers;

use App\Models\Circular;
use App\Models\ComunidadMiembro;
use App\Models\DescargaDocumento;
use App\Models\HeroSlide;
use App\Models\HistoriaHito;
use App\Models\Institucion;
use App\Models\UtilItem;
use App\Support\PortalContenido;

/**
 * Portal institucional público.
 *
 * Qué hace: entrega a cada pestaña del portal su propia vista con los
 * contenidos administrables del sitio. La portada reúne el carrusel, la
 * identidad, los accesos y las circulares recientes; historia, comunidad,
 * útiles, descargas y circulares tienen una página independiente.
 * Conecta con: App\Models\SiteContent (a través de PortalContenido),
 * HeroSlide, HistoriaHito, ComunidadMiembro, UtilItem, DescargaDocumento y
 * Circular; y con las vistas portal/*.
 *
 * Rutas:
 * - GET /             (welcome)           -> index
 * - GET /historia     (portal.historia)   -> historia
 * - GET /comunidad    (portal.comunidad)  -> comunidad
 * - GET /utiles       (portal.utiles)     -> utiles
 * - GET /descargas    (portal.descargas)  -> descargas
 * - GET /circulares   (portal.circulares) -> circulares
 */
class PortalController extends Controller
{
    /** Portada del portal: carrusel, identidad, accesos y circulares. */
    public function index()
    {
        return view('welcome', $this->datosBase() + [
            'slides' => HeroSlide::where('activo', true)
                ->paraUbicacion('inicio')
                ->orderBy('orden')
                ->orderBy('id')
                ->get(),
            'circulares' => Circular::orderByDesc('fecha_publicacion')
                ->orderByDesc('id')
                ->limit(3)
                ->get(),
        ]);
    }

    /** Página "Nuestra historia": relato fundacional e hitos. */
    public function historia()
    {
        return view('portal.historia', $this->datosBase() + [
            'hitos' => HistoriaHito::orderBy('orden')->orderBy('id')->get(),
        ]);
    }

    /** Página "Comunidad educativa": integrantes agrupados por área. */
    public function comunidad()
    {
        return view('portal.comunidad', $this->datosBase() + [
            'miembros' => ComunidadMiembro::where('activo', true)
                ->orderBy('area_orden')
                ->orderBy('area')
                ->orderBy('orden')
                ->orderBy('id')
                ->get()
                ->groupBy('area'),
        ]);
    }

    /** Página "Lista de útiles": materiales agrupados por nivel. */
    public function utiles()
    {
        return view('portal.utiles', $this->datosBase() + [
            'utiles' => UtilItem::orderBy('orden')->orderBy('id')->get()->groupBy('nivel'),
        ]);
    }

    /** Página "Centro de descargas": documentos agrupados por categoría. */
    public function descargas()
    {
        return view('portal.descargas', $this->datosBase() + [
            'descargas' => DescargaDocumento::where('activo', true)
                ->orderBy('orden')
                ->orderBy('id')
                ->get()
                ->groupBy('categoria'),
        ]);
    }

    /** Página "Circulares": listado completo de comunicaciones oficiales. */
    public function circulares()
    {
        return view('portal.circulares', $this->datosBase() + [
            'circulares' => Circular::orderByDesc('fecha_publicacion')
                ->orderByDesc('id')
                ->get(),
        ]);
    }

    /**
     * Datos comunes a todas las páginas del portal.
     *
     * @return array<string, mixed>
     */
    private function datosBase(): array
    {
        $institucion = Institucion::first();

        return [
            'institucion' => $institucion,
            'intervalo' => $institucion?->carousel_interval ?? 4500,
            // Datos y atajos que comparten el layout y todas las pestañas.
            'nombreInstitucion' => $institucion?->nombre
                ?? PortalContenido::valor('institucion.nombre_corto'),
            'eslogan' => PortalContenido::valor('institucion.eslogan'),
            'logoUrl' => ! empty($institucion?->logo_path) ? $institucion->logoUrl() : null,
            'anioActual' => date('Y'),
            'texto' => fn (string $clave): string => PortalContenido::valor($clave),
            'imagen' => fn (string $clave): string => PortalContenido::imagen($clave),
        ];
    }
}
