<?php

namespace App\Http\Controllers\Dece;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Dece\Concerns\AutorizaDece;
use App\Http\Controllers\Dece\Concerns\CargaAlumnoSeleccionado;
use App\Http\Controllers\Dece\Concerns\FiltraPorAlumno;
use App\Models\DeceAcuerdo;
use App\Models\DeceAcuerdoSeguimiento;
use App\Models\DeceAtencion;
use App\Models\DeceAuditoria;
use App\Models\Institucion;
use App\Services\Dece\RegistrarAuditoria;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Acuerdos y actas de compromiso. El acta solo se emite cuando las tres firmas
 * estan registradas; cada firma y cada seguimiento deja rastro en la bitacora.
 */
class AcuerdoController extends Controller
{
    use AutorizaDece;
    use CargaAlumnoSeleccionado;
    use FiltraPorAlumno;

    public function __construct(private readonly RegistrarAuditoria $auditoria) {}

    public function index(Request $request)
    {
        DeceAcuerdo::marcarVencidos();

        $filtros = $request->validate([
            'buscar' => ['nullable', 'string', 'max:100'],
            'alumno_id' => ['nullable', 'integer', 'exists:alumnos,id'],
            'tipo' => ['nullable', 'string', Rule::in(array_keys(DeceAcuerdo::TIPOS))],
            'estado' => ['nullable', 'string', Rule::in(array_keys(DeceAcuerdo::ESTADOS))],
        ]);

        $acuerdos = DeceAcuerdo::with(['alumno', 'profesional'])
            ->buscar($this->textoBusqueda($filtros))
            ->when($filtros['alumno_id'] ?? null, fn ($query, int $id) => $query->where('alumno_id', $id))
            ->when($filtros['tipo'] ?? null, fn ($query, string $tipo) => $query->where('tipo', $tipo))
            ->when($filtros['estado'] ?? null, fn ($query, string $estado) => $query->where('estado', $estado))
            ->orderByDesc('fecha_inicio')
            ->paginate(15)
            ->withQueryString();

        $tipos = DeceAcuerdo::TIPOS;
        $estados = DeceAcuerdo::ESTADOS;

        return view('dece.acuerdos.index', compact('acuerdos', 'tipos', 'estados', 'filtros'));
    }

    public function create(Request $request)
    {
        $alumnoSeleccionado = $this->alumnoSeleccionado($request);

        $atenciones = $alumnoSeleccionado
            ? DeceAtencion::where('alumno_id', $alumnoSeleccionado->id)
                ->latest('fecha_atencion')
                ->get()
            : collect();

        $tipos = DeceAcuerdo::TIPOS;

        return view('dece.acuerdos.create', compact('alumnoSeleccionado', 'atenciones', 'tipos'));
    }

    public function store(Request $request)
    {
        $this->soloGestion($request);

        $validated = $request->validate([
            'alumno_id' => ['required', 'integer', 'exists:alumnos,id'],
            'atencion_id' => ['nullable', 'integer', 'exists:dece_atenciones,id'],
            'tipo' => ['required', 'string', Rule::in(array_keys(DeceAcuerdo::TIPOS))],
            'descripcion' => ['required', 'string', 'min:20', 'max:5000'],
            'compromiso_estudiante' => ['nullable', 'string', 'max:3000'],
            'compromiso_representante' => ['nullable', 'string', 'max:3000'],
            'fecha_inicio' => ['required', 'date'],
            'fecha_compromiso' => ['required', 'date', 'after_or_equal:fecha_inicio'],
        ]);

        $acuerdo = DeceAcuerdo::create([
            'alumno_id' => $validated['alumno_id'],
            'atencion_id' => $validated['atencion_id'] ?? null,
            'profesional_id' => $request->user()->id,
            'tipo' => $validated['tipo'],
            'descripcion' => trim($validated['descripcion']),
            'compromiso_estudiante' => isset($validated['compromiso_estudiante']) ? trim($validated['compromiso_estudiante']) : null,
            'compromiso_representante' => isset($validated['compromiso_representante']) ? trim($validated['compromiso_representante']) : null,
            'fecha_inicio' => $validated['fecha_inicio'],
            'fecha_compromiso' => $validated['fecha_compromiso'],
            'estado' => DeceAcuerdo::ESTADO_ACTIVO,
        ]);

        $this->auditoria->registrar(
            $request,
            $acuerdo,
            DeceAuditoria::ACCION_CREACION,
            'Acuerdo de compromiso registrado: '.$acuerdo->tipoLabel().'.',
            null,
            $acuerdo->only(['tipo', 'fecha_inicio', 'fecha_compromiso', 'estado']),
            $acuerdo->alumno_id
        );

        return redirect()
            ->route('dece.acuerdos.show', $acuerdo)
            ->with('success', 'Acuerdo registrado. Registre las firmas para poder emitir el acta.');
    }

    public function show(Request $request, DeceAcuerdo $acuerdo)
    {
        $acuerdo->load(['alumno.representante', 'profesional', 'atencion', 'seguimientos.registrador']);

        $this->auditoria->registrar(
            $request,
            $acuerdo,
            DeceAuditoria::ACCION_CONSULTA,
            'Consulta del acuerdo de compromiso.',
            null,
            null,
            $acuerdo->alumno_id
        );

        $tipos = DeceAcuerdo::TIPOS;
        $estadosSeguimiento = DeceAcuerdoSeguimiento::ESTADOS;

        return view('dece.acuerdos.show', compact('acuerdo', 'tipos', 'estadosSeguimiento'));
    }

    public function firmar(Request $request, DeceAcuerdo $acuerdo)
    {
        $this->soloGestion($request);

        $validated = $request->validate([
            'firma' => ['required', 'string', Rule::in(['estudiante', 'representante', 'profesional'])],
        ]);

        $campo = 'firmado_'.$validated['firma'].'_en';

        if ($acuerdo->{$campo} !== null) {
            return back()->with('info', 'Esa firma ya estaba registrada.');
        }

        $acuerdo->update([$campo => now()]);

        $this->auditoria->registrar(
            $request,
            $acuerdo,
            DeceAuditoria::ACCION_FIRMA,
            'Firma digital registrada por '.$validated['firma'].'.',
            [$campo => null],
            [$campo => $acuerdo->{$campo}?->toIso8601String()],
            $acuerdo->alumno_id
        );

        return back()->with('success', 'Firma registrada con sello de tiempo.');
    }

    public function seguimiento(Request $request, DeceAcuerdo $acuerdo)
    {
        $this->soloGestion($request);

        $validated = $request->validate([
            'fecha' => ['required', 'date'],
            'estado' => ['required', 'string', Rule::in(array_keys(DeceAcuerdoSeguimiento::ESTADOS))],
            'observacion' => ['nullable', 'string', 'max:3000'],
        ]);

        $seguimiento = $acuerdo->seguimientos()->create([
            'fecha' => $validated['fecha'],
            'estado' => $validated['estado'],
            'observacion' => isset($validated['observacion']) ? trim($validated['observacion']) : null,
            'registrado_por' => $request->user()->id,
        ]);

        $nuevoEstado = match ($validated['estado']) {
            DeceAcuerdoSeguimiento::ESTADO_CUMPLIDO => DeceAcuerdo::ESTADO_CUMPLIDO,
            DeceAcuerdoSeguimiento::ESTADO_INCUMPLIDO => DeceAcuerdo::ESTADO_INCUMPLIDO,
            default => DeceAcuerdo::ESTADO_ACTIVO,
        };

        $anterior = $acuerdo->estado;
        $acuerdo->update(['estado' => $nuevoEstado]);

        $this->auditoria->registrar(
            $request,
            $seguimiento,
            DeceAuditoria::ACCION_SEGUIMIENTO,
            'Seguimiento del acuerdo registrado como '.$seguimiento->estadoLabel().'.',
            ['acuerdo_estado' => $anterior],
            ['acuerdo_estado' => $nuevoEstado, 'seguimiento_id' => $seguimiento->id],
            $acuerdo->alumno_id
        );

        return back()->with('success', 'Seguimiento registrado.');
    }

    public function pdf(Request $request, DeceAcuerdo $acuerdo)
    {
        if (! $acuerdo->firmadoCompleto()) {
            return back()->with('error', 'El acta solo se emite cuando las tres firmas están registradas.');
        }

        $acuerdo->load(['alumno.representante', 'profesional', 'seguimientos']);

        $this->auditoria->registrar(
            $request,
            $acuerdo,
            DeceAuditoria::ACCION_CONSULTA,
            'Emisión del acta de compromiso en PDF.',
            null,
            null,
            $acuerdo->alumno_id
        );

        $pdf = Pdf::loadView('dece.pdf.acta-acuerdo', [
            'acuerdo' => $acuerdo,
            'institucion' => Institucion::first(),
            'fechaEmision' => now(),
        ])->setPaper('a4', 'portrait');

        return $pdf->download('acta-dece-'.$acuerdo->id.'.pdf');
    }
}
