<?php

namespace App\Http\Controllers\Dece;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Dece\Concerns\AutorizaDece;
use App\Http\Controllers\Dece\Concerns\CargaAlumnoSeleccionado;
use App\Http\Controllers\Dece\Concerns\FiltraPorAlumno;
use App\Models\DeceAuditoria;
use App\Models\DeceDerivacion;
use App\Models\Institucion;
use App\Services\Dece\RegistrarAuditoria;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Derivaciones internas (docentes e inspeccion) y externas (red de proteccion).
 * El personal docente solo ve las derivaciones que el mismo registro; el DECE
 * administra el seguimiento y la respuesta institucional.
 */
class DerivacionController extends Controller
{
    use AutorizaDece;
    use CargaAlumnoSeleccionado;
    use FiltraPorAlumno;

    public function __construct(private readonly RegistrarAuditoria $auditoria) {}

    public function index(Request $request)
    {
        $filtros = $request->validate([
            'buscar' => ['nullable', 'string', 'max:100'],
            'alumno_id' => ['nullable', 'integer', 'exists:alumnos,id'],
            'tipo' => ['nullable', 'string', Rule::in(array_keys(DeceDerivacion::TIPOS))],
            'estado' => ['nullable', 'string', Rule::in(array_keys(DeceDerivacion::ESTADOS))],
        ]);

        $derivaciones = DeceDerivacion::with(['alumno', 'derivador'])
            ->buscar($this->textoBusqueda($filtros))
            ->when($filtros['alumno_id'] ?? null, fn ($query, int $id) => $query->where('alumno_id', $id))
            ->when(! $this->puedeGestionar($request), fn ($query) => $query->where('derivado_por', $request->user()->id))
            ->when($filtros['tipo'] ?? null, fn ($query, string $tipo) => $query->where('tipo', $tipo))
            ->when($filtros['estado'] ?? null, fn ($query, string $estado) => $query->where('estado', $estado))
            ->orderByDesc('fecha_derivacion')
            ->paginate(15)
            ->withQueryString();

        $tipos = DeceDerivacion::TIPOS;
        $estados = DeceDerivacion::ESTADOS;

        return view('dece.derivaciones.index', compact('derivaciones', 'tipos', 'estados', 'filtros'));
    }

    public function create(Request $request)
    {
        $alumnoSeleccionado = $this->alumnoSeleccionado($request);

        $tipos = $this->puedeGestionar($request)
            ? DeceDerivacion::TIPOS
            : [DeceDerivacion::TIPO_INTERNA => DeceDerivacion::TIPOS[DeceDerivacion::TIPO_INTERNA]];

        return view('dece.derivaciones.create', compact('alumnoSeleccionado', 'tipos'));
    }

    public function store(Request $request)
    {
        $esGestion = $this->puedeGestionar($request);

        $validated = $request->validate([
            'alumno_id' => ['required', 'integer', 'exists:alumnos,id'],
            'tipo' => ['required', 'string', Rule::in($esGestion
                ? array_keys(DeceDerivacion::TIPOS)
                : [DeceDerivacion::TIPO_INTERNA])],
            'motivo' => ['required', 'string', 'max:150'],
            'descripcion' => ['required', 'string', 'min:20', 'max:5000'],
            'entidad_externa' => ['nullable', 'string', 'max:255'],
            'oficio' => ['nullable', 'string', 'max:100'],
            'fecha_derivacion' => ['required', 'date'],
            'fecha_limite' => ['nullable', 'date', 'after_or_equal:fecha_derivacion'],
        ]);

        if ($validated['tipo'] === DeceDerivacion::TIPO_EXTERNA && empty($validated['entidad_externa'])) {
            throw ValidationException::withMessages([
                'entidad_externa' => 'Una derivación externa debe indicar la entidad de destino.',
            ]);
        }

        $derivacion = DeceDerivacion::create([
            'alumno_id' => $validated['alumno_id'],
            'tipo' => $validated['tipo'],
            'motivo' => trim($validated['motivo']),
            'descripcion' => trim($validated['descripcion']),
            'derivado_por' => $request->user()->id,
            'entidad_externa' => $validated['tipo'] === DeceDerivacion::TIPO_EXTERNA
                ? trim((string) $validated['entidad_externa'])
                : null,
            'oficio' => $validated['oficio'] ?? null,
            'fecha_derivacion' => $validated['fecha_derivacion'],
            'fecha_limite' => $validated['fecha_limite'] ?? null,
            'estado' => DeceDerivacion::ESTADO_PENDIENTE,
        ]);

        $this->auditoria->registrar(
            $request,
            $derivacion,
            DeceAuditoria::ACCION_CREACION,
            'Derivación '.$derivacion->tipoLabel().' registrada: '.$derivacion->motivo.'.',
            null,
            $derivacion->only(['tipo', 'estado', 'fecha_derivacion', 'fecha_limite']),
            $derivacion->alumno_id
        );

        $destino = $request->boolean('imprimir')
            ? route('dece.derivaciones.pdf', $derivacion)
            : route('dece.derivaciones.show', $derivacion);

        return redirect()
            ->to($destino)
            ->with('success', 'Derivación registrada. El DECE dará seguimiento al caso.');
    }

    public function show(Request $request, DeceDerivacion $derivacion)
    {
        if (! $this->puedeGestionar($request) && $derivacion->derivado_por !== $request->user()->id) {
            abort(403, 'Solo puede consultar las derivaciones que usted registró.');
        }

        $derivacion->load(['alumno.representante', 'derivador']);

        $this->auditoria->registrar(
            $request,
            $derivacion,
            DeceAuditoria::ACCION_CONSULTA,
            'Consulta de la derivación.',
            null,
            null,
            $derivacion->alumno_id
        );

        $estados = DeceDerivacion::ESTADOS;

        return view('dece.derivaciones.show', compact('derivacion', 'estados'));
    }

    /**
     * Documento oficial de la derivación. Se abre en el navegador para que el
     * usuario pueda imprimirlo o guardarlo como PDF.
     */
    public function pdf(Request $request, DeceDerivacion $derivacion)
    {
        if (! $this->puedeGestionar($request) && $derivacion->derivado_por !== $request->user()->id) {
            abort(403, 'Solo puede imprimir las derivaciones que usted registró.');
        }

        $derivacion->load(['alumno.representante', 'derivador']);

        $this->auditoria->registrar(
            $request,
            $derivacion,
            DeceAuditoria::ACCION_CONSULTA,
            'Emisión del documento de derivación en PDF.',
            null,
            null,
            $derivacion->alumno_id
        );

        $pdf = Pdf::loadView('dece.pdf.derivacion', [
            'derivacion' => $derivacion,
            'institucion' => Institucion::first(),
            'fechaEmision' => now(),
        ])->setPaper('a4', 'portrait');

        return $pdf->stream('derivacion-dece-'.$derivacion->id.'.pdf');
    }

    public function update(Request $request, DeceDerivacion $derivacion)
    {
        $this->soloGestion($request);

        $validated = $request->validate([
            'estado' => ['required', 'string', Rule::in(array_keys(DeceDerivacion::ESTADOS))],
            'respuesta' => ['nullable', 'string', 'max:5000'],
            'fecha_limite' => ['nullable', 'date'],
        ]);

        $anteriores = $derivacion->only(['estado', 'respuesta', 'fecha_limite']);
        $derivacion->update($validated);

        $this->auditoria->registrar(
            $request,
            $derivacion,
            DeceAuditoria::ACCION_ACTUALIZACION,
            'Derivación actualizada a '.$derivacion->estadoLabel().'.',
            $anteriores,
            $derivacion->only(['estado', 'respuesta', 'fecha_limite']),
            $derivacion->alumno_id
        );

        return back()->with('success', 'Derivación actualizada.');
    }
}
