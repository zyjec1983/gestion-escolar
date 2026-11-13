<?php

namespace App\Http\Controllers\Dece;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Dece\Concerns\AutorizaDece;
use App\Http\Controllers\Dece\Concerns\CargaAlumnoSeleccionado;
use App\Http\Controllers\Dece\Concerns\FiltraPorAlumno;
use App\Models\DeceAtencion;
use App\Models\DeceAuditoria;
use App\Models\DeceCita;
use App\Models\Institucion;
use App\Services\Dece\GestorArchivos;
use App\Services\Dece\RegistrarAuditoria;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Registro de atenciones individuales del DECE. Cada ficha guarda el motivo,
 * la relatoria y los acuerdos, y permite adjuntar evidencias escritas.
 */
class AtencionController extends Controller
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
            'motivo' => ['nullable', 'string', 'in:'.implode(',', DeceAtencion::MOTIVOS)],
            'estado' => ['nullable', 'string', 'in:abierta,cerrada'],
            'desde' => ['nullable', 'date'],
            'hasta' => ['nullable', 'date'],
        ]);

        $atenciones = DeceAtencion::with(['alumno', 'profesional'])
            ->buscar($this->textoBusqueda($filtros))
            ->when($filtros['alumno_id'] ?? null, fn ($query, int $id) => $query->where('alumno_id', $id))
            ->when($filtros['motivo'] ?? null, fn ($query, string $motivo) => $query->where('motivo', $motivo))
            ->when($filtros['estado'] ?? null, fn ($query, string $estado) => $query->where('estado', $estado))
            ->when($filtros['desde'] ?? null, fn ($query, string $desde) => $query->whereDate('fecha_atencion', '>=', $desde))
            ->when($filtros['hasta'] ?? null, fn ($query, string $hasta) => $query->whereDate('fecha_atencion', '<=', $hasta))
            ->latest('fecha_atencion')
            ->paginate(15)
            ->withQueryString();

        $motivos = DeceAtencion::MOTIVOS;

        return view('dece.atenciones.index', compact('atenciones', 'motivos', 'filtros'));
    }

    public function create(Request $request)
    {
        $alumnoSeleccionado = $this->alumnoSeleccionado($request);

        $citas = $alumnoSeleccionado
            ? DeceCita::where('alumno_id', $alumnoSeleccionado->id)
                ->where('estado', DeceCita::ESTADO_PROGRAMADA)
                ->orderBy('fecha_hora')
                ->get()
            : collect();

        $motivos = DeceAtencion::MOTIVOS;

        return view('dece.atenciones.create', compact('alumnoSeleccionado', 'citas', 'motivos'));
    }

    public function store(Request $request)
    {
        $this->soloGestion($request);

        $validated = $request->validate([
            'alumno_id' => ['required', 'integer', 'exists:alumnos,id'],
            'cita_id' => ['nullable', 'integer', 'exists:dece_citas,id'],
            'fecha_atencion' => ['required', 'date'],
            'motivo' => ['required', 'string', 'in:'.implode(',', DeceAtencion::MOTIVOS)],
            'historia_narrada' => ['required', 'string', 'min:20', 'max:10000'],
            'acuerdos' => ['nullable', 'string', 'max:5000'],
            'evidencias' => ['nullable', 'array', 'max:5'],
            'evidencias.*' => ['file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ]);

        $atencion = DB::transaction(function () use ($request, $validated) {
            $atencion = DeceAtencion::create([
                'alumno_id' => $validated['alumno_id'],
                'profesional_id' => $request->user()->id,
                'cita_id' => $validated['cita_id'] ?? null,
                'fecha_atencion' => $validated['fecha_atencion'],
                'motivo' => $validated['motivo'],
                'historia_narrada' => trim($validated['historia_narrada']),
                'acuerdos' => isset($validated['acuerdos']) ? trim($validated['acuerdos']) : null,
                'estado' => DeceAtencion::ESTADO_ABIERTA,
                'confidencial' => true,
            ]);

            $this->auditoria->registrar(
                $request,
                $atencion,
                DeceAuditoria::ACCION_CREACION,
                'Atención individual registrada: '.$atencion->motivo.'.',
                null,
                $atencion->only(['motivo', 'fecha_atencion', 'estado']),
                $atencion->alumno_id
            );

            $guardados = [];
            try {
                $guardados = $this->archivos->guardar(
                    $request,
                    $atencion,
                    $request->file('evidencias', []),
                    'atenciones'
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
                    $atencion,
                    DeceAuditoria::ACCION_ARCHIVO,
                    'Evidencia adjunta: '.$archivo->nombre_original.'.',
                    null,
                    ['archivo' => $archivo->nombre_original],
                    $atencion->alumno_id
                );
            }

            return $atencion;
        });

        return redirect()
            ->route('dece.atenciones.show', $atencion)
            ->with('success', 'Atención registrada. La ficha quedó guardada con su bitácora de auditoría.');
    }

    public function show(Request $request, DeceAtencion $atencion)
    {
        $atencion->load(['alumno.representante', 'profesional', 'cita', 'archivos.subidor', 'acuerdosFormales']);

        $this->auditoria->registrar(
            $request,
            $atencion,
            DeceAuditoria::ACCION_CONSULTA,
            'Consulta de la ficha de atención.',
            null,
            null,
            $atencion->alumno_id
        );

        $puedeVerConfidencial = $this->puedeVerConfidencial($request);

        return view('dece.atenciones.show', compact('atencion', 'puedeVerConfidencial'));
    }

    public function cerrar(Request $request, DeceAtencion $atencion)
    {
        $this->soloGestion($request);

        if ($atencion->estaCerrada()) {
            return back()->with('info', 'La atención ya estaba cerrada.');
        }

        $atencion->update(['estado' => DeceAtencion::ESTADO_CERRADA]);

        $this->auditoria->registrar(
            $request,
            $atencion,
            DeceAuditoria::ACCION_CIERRE,
            'Atención cerrada por el profesional del DECE.',
            ['estado' => DeceAtencion::ESTADO_ABIERTA],
            ['estado' => DeceAtencion::ESTADO_CERRADA],
            $atencion->alumno_id
        );

        return back()->with('success', 'Atención cerrada. El registro sigue disponible para consulta.');
    }

    public function pdf(Request $request, DeceAtencion $atencion)
    {
        $atencion->load(['alumno.representante', 'profesional', 'archivos', 'acuerdosFormales']);

        $this->auditoria->registrar(
            $request,
            $atencion,
            DeceAuditoria::ACCION_CONSULTA,
            'Emisión de la ficha de atención en PDF.',
            null,
            null,
            $atencion->alumno_id
        );

        $pdf = Pdf::loadView('dece.pdf.ficha-atencion', [
            'atencion' => $atencion,
            'institucion' => Institucion::first(),
            'fechaEmision' => now(),
        ])->setPaper('a4', 'portrait');

        return $pdf->download('ficha-dece-'.$atencion->id.'.pdf');
    }
}
