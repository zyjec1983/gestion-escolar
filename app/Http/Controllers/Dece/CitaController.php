<?php

namespace App\Http\Controllers\Dece;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Dece\Concerns\AutorizaDece;
use App\Http\Controllers\Dece\Concerns\CargaAlumnoSeleccionado;
use App\Http\Controllers\Dece\Concerns\FiltraPorAlumno;
use App\Models\Alumno;
use App\Models\DeceAuditoria;
use App\Models\DeceCita;
use App\Models\User;
use App\Services\Dece\NotificarRepresentante;
use App\Services\Dece\RegistrarAuditoria;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Agenda de citas del DECE con los representantes legales. Al programar una
 * cita se envia una notificacion a la bandeja del representante y cada cambio
 * de estado queda auditado.
 */
class CitaController extends Controller
{
    use AutorizaDece;
    use CargaAlumnoSeleccionado;
    use FiltraPorAlumno;

    public function __construct(
        private readonly RegistrarAuditoria $auditoria,
        private readonly NotificarRepresentante $notificador
    ) {}

    public function index(Request $request)
    {
        $filtros = $request->validate([
            'buscar' => ['nullable', 'string', 'max:100'],
            'alumno_id' => ['nullable', 'integer', 'exists:alumnos,id'],
            'estado' => ['nullable', 'string', Rule::in(array_keys(DeceCita::ESTADOS))],
            'profesional_id' => ['nullable', 'integer', 'exists:users,id'],
            'desde' => ['nullable', 'date'],
            'hasta' => ['nullable', 'date'],
        ]);

        $citas = DeceCita::with(['alumno', 'representante', 'profesional'])
            ->when(! ($filtros['alumno_id'] ?? null) && ($filtros['buscar'] ?? null), function ($query, string $buscar) {
                $query->where(function ($sub) use ($buscar) {
                    $sub->where('motivo', 'like', "%{$buscar}%")
                        ->orWhereHas('alumno', function ($alumno) use ($buscar) {
                            $alumno->where('nombres', 'like', "%{$buscar}%")
                                ->orWhere('apellidos', 'like', "%{$buscar}%")
                                ->orWhere('numero_cedula', 'like', "%{$buscar}%");
                        });
                });
            })
            ->when($filtros['alumno_id'] ?? null, fn ($query, int $id) => $query->where('alumno_id', $id))
            ->when($filtros['estado'] ?? null, fn ($query, string $estado) => $query->where('estado', $estado))
            ->when($filtros['profesional_id'] ?? null, fn ($query, int $id) => $query->where('profesional_id', $id))
            ->when($filtros['desde'] ?? null, fn ($query, string $desde) => $query->whereDate('fecha_hora', '>=', $desde))
            ->when($filtros['hasta'] ?? null, fn ($query, string $hasta) => $query->whereDate('fecha_hora', '<=', $hasta))
            ->orderByDesc('fecha_hora')
            ->paginate(15)
            ->withQueryString();

        $estados = DeceCita::ESTADOS;
        $profesionales = User::whereIn('rol', ['DECE', 'ADMIN'])->orderBy('apellidos')->get();

        return view('dece.citas.index', compact('citas', 'estados', 'profesionales', 'filtros'));
    }

    public function create(Request $request)
    {
        $alumnoSeleccionado = $this->alumnoSeleccionado($request);

        $profesionales = User::whereIn('rol', ['DECE', 'ADMIN'])->orderBy('apellidos')->get();

        return view('dece.citas.create', compact('alumnoSeleccionado', 'profesionales'));
    }

    public function store(Request $request)
    {
        $this->soloGestion($request);

        $validated = $request->validate([
            'alumno_id' => ['required', 'integer', 'exists:alumnos,id'],
            'representante_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'profesional_id' => ['nullable', 'integer', 'exists:users,id'],
            'fecha_hora' => ['required', 'date', 'after:now'],
            'duracion_minutos' => ['required', 'integer', 'min:10', 'max:240'],
            'motivo' => ['required', 'string', 'max:150'],
            'observaciones' => ['nullable', 'string', 'max:2000'],
        ]);

        $cita = DB::transaction(function () use ($request, $validated) {
            $alumno = Alumno::findOrFail($validated['alumno_id']);

            $cita = DeceCita::create([
                'alumno_id' => $alumno->id,
                'representante_user_id' => $validated['representante_user_id']
                    ?? $alumno->representante_user_id,
                'profesional_id' => $validated['profesional_id'] ?? $request->user()->id,
                'fecha_hora' => $validated['fecha_hora'],
                'duracion_minutos' => $validated['duracion_minutos'],
                'motivo' => trim($validated['motivo']),
                'estado' => DeceCita::ESTADO_PROGRAMADA,
                'observaciones' => isset($validated['observaciones']) ? trim($validated['observaciones']) : null,
                'created_by' => $request->user()->id,
            ]);

            $this->auditoria->registrar(
                $request,
                $cita,
                DeceAuditoria::ACCION_CREACION,
                'Cita programada con el representante: '.$cita->motivo.'.',
                null,
                $cita->only(['fecha_hora', 'motivo', 'estado']),
                $cita->alumno_id
            );

            $mensaje = $this->notificador->notificarCita($cita, $request->user());

            if ($mensaje) {
                $cita->update(['notificado_en' => now()]);

                $this->auditoria->registrar(
                    $request,
                    $cita,
                    DeceAuditoria::ACCION_NOTIFICACION,
                    'Notificación de la citación enviada al representante.',
                    null,
                    ['mensaje_id' => $mensaje->id],
                    $cita->alumno_id
                );
            }

            return $cita;
        });

        return redirect()
            ->route('dece.citas.index')
            ->with('success', 'Cita programada. Se notificó al representante en su bandeja de mensajes.');
    }

    public function show(Request $request, DeceCita $cita)
    {
        $cita->load(['alumno.representante', 'representante', 'profesional', 'creador', 'atenciones']);

        $this->auditoria->registrar(
            $request,
            $cita,
            DeceAuditoria::ACCION_CONSULTA,
            'Consulta de la cita del DECE.',
            null,
            null,
            $cita->alumno_id
        );

        return view('dece.citas.show', compact('cita'));
    }

    public function registrar(Request $request, DeceCita $cita)
    {
        $this->soloGestion($request);

        $validated = $request->validate([
            'estado' => ['required', 'string', Rule::in([
                DeceCita::ESTADO_ASISTIO,
                DeceCita::ESTADO_NO_ASISTIO,
                DeceCita::ESTADO_CANCELADA,
            ])],
            'observaciones' => ['nullable', 'string', 'max:2000'],
        ]);

        $anterior = $cita->estado;

        $cita->update([
            'estado' => $validated['estado'],
            'observaciones' => isset($validated['observaciones'])
                ? trim($validated['observaciones'])
                : $cita->observaciones,
        ]);

        $this->auditoria->registrar(
            $request,
            $cita,
            DeceAuditoria::ACCION_ACTUALIZACION,
            'Resultado de la cita registrado como '.$cita->estadoLabel().'.',
            ['estado' => $anterior],
            ['estado' => $cita->estado],
            $cita->alumno_id
        );

        return redirect()
            ->route('dece.citas.show', $cita)
            ->with('success', 'Resultado de la cita registrado.');
    }
}
