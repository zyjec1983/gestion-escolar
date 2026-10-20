<?php

namespace App\Http\Controllers\Inspeccion;

use App\Http\Controllers\Controller;
use App\Models\Ajuste;
use App\Models\TipoObservacion;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TipoObservacionController extends Controller
{
    /** Roles con edicion permanente del catalogo. */
    private const AUTORIDADES = ['RECTORA', 'VICERRECTORA', 'DECE'];

    /** Rutas bajo el prefijo inspeccion.observaciones.* */
    private const RUTA_INDEX = 'inspeccion.observaciones.tipos.index';

    public function index()
    {
        $tipos = TipoObservacion::orderBy('clasificacion')->orderBy('nombre')->get();

        return view('inspeccion.observaciones.tipos', [
            'tipos' => $tipos,
            'clasificaciones' => TipoObservacion::CLASIFICACIONES,
            'edicionGlobal' => Ajuste::edicionTiposHabilitada(),
            'puedeEditar' => $this->puedeEditar(request()->user()),
            'esAutoridad' => $this->esAutoridad(request()->user()),
        ]);
    }

    public function store(Request $request)
    {
        $this->autorizarEdicion($request->user());

        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'codigo' => 'nullable|string|max:30',
            'clasificacion' => 'required|in:'.implode(',', array_keys(TipoObservacion::CLASIFICACIONES)),
            'articulo_ley' => 'nullable|string|max:1000',
            'icono' => 'nullable|string|max:60',
        ], [], ['clasificacion' => 'clasificación']);

        $codigo = $this->resolverCodigo($validated['codigo'] ?? null, $validated['nombre']);

        TipoObservacion::create([
            'codigo' => $codigo,
            'nombre' => strtoupper($validated['nombre']),
            'icono' => $validated['icono'] ?? null,
            'clasificacion' => $validated['clasificacion'],
            'articulo_ley' => $validated['articulo_ley'] ?? null,
            'activo' => true,
        ]);

        return redirect()->route(self::RUTA_INDEX)
            ->with('success', 'Tipo de observación creado correctamente.');
    }

    public function update(Request $request, int $id)
    {
        $this->autorizarEdicion($request->user());

        $tipo = TipoObservacion::findOrFail($id);

        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'codigo' => 'nullable|string|max:30',
            'clasificacion' => 'required|in:'.implode(',', array_keys(TipoObservacion::CLASIFICACIONES)),
            'articulo_ley' => 'nullable|string|max:1000',
            'icono' => 'nullable|string|max:60',
        ], [], ['clasificacion' => 'clasificación']);

        $codigo = $this->resolverCodigo($validated['codigo'] ?? null, $validated['nombre'], $tipo->id);

        $tipo->update([
            'codigo' => $codigo,
            'nombre' => strtoupper($validated['nombre']),
            'icono' => $validated['icono'] ?? null,
            'clasificacion' => $validated['clasificacion'],
            'articulo_ley' => $validated['articulo_ley'] ?? null,
        ]);

        return redirect()->route(self::RUTA_INDEX)
            ->with('success', 'Tipo de observación actualizado.');
    }

    /** Habilita o deshabilita un tipo. No se borra nunca para no perder el historial. */
    public function toggleActivo(Request $request, int $id)
    {
        $this->autorizarEdicion($request->user());

        $tipo = TipoObservacion::findOrFail($id);
        $tipo->update(['activo' => ! $tipo->activo]);

        return redirect()->route(self::RUTA_INDEX)->with('success', $tipo->activo
            ? 'Tipo habilitado. Ya aparece en el selector.'
            : 'Tipo deshabilitado. Deja de aparecer en el selector.');
    }

    /** Solo autoridades alternan el modo edicion para inspeccion. */
    public function alternarEdicionGlobal(Request $request)
    {
        if (! $this->esAutoridad($request->user())) {
            abort(403, 'Solo rectorado/vicerrectorado pueden habilitar la edición del catálogo.');
        }

        $habilitado = $request->boolean('valor');
        Ajuste::guardar(Ajuste::TIPOS_OBS_EDICION, $habilitado ? '1' : '0');

        return redirect()->route(self::RUTA_INDEX)->with('success', $habilitado
            ? 'Edición del catálogo habilitada para inspección.'
            : 'Edición del catálogo restringida a rectorado.');
    }

    private function esAutoridad($user): bool
    {
        return $user && in_array($user->rol, self::AUTORIDADES, true);
    }

    private function puedeEditar($user): bool
    {
        if ($this->esAutoridad($user)) {
            return true;
        }

        return $user && $user->rol === 'INSPECCION' && Ajuste::edicionTiposHabilitada();
    }

    private function autorizarEdicion($user): void
    {
        if (! $this->puedeEditar($user)) {
            abort(403, 'La edición del catálogo está deshabilitada por rectorado.');
        }
    }

    /** Genera un codigo unico a partir del nombre cuando no viene o ya existe. */
    private function resolverCodigo(?string $codigo, string $nombre, ?int $excluirId = null): string
    {
        $base = $codigo
            ? Str::upper(Str::slug($codigo, '_'))
            : 'OBS_'.Str::upper(Str::slug($nombre, '_'));
        $base = Str::limit($base, 30, '');

        $candidato = $base;
        $intento = 1;
        while (TipoObservacion::where('codigo', $candidato)
            ->when($excluirId, fn ($q) => $q->where('id', '!=', $excluirId))
            ->exists()) {
            $sufijo = '_'.($intento++);
            $candidato = Str::limit($base, 30 - strlen($sufijo), '').$sufijo;
        }

        return $candidato;
    }
}
