<?php

namespace App\Http\Controllers\Dece;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Dece\Concerns\AutorizaDece;
use App\Http\Controllers\Dece\Concerns\CargaAlumnoSeleccionado;
use App\Http\Controllers\Dece\Concerns\FiltraPorAlumno;
use App\Models\DeceAuditoria;
use App\Models\DeceDiagnostico;
use App\Models\DeceNee;
use App\Services\Dece\GestorArchivos;
use App\Services\Dece\RegistrarAuditoria;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Necesidades educativas especificas. Un registro interno exige estudiante del
 * padron; uno externo exige nombre y curso de referencia. El diagnostico sale
 * del catalogo oficial y puede llevar el informe psicopedagogico adjunto.
 */
class NeeController extends Controller
{
    use AutorizaDece;
    use CargaAlumnoSeleccionado;
    use FiltraPorAlumno;

    public function __construct(
        private readonly RegistrarAuditoria $auditoria,
        private readonly GestorArchivos $archivos
    ) {}

    public function index(Request $request)
    {
        $filtros = $request->validate([
            'buscar' => ['nullable', 'string', 'max:100'],
            'alumno_id' => ['nullable', 'integer', 'exists:alumnos,id'],
            'procedencia' => ['nullable', 'string', Rule::in(array_keys(DeceNee::PROCEDENCIAS))],
            'diagnostico_id' => ['nullable', 'integer', 'exists:dece_diagnosticos,id'],
            'activo' => ['nullable', 'boolean'],
        ]);

        $registros = DeceNee::with(['alumno', 'diagnostico'])
            ->buscar($this->textoBusqueda($filtros))
            ->when($filtros['alumno_id'] ?? null, fn ($query, int $id) => $query->where('alumno_id', $id))
            ->when($filtros['procedencia'] ?? null, fn ($query, string $procedencia) => $query->where('procedencia', $procedencia))
            ->when($filtros['diagnostico_id'] ?? null, fn ($query, int $id) => $query->where('diagnostico_id', $id))
            ->when(array_key_exists('activo', $filtros) && $filtros['activo'] !== null, fn ($query) => $query->where('activo', (bool) $filtros['activo']))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $procedencias = DeceNee::PROCEDENCIAS;
        $diagnosticos = DeceDiagnostico::activos()->orderBy('nombre')->get();

        return view('dece.nee.index', compact('registros', 'procedencias', 'diagnosticos', 'filtros'));
    }

    public function create(Request $request)
    {
        $alumnoSeleccionado = $this->alumnoSeleccionado($request);

        $diagnosticos = DeceDiagnostico::activos()->orderBy('categoria')->orderBy('nombre')->get();
        $procedencias = DeceNee::PROCEDENCIAS;
        $grados = DeceNee::GRADOS_ADAPTACION;

        return view('dece.nee.create', compact('alumnoSeleccionado', 'diagnosticos', 'procedencias', 'grados'));
    }

    public function store(Request $request)
    {
        $this->soloGestion($request);

        $validated = $this->validar($request);

        $registro = DB::transaction(function () use ($request, $validated) {
            $registro = DeceNee::create($this->datos($validated) + [
                'created_by' => $request->user()->id,
                'activo' => true,
            ]);

            $this->auditoria->registrar(
                $request,
                $registro,
                DeceAuditoria::ACCION_CREACION,
                'Registro NEE creado ('.$registro->procedenciaLabel().').',
                null,
                $registro->only(['procedencia', 'diagnostico_id', 'grado_adaptacion', 'activo']),
                $registro->alumno_id
            );

            $guardados = [];

            try {
                $guardados = $this->archivos->guardar(
                    $request,
                    $registro,
                    $request->file('evidencias', []),
                    'nee'
                );
            } catch (Throwable $e) {
                foreach ($guardados as $archivo) {
                    $this->archivos->eliminar($archivo);
                }

                throw $e;
            }

            foreach ($guardados as $archivo) {
                $this->auditoria->registrar(
                    $request,
                    $registro,
                    DeceAuditoria::ACCION_ARCHIVO,
                    'Informe adjunto: '.$archivo->nombre_original.'.',
                    null,
                    ['archivo' => $archivo->nombre_original],
                    $registro->alumno_id
                );
            }

            return $registro;
        });

        return redirect()
            ->route('dece.nee.show', $registro)
            ->with('success', 'Registro NEE guardado con su respaldo documental.');
    }

    public function show(Request $request, DeceNee $nee)
    {
        $nee->load(['alumno.representante', 'diagnostico', 'creador', 'archivos.subidor']);

        $this->auditoria->registrar(
            $request,
            $nee,
            DeceAuditoria::ACCION_CONSULTA,
            'Consulta del registro NEE.',
            null,
            null,
            $nee->alumno_id
        );

        return view('dece.nee.show', compact('nee'));
    }

    public function edit(Request $request, DeceNee $nee)
    {
        $alumnoSeleccionado = $this->alumnoSeleccionado($request) ?? $this->cargarAlumno($nee->alumno_id);

        $diagnosticos = DeceDiagnostico::activos()->orderBy('categoria')->orderBy('nombre')->get();
        $procedencias = DeceNee::PROCEDENCIAS;
        $grados = DeceNee::GRADOS_ADAPTACION;

        return view('dece.nee.edit', compact('nee', 'alumnoSeleccionado', 'diagnosticos', 'procedencias', 'grados'));
    }

    public function update(Request $request, DeceNee $nee)
    {
        $this->soloGestion($request);

        $validated = $this->validar($request);
        $anteriores = $nee->only(['procedencia', 'diagnostico_id', 'grado_adaptacion', 'requiere_informe', 'activo']);

        $nee->update($this->datos($validated));

        $this->auditoria->registrar(
            $request,
            $nee,
            DeceAuditoria::ACCION_ACTUALIZACION,
            'Registro NEE actualizado.',
            $anteriores,
            $nee->only(['procedencia', 'diagnostico_id', 'grado_adaptacion', 'requiere_informe', 'activo']),
            $nee->alumno_id
        );

        return redirect()
            ->route('dece.nee.show', $nee)
            ->with('success', 'Registro NEE actualizado.');
    }

    public function toggle(Request $request, DeceNee $nee)
    {
        $this->soloGestion($request);

        $anterior = $nee->activo;
        $nee->update(['activo' => ! $anterior]);

        $this->auditoria->registrar(
            $request,
            $nee,
            DeceAuditoria::ACCION_ACTUALIZACION,
            $nee->activo ? 'Registro NEE reactivado.' : 'Registro NEE desactivado.',
            ['activo' => $anterior],
            ['activo' => $nee->activo],
            $nee->alumno_id
        );

        return back()->with('success', $nee->activo ? 'Registro reactivado.' : 'Registro desactivado.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validar(Request $request): array
    {
        $validated = $request->validate([
            'procedencia' => ['required', 'string', Rule::in(array_keys(DeceNee::PROCEDENCIAS))],
            'alumno_id' => ['nullable', 'integer', 'exists:alumnos,id'],
            'nombre_externo' => ['nullable', 'string', 'max:255'],
            'curso_externo' => ['nullable', 'string', 'max:120'],
            'diagnostico_id' => ['nullable', 'integer', 'exists:dece_diagnosticos,id'],
            'diagnostico_detalle' => ['nullable', 'string', 'max:255'],
            'profesional_evaluador' => ['nullable', 'string', 'max:255'],
            'fecha_evaluacion' => ['nullable', 'date'],
            'grado_adaptacion' => ['nullable', 'integer', Rule::in(array_keys(DeceNee::GRADOS_ADAPTACION))],
            'requiere_informe' => ['nullable', 'boolean'],
            'observaciones' => ['nullable', 'string', 'max:5000'],
            'evidencias' => ['nullable', 'array', 'max:5'],
            'evidencias.*' => ['file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ]);

        if ($validated['procedencia'] === DeceNee::PROCEDENCIA_INTERNO && empty($validated['alumno_id'])) {
            throw ValidationException::withMessages([
                'alumno_id' => 'Debe seleccionar al estudiante del plantel.',
            ]);
        }

        if ($validated['procedencia'] === DeceNee::PROCEDENCIA_EXTERNO) {
            $errores = [];

            if (empty($validated['nombre_externo'])) {
                $errores['nombre_externo'] = 'Para un registro externo indique el nombre del estudiante.';
            }

            if (empty($validated['curso_externo'])) {
                $errores['curso_externo'] = 'Para un registro externo indique el curso de referencia.';
            }

            if ($errores) {
                throw ValidationException::withMessages($errores);
            }
        }

        return $validated;
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function datos(array $validated): array
    {
        $externo = $validated['procedencia'] === DeceNee::PROCEDENCIA_EXTERNO;

        return [
            'procedencia' => $validated['procedencia'],
            'alumno_id' => $externo ? null : $validated['alumno_id'],
            'nombre_externo' => $externo ? trim((string) $validated['nombre_externo']) : null,
            'curso_externo' => $externo ? trim((string) $validated['curso_externo']) : null,
            'diagnostico_id' => $validated['diagnostico_id'] ?? null,
            'diagnostico_detalle' => isset($validated['diagnostico_detalle']) ? trim($validated['diagnostico_detalle']) : null,
            'profesional_evaluador' => isset($validated['profesional_evaluador']) ? trim($validated['profesional_evaluador']) : null,
            'fecha_evaluacion' => $validated['fecha_evaluacion'] ?? null,
            'grado_adaptacion' => $validated['grado_adaptacion'] ?? null,
            'requiere_informe' => (bool) ($validated['requiere_informe'] ?? false),
            'observaciones' => isset($validated['observaciones']) ? trim($validated['observaciones']) : null,
        ];
    }
}
