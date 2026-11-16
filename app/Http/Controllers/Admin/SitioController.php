<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DescargaDocumento;
use App\Models\HistoriaHito;
use App\Models\HistorialCambio;
use App\Models\ListaUtil;
use App\Models\SiteContent;
use App\Models\UtilItem;
use App\Support\PortalContenido;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Administración del sitio web institucional.
 *
 * Qué hace:
 * - Edita todos los textos e imágenes del portal (identidad, historia,
 *   comunidad, útiles, descargas, admisiones y cabecera del pre-registro).
 * - Administra la línea de tiempo, la lista de útiles y los documentos.
 *
 * Conecta con: App\Support\PortalContenido, App\Models\SiteContent,
 * HistoriaHito, ListaUtil, UtilItem, DescargaDocumento y welcome.blade.php.
 *
 * Rutas:
 * - GET  /admin/sitio                         (admin.sitio.index)
 * - POST /admin/sitio/contenido/{grupo}       (admin.sitio.contenido)
 * - CRUD /admin/sitio/historia                (admin.sitio.historia.*)
 * - CRUD /admin/sitio/utiles/listas           (admin.sitio.utiles.listas.*)
 * - CRUD /admin/sitio/descargas               (admin.sitio.descargas.*)
 */
class SitioController extends Controller
{
    public function index()
    {
        // Enlaces antiguos con ?tab= se redirigen a su vista independiente.
        $destinos = [
            'preregistro' => 'admin.sitio.preregistro',
            'historia' => 'admin.sitio.historia',
            'utiles' => 'admin.sitio.utiles',
            'descargas' => 'admin.sitio.descargas',
        ];

        $destino = $destinos[request('tab')] ?? null;

        if ($destino !== null) {
            $parametros = request('lista') ? ['lista' => request('lista')] : [];

            return redirect()->route($destino, $parametros);
        }

        return view('admin.sitio.index', $this->datosSitio());
    }

    /**
     * Cabecera del formulario de pre-registro.
     *
     * Ruta: GET /admin/sitio/preregistro (admin.sitio.preregistro).
     */
    public function preregistro()
    {
        return view('admin.sitio.preregistro', $this->datosSitio());
    }

    /**
     * Hitos de la historia institucional.
     *
     * Ruta: GET /admin/sitio/historia (admin.sitio.historia).
     */
    public function historia()
    {
        return view('admin.sitio.historia', $this->datosSitio());
    }

    /**
     * Listas de útiles por nivel o curso.
     *
     * Ruta: GET /admin/sitio/utiles (admin.sitio.utiles).
     */
    public function utiles()
    {
        return view('admin.sitio.utiles', $this->datosSitio());
    }

    /**
     * Centro de descargas del portal.
     *
     * Ruta: GET /admin/sitio/descargas (admin.sitio.descargas).
     */
    public function descargas()
    {
        return view('admin.sitio.descargas', $this->datosSitio());
    }

    /**
     * Datos compartidos por las vistas del sitio web.
     *
     * @return array<string, mixed>
     */
    private function datosSitio(): array
    {
        $listas = ListaUtil::with('items')
            ->orderBy('orden')
            ->orderBy('id')
            ->get();

        $listaSeleccionada = null;
        $idLista = (int) request('lista');

        if ($idLista > 0) {
            $listaSeleccionada = $listas->firstWhere('id', $idLista);
        }

        return [
            'grupos' => PortalContenido::GRUPOS,
            'campos' => PortalContenido::campos(),
            'hitos' => HistoriaHito::orderBy('orden')->orderBy('id')->get(),
            'listas' => $listas,
            'listaSeleccionada' => $listaSeleccionada,
            'descargas' => DescargaDocumento::orderBy('categoria')->orderBy('orden')->orderBy('id')->get()->groupBy('categoria'),
            'categorias' => DescargaDocumento::CATEGORIAS,
        ];
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
            ->route($grupo === 'preregistro' ? 'admin.sitio.preregistro' : 'admin.sitio.index')
            ->with('success', "Se guardaron {$guardados} campos de ".PortalContenido::GRUPOS[$grupo]['titulo'].'.');
    }

    public function storeHito(Request $request)
    {
        $validated = $this->validarHito($request);

        $hito = HistoriaHito::create($validated);
        HistorialCambio::registrar('Sitio web', 'crear', "Hito histórico creado: {$hito->titulo}", ['id' => $hito->id]);

        return redirect()->route('admin.sitio.historia')->with('success', 'Hito histórico agregado.');
    }

    public function updateHito(Request $request, HistoriaHito $hito)
    {
        $hito->update($this->validarHito($request));
        HistorialCambio::registrar('Sitio web', 'actualizar', "Hito histórico actualizado: {$hito->titulo}", ['id' => $hito->id]);

        return redirect()->route('admin.sitio.historia')->with('success', 'Hito histórico actualizado.');
    }

    public function destroyHito(HistoriaHito $hito)
    {
        HistorialCambio::registrar('Sitio web', 'eliminar', "Hito histórico eliminado: {$hito->titulo}", ['id' => $hito->id]);
        $hito->delete();

        return redirect()->route('admin.sitio.historia')->with('success', 'Hito histórico eliminado.');
    }

    /**
     * Crea una lista de útiles para un nivel o curso.
     *
     * Qué hace: recibe el nombre visible (por ejemplo "Octavo" o
     * "1ro Ciencias"), crea la lista vacía y abre el editor tipo Excel para
     * que el administrador agregue las filas.
     * Ruta: POST /admin/sitio/utiles/listas.
     */
    public function storeLista(Request $request)
    {
        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:120', Rule::unique('listas_utiles', 'nombre')],
        ], [
            'nombre.required' => 'Escribe el nivel o curso de la lista.',
            'nombre.unique' => 'Ya existe una lista con ese nivel o curso.',
        ]);

        $orden = ((int) ListaUtil::max('orden')) + 1;

        $lista = ListaUtil::create([
            'nombre' => trim($validated['nombre']),
            'finalizada' => false,
            'orden' => $orden,
        ]);

        HistorialCambio::registrar(
            'Sitio web',
            'crear',
            "Lista de útiles creada: {$lista->nombre}",
            ['id' => $lista->id]
        );

        return redirect()
            ->route('admin.sitio.utiles', ['lista' => $lista->id])
            ->with('success', 'Lista creada. Agrega los artículos y presiona Terminar.');
    }

    /**
     * Guarda las filas de una lista y la marca como terminada.
     *
     * Qué hace: reemplaza los artículos de la lista con las filas recibidas,
     * actualiza el nombre del curso y cambia el estado a finalizada. Las filas
     * sin artículo se descartan y se exige al menos un artículo para terminar.
     * Ruta: PUT /admin/sitio/utiles/listas/{lista}.
     */
    public function updateLista(Request $request, ListaUtil $lista)
    {
        $validated = $request->validate([
            'nombre' => [
                'required', 'string', 'max:120',
                Rule::unique('listas_utiles', 'nombre')->ignore($lista->id),
            ],
            'finalizada' => ['nullable', 'boolean'],
            'items' => ['nullable', 'array'],
            'items.*.cantidad' => ['nullable', 'string', 'max:60'],
            'items.*.articulo' => ['nullable', 'string', 'max:255'],
            'items.*.materia' => ['nullable', 'string', 'max:120'],
            'items.*.especificacion' => ['nullable', 'string', 'max:255'],
        ], [
            'nombre.required' => 'El nivel o curso no puede quedar vacío.',
            'nombre.unique' => 'Ya existe una lista con ese nivel o curso.',
        ]);

        $filas = collect($validated['items'] ?? [])
            ->map(function (array $fila) {
                return [
                    'cantidad' => trim((string) ($fila['cantidad'] ?? '')),
                    'nombre' => trim((string) ($fila['articulo'] ?? '')),
                    'materia' => trim((string) ($fila['materia'] ?? '')),
                    'especificacion' => trim((string) ($fila['especificacion'] ?? '')),
                ];
            })
            ->filter(fn (array $fila) => $fila['nombre'] !== '')
            ->values();

        $finalizada = $request->boolean('finalizada');

        if ($finalizada && $filas->isEmpty()) {
            throw ValidationException::withMessages([
                'items' => 'Agrega al menos un artículo antes de terminar la lista.',
            ]);
        }

        DB::transaction(function () use ($lista, $validated, $filas, $finalizada) {
            $lista->update([
                'nombre' => trim($validated['nombre']),
                'finalizada' => $finalizada,
            ]);

            $lista->items()->delete();

            foreach ($filas as $indice => $fila) {
                $lista->items()->create([
                    'nivel' => 'lista',
                    'nombre' => $fila['nombre'],
                    'especificacion' => $fila['especificacion'] !== '' ? $fila['especificacion'] : null,
                    'cantidad' => $fila['cantidad'] !== '' ? $fila['cantidad'] : null,
                    'materia' => $fila['materia'] !== '' ? $fila['materia'] : null,
                    'area' => $fila['materia'] !== '' ? $fila['materia'] : null,
                    'orden' => $indice + 1,
                ]);
            }
        });

        HistorialCambio::registrar(
            'Sitio web',
            $finalizada ? 'finalizar' : 'actualizar',
            "Lista de útiles actualizada: {$lista->nombre}",
            ['id' => $lista->id, 'articulos' => $filas->count(), 'finalizada' => $finalizada]
        );

        return redirect()
            ->route('admin.sitio.utiles', ['lista' => $lista->id])
            ->with('success', $finalizada
                ? 'Lista terminada. Ya está publicada en el portal.'
                : 'Lista actualizada.');
    }

    /**
     * Habilita nuevamente la edición de una lista terminada.
     *
     * Ruta: POST /admin/sitio/utiles/listas/{lista}/reabrir.
     */
    public function reabrirLista(ListaUtil $lista)
    {
        $lista->update(['finalizada' => false]);

        HistorialCambio::registrar(
            'Sitio web',
            'reabrir',
            "Lista de útiles habilitada para edición: {$lista->nombre}",
            ['id' => $lista->id]
        );

        return redirect()
            ->route('admin.sitio.utiles', ['lista' => $lista->id])
            ->with('success', 'Lista habilitada para edición.');
    }

    /**
     * Elimina una lista completa con todos sus artículos.
     *
     * Ruta: DELETE /admin/sitio/utiles/listas/{lista}.
     */
    public function destroyLista(ListaUtil $lista)
    {
        $nombre = $lista->nombre;

        DB::transaction(function () use ($lista) {
            $lista->items()->delete();
            $lista->delete();
        });

        HistorialCambio::registrar(
            'Sitio web',
            'eliminar',
            "Lista de útiles eliminada: {$nombre}",
            ['id' => $lista->id]
        );

        return redirect()
            ->route('admin.sitio.utiles')
            ->with('success', "Lista de {$nombre} eliminada.");
    }

    public function storeDescarga(Request $request)
    {
        $validated = $this->validarDescarga($request);

        if ($request->hasFile('archivo')) {
            $validated['archivo_path'] = $request->file('archivo')->store('portal/descargas', 'public');
        }

        $documento = DescargaDocumento::create($validated);
        HistorialCambio::registrar('Sitio web', 'crear', "Documento agregado: {$documento->titulo}", ['id' => $documento->id]);

        return redirect()->route('admin.sitio.descargas')->with('success', 'Documento agregado al centro de descargas.');
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

        return redirect()->route('admin.sitio.descargas')->with('success', 'Documento actualizado.');
    }

    public function destroyDescarga(DescargaDocumento $documento)
    {
        if ($documento->archivo_path) {
            Storage::disk('public')->delete($documento->archivo_path);
        }

        HistorialCambio::registrar('Sitio web', 'eliminar', "Documento eliminado: {$documento->titulo}", ['id' => $documento->id]);
        $documento->delete();

        return redirect()->route('admin.sitio.descargas')->with('success', 'Documento eliminado.');
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
