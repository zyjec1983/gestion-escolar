<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DescargaDocumento;
use App\Models\HistoriaHito;
use App\Models\HistorialCambio;
use App\Models\SiteContent;
use App\Models\UtilItem;
use App\Support\PortalContenido;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

/**
 * Administración del sitio web institucional.
 *
 * Qué hace:
 * - Edita todos los textos e imágenes del portal (identidad, historia,
 *   comunidad, útiles, descargas y admisiones).
 * - Administra la línea de tiempo, la lista de útiles y los documentos.
 *
 * Conecta con: App\Support\PortalContenido, App\Models\SiteContent,
 * HistoriaHito, UtilItem, DescargaDocumento y welcome.blade.php.
 *
 * Rutas:
 * - GET  /admin/sitio                         (admin.sitio.index)
 * - POST /admin/sitio/contenido/{grupo}       (admin.sitio.contenido)
 * - CRUD /admin/sitio/historia                (admin.sitio.historia.*)
 * - CRUD /admin/sitio/utiles                  (admin.sitio.utiles.*)
 * - CRUD /admin/sitio/descargas               (admin.sitio.descargas.*)
 */
class SitioController extends Controller
{
    public function index()
    {
        return view('admin.sitio.index', [
            'grupos' => PortalContenido::GRUPOS,
            'campos' => PortalContenido::campos(),
            'hitos' => HistoriaHito::orderBy('orden')->orderBy('id')->get(),
            'utiles' => UtilItem::orderBy('nivel')->orderBy('orden')->orderBy('id')->get()->groupBy('nivel'),
            'descargas' => DescargaDocumento::orderBy('categoria')->orderBy('orden')->orderBy('id')->get()->groupBy('categoria'),
            'niveles' => UtilItem::NIVELES,
            'categorias' => DescargaDocumento::CATEGORIAS,
        ]);
    }

    /**
     * Guarda los campos de un grupo del portal.
     *
     * Los textos llegan en `campos[clave]`, los archivos en `imagenes[clave]`
     * y las bajas de imagen en `quitar[clave]`.
     * Ruta: POST /admin/sitio/contenido/{grupo}.
     */
    public function updateContenido(Request $request, string $grupo)
    {
        abort_unless(array_key_exists($grupo, PortalContenido::GRUPOS), 404);

        $request->validate([
            'campos' => 'nullable|array',
            'campos.*' => 'nullable|string|max:10000',
            'imagenes' => 'nullable|array',
            'imagenes.*' => 'nullable|image|mimes:png,jpg,jpeg,webp,svg|max:4096',
            'quitar' => 'nullable|array',
        ], [
            'imagenes.*.image' => 'Cada imagen debe ser un archivo PNG, JPG, WEBP o SVG.',
            'imagenes.*.max' => 'Cada imagen puede pesar como máximo 4 MB.',
        ]);

        $guardados = 0;

        // Los nombres de campo del portal llevan punto (ej. institucion.nombre_corto).
        // Se leen como arreglos para no confundirlos con la notación de puntos
        // que usa Laravel para acceder a datos anidados.
        $campos = (array) $request->input('campos', []);
        $imagenes = $request->file('imagenes', []);
        $quitar = (array) $request->input('quitar', []);

        foreach (PortalContenido::porGrupo($grupo) as $clave => $campo) {
            $meta = [
                'tipo' => $campo['tipo'],
                'grupo' => $grupo,
                'etiqueta' => $campo['etiqueta'],
            ];

            if ($campo['tipo'] === 'imagen') {
                $actual = SiteContent::valor($clave);

                if (isset($imagenes[$clave])) {
                    $this->borrarArchivoAnterior($actual);
                    $ruta = $imagenes[$clave]->store('portal/'.$grupo, 'public');
                    SiteContent::guardar($clave, $ruta, $meta);
                    $guardados++;
                } elseif (! empty($quitar[$clave])) {
                    $this->borrarArchivoAnterior($actual);
                    SiteContent::guardar($clave, null, $meta);
                    $guardados++;
                } elseif (! empty($campos[$clave])) {
                    SiteContent::guardar($clave, $campos[$clave], $meta);
                    $guardados++;
                }

                continue;
            }

            if ($campo['tipo'] === 'booleano') {
                SiteContent::guardar(
                    $clave,
                    filter_var($campos[$clave] ?? false, FILTER_VALIDATE_BOOL),
                    $meta
                );
                $guardados++;

                continue;
            }

            SiteContent::guardar($clave, $campos[$clave] ?? null, $meta);
            $guardados++;
        }

        HistorialCambio::registrar(
            'Sitio web',
            'actualizar',
            'Contenido actualizado: '.PortalContenido::GRUPOS[$grupo]['titulo'],
            ['grupo' => $grupo, 'campos' => $guardados]
        );

        return redirect()
            ->route('admin.sitio.index', ['tab' => 'textos'])
            ->with('success', "Se guardaron {$guardados} campos de ".PortalContenido::GRUPOS[$grupo]['titulo'].'.');
    }

    public function storeHito(Request $request)
    {
        $validated = $this->validarHito($request);

        $hito = HistoriaHito::create($validated);
        HistorialCambio::registrar('Sitio web', 'crear', "Hito histórico creado: {$hito->titulo}", ['id' => $hito->id]);

        return redirect()->route('admin.sitio.index', ['tab' => 'historia'])->with('success', 'Hito histórico agregado.');
    }

    public function updateHito(Request $request, HistoriaHito $hito)
    {
        $hito->update($this->validarHito($request));
        HistorialCambio::registrar('Sitio web', 'actualizar', "Hito histórico actualizado: {$hito->titulo}", ['id' => $hito->id]);

        return redirect()->route('admin.sitio.index', ['tab' => 'historia'])->with('success', 'Hito histórico actualizado.');
    }

    public function destroyHito(HistoriaHito $hito)
    {
        HistorialCambio::registrar('Sitio web', 'eliminar', "Hito histórico eliminado: {$hito->titulo}", ['id' => $hito->id]);
        $hito->delete();

        return redirect()->route('admin.sitio.index', ['tab' => 'historia'])->with('success', 'Hito histórico eliminado.');
    }

    public function storeUtil(Request $request)
    {
        $item = UtilItem::create($this->validarUtil($request));
        HistorialCambio::registrar('Sitio web', 'crear', "Útil agregado: {$item->nombre}", ['id' => $item->id]);

        return redirect()->route('admin.sitio.index', ['tab' => 'utiles'])->with('success', 'Artículo agregado a la lista de útiles.');
    }

    public function updateUtil(Request $request, UtilItem $item)
    {
        $item->update($this->validarUtil($request));
        HistorialCambio::registrar('Sitio web', 'actualizar', "Útil actualizado: {$item->nombre}", ['id' => $item->id]);

        return redirect()->route('admin.sitio.index', ['tab' => 'utiles'])->with('success', 'Artículo actualizado.');
    }

    public function destroyUtil(UtilItem $item)
    {
        HistorialCambio::registrar('Sitio web', 'eliminar', "Útil eliminado: {$item->nombre}", ['id' => $item->id]);
        $item->delete();

        return redirect()->route('admin.sitio.index', ['tab' => 'utiles'])->with('success', 'Artículo eliminado.');
    }

    public function storeDescarga(Request $request)
    {
        $validated = $this->validarDescarga($request);

        if ($request->hasFile('archivo')) {
            $validated['archivo_path'] = $request->file('archivo')->store('portal/descargas', 'public');
        }

        $documento = DescargaDocumento::create($validated);
        HistorialCambio::registrar('Sitio web', 'crear', "Documento agregado: {$documento->titulo}", ['id' => $documento->id]);

        return redirect()->route('admin.sitio.index', ['tab' => 'descargas'])->with('success', 'Documento agregado al centro de descargas.');
    }

    public function updateDescarga(Request $request, DescargaDocumento $documento)
    {
        $validated = $this->validarDescarga($request);

        if ($request->hasFile('archivo')) {
            if ($documento->archivo_path) {
                Storage::disk('public')->delete($documento->archivo_path);
            }
            $validated['archivo_path'] = $request->file('archivo')->store('portal/descargas', 'public');
        }

        $documento->update($validated);
        HistorialCambio::registrar('Sitio web', 'actualizar', "Documento actualizado: {$documento->titulo}", ['id' => $documento->id]);

        return redirect()->route('admin.sitio.index', ['tab' => 'descargas'])->with('success', 'Documento actualizado.');
    }

    public function destroyDescarga(DescargaDocumento $documento)
    {
        if ($documento->archivo_path) {
            Storage::disk('public')->delete($documento->archivo_path);
        }

        HistorialCambio::registrar('Sitio web', 'eliminar', "Documento eliminado: {$documento->titulo}", ['id' => $documento->id]);
        $documento->delete();

        return redirect()->route('admin.sitio.index', ['tab' => 'descargas'])->with('success', 'Documento eliminado.');
    }

    /** @return array<string, mixed> */
    private function validarHito(Request $request): array
    {
        return $request->validate([
            'anio' => 'required|string|max:40',
            'titulo' => 'required|string|max:255',
            'descripcion' => 'nullable|string|max:2000',
            'orden' => 'nullable|integer|min:0|max:999',
        ]);
    }

    /** @return array<string, mixed> */
    private function validarUtil(Request $request): array
    {
        return $request->validate([
            'nivel' => ['required', Rule::in(array_keys(UtilItem::NIVELES))],
            'nombre' => 'required|string|max:255',
            'especificacion' => 'nullable|string|max:255',
            'cantidad' => 'nullable|string|max:60',
            'area' => 'nullable|string|max:80',
            'orden' => 'nullable|integer|min:0|max:999',
        ]);
    }

    /** @return array<string, mixed> */
    private function validarDescarga(Request $request): array
    {
        return $request->validate([
            'categoria' => ['required', Rule::in(array_keys(DescargaDocumento::CATEGORIAS))],
            'titulo' => 'required|string|max:255',
            'descripcion' => 'nullable|string|max:2000',
            'fecha_texto' => 'nullable|string|max:80',
            'peso' => 'nullable|string|max:30',
            'archivo' => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,zip|max:10240',
            'orden' => 'nullable|integer|min:0|max:999',
            'activo' => 'nullable|boolean',
        ], [
            'archivo.mimes' => 'El documento debe ser PDF, Word, Excel, PowerPoint o ZIP.',
            'archivo.max' => 'El documento puede pesar como máximo 10 MB.',
        ]);
    }

    /** Borra un archivo del disco público solo si es una ruta interna. */
    private function borrarArchivoAnterior(?string $valor): void
    {
        if (! $valor || str_starts_with($valor, 'http://') || str_starts_with($valor, 'https://')) {
            return;
        }

        Storage::disk('public')->delete($valor);
    }
}
