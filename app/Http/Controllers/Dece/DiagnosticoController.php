<?php

namespace App\Http\Controllers\Dece;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Dece\Concerns\AutorizaDece;
use App\Models\DeceAuditoria;
use App\Models\DeceDiagnostico;
use App\Services\Dece\RegistrarAuditoria;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Catalogo oficial de diagnosticos del DECE. Solo los profesionales del
 * departamento y la administracion pueden modificarlo.
 */
class DiagnosticoController extends Controller
{
    use AutorizaDece;

    public function __construct(private readonly RegistrarAuditoria $auditoria) {}

    public function index(Request $request)
    {
        $filtros = $request->validate([
            'buscar' => ['nullable', 'string', 'max:100'],
            'categoria' => ['nullable', 'string', 'max:120'],
            'activo' => ['nullable', 'boolean'],
        ]);

        $diagnosticos = DeceDiagnostico::buscar($filtros['buscar'] ?? null)
            ->when($filtros['categoria'] ?? null, fn ($query, string $categoria) => $query->where('categoria', $categoria))
            ->when(array_key_exists('activo', $filtros) && $filtros['activo'] !== null, fn ($query) => $query->where('activo', (bool) $filtros['activo']))
            ->orderBy('categoria')
            ->orderBy('codigo')
            ->paginate(20)
            ->withQueryString();

        $categorias = DeceDiagnostico::CATEGORIAS;

        return view('dece.diagnosticos.index', compact('diagnosticos', 'categorias', 'filtros'));
    }

    public function store(Request $request)
    {
        $this->soloGestion($request);

        $validated = $request->validate([
            'codigo' => ['required', 'string', 'max:30', 'unique:dece_diagnosticos,codigo'],
            'nombre' => ['required', 'string', 'max:255'],
            'categoria' => ['required', 'string', Rule::in(DeceDiagnostico::CATEGORIAS)],
        ]);

        $diagnostico = DeceDiagnostico::create($validated + ['activo' => true]);

        $this->auditoria->registrar(
            $request,
            $diagnostico,
            DeceAuditoria::ACCION_CREACION,
            'Diagnóstico agregado al catálogo: '.$diagnostico->etiqueta().'.',
            null,
            $diagnostico->only(['codigo', 'nombre', 'categoria', 'activo'])
        );

        return back()->with('success', 'Diagnóstico agregado al catálogo.');
    }

    public function update(Request $request, DeceDiagnostico $diagnostico)
    {
        $this->soloGestion($request);

        $validated = $request->validate([
            'codigo' => ['required', 'string', 'max:30', Rule::unique('dece_diagnosticos', 'codigo')->ignore($diagnostico->id)],
            'nombre' => ['required', 'string', 'max:255'],
            'categoria' => ['required', 'string', Rule::in(DeceDiagnostico::CATEGORIAS)],
        ]);

        $anteriores = $diagnostico->only(['codigo', 'nombre', 'categoria']);
        $diagnostico->update($validated);

        $this->auditoria->registrar(
            $request,
            $diagnostico,
            DeceAuditoria::ACCION_ACTUALIZACION,
            'Diagnóstico actualizado en el catálogo.',
            $anteriores,
            $diagnostico->only(['codigo', 'nombre', 'categoria'])
        );

        return back()->with('success', 'Diagnóstico actualizado.');
    }

    public function toggle(Request $request, DeceDiagnostico $diagnostico)
    {
        $this->soloGestion($request);

        $anterior = $diagnostico->activo;
        $diagnostico->update(['activo' => ! $anterior]);

        $this->auditoria->registrar(
            $request,
            $diagnostico,
            DeceAuditoria::ACCION_ACTUALIZACION,
            $diagnostico->activo ? 'Diagnóstico habilitado.' : 'Diagnóstico deshabilitado.',
            ['activo' => $anterior],
            ['activo' => $diagnostico->activo]
        );

        return back()->with('success', $diagnostico->activo ? 'Diagnóstico habilitado.' : 'Diagnóstico deshabilitado.');
    }
}
