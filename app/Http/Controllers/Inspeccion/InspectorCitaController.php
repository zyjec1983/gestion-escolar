<?php

namespace App\Http\Controllers\Inspeccion;

use App\Http\Controllers\Controller;
use App\Models\Alumno;
use App\Models\Cita;
use App\Models\Curso;
use App\Models\Paralelo;
use Carbon\Carbon;
use Illuminate\Http\Request;

class InspectorCitaController extends Controller
{
    public const MOTIVOS = [
        'Bajo rendimiento académico',
        'Problemas de conducta',
        'Inasistencias',
        'Reunión de padres',
        'Seguimiento académico',
        'Otro',
    ];

    public function agendar(Request $request)
    {
        $cursos = Curso::orderBy('nombre')->get(['id', 'nombre']);

        $cursoId = $request->query('curso_id') ?: null;
        $paraleloId = $request->query('paralelo_id') ?: null;
        $alumnoId = (int) $request->query('alumno_id') ?: null;

        $paralelos = $cursoId
            ? Paralelo::whereHas('matriculas', fn ($q) => $q->where('curso_id', $cursoId))
                ->orderBy('nombre')->get(['id', 'nombre'])
            : Paralelo::orderBy('nombre')->get(['id', 'nombre']);

        $alumnos = Alumno::with('representante')
            ->whereHas('matriculas', fn ($q) => $q
                ->when($cursoId, fn ($qq) => $qq->where('curso_id', $cursoId))
                ->when($paraleloId, fn ($qq) => $qq->where('paralelo_id', $paraleloId)))
            ->orderBy('apellidos')->orderBy('nombres')
            ->get();

        $alumno = $alumnoId ? $alumnos->firstWhere('id', $alumnoId) : null;

        if ($alumno) {
            $alumno->load(['matriculas.curso', 'matriculas.paralelo']);
        }

        $citasPrevias = $alumno
            ? Cita::with('docente')
                ->where('alumno_id', $alumno->id)
                ->orderByDesc('fecha_inicio')
                ->get()
            : collect();

        return view('inspeccion.citas.agendar', compact(
            'cursos', 'paralelos', 'alumnos',
            'cursoId', 'paraleloId', 'alumno', 'citasPrevias',
        ));
    }

    public function historial(Request $request)
    {
        $niveles = Curso::orderBy('nombre')->get(['id', 'nombre']);

        $nivelId = (int) $request->query('nivel') ?: null;
        $paraleloId = (int) $request->query('paralelo') ?: null;
        $alumnoId = (int) $request->query('alumno') ?: null;

        $paralelos = $nivelId
            ? Paralelo::whereHas('matriculas', fn ($q) => $q->where('curso_id', $nivelId))
                ->orderBy('nombre')->get(['id', 'nombre'])
            : Paralelo::orderBy('nombre')->get(['id', 'nombre']);

        $alumnos = Alumno::with('representante')
            ->whereHas('matriculas', fn ($q) => $q
                ->when($nivelId, fn ($qq) => $qq->where('curso_id', $nivelId))
                ->when($paraleloId, fn ($qq) => $qq->where('paralelo_id', $paraleloId)))
            ->orderBy('apellidos')->orderBy('nombres')
            ->get();

        $alumnoSel = $alumnoId ? $alumnos->firstWhere('id', $alumnoId) : null;

        // Citas del subconjunto filtrado (curso, o paralelo, o un estudiante).
        $citas = Cita::with(['alumno', 'docente', 'registrador'])
            ->whereNotNull('alumno_id')
            ->when($nivelId || $paraleloId, fn ($q) => $q->whereHas('alumno.matriculas', fn ($qq) => $qq
                ->when($nivelId, fn ($q3) => $q3->where('curso_id', $nivelId))
                ->when($paraleloId, fn ($q3) => $q3->where('paralelo_id', $paraleloId))))
            ->when($alumnoSel, fn ($q) => $q->where('alumno_id', $alumnoSel->id))
            ->orderByDesc('fecha_inicio')
            ->get();

        $registradas = $citas->whereNotNull('resultado');
        $stats = [
            'estudiantes' => $alumnos->count(),
            'total' => $citas->count(),
            'registradas' => $registradas->count(),
            'asistio' => $citas->where('resultado', 'asistio')->count(),
            'no_asistio' => $citas->where('resultado', 'no_asistio')->count(),
            'sin_registrar' => $citas->whereNull('resultado')->count(),
            'asistencia' => $registradas->count()
                ? (int) round($citas->where('resultado', 'asistio')->count() / $registradas->count() * 100)
                : 0,
        ];

        // Una fila por estudiante, aunque no tenga citas (para ver el listado completo).
        $porAlumno = $alumnos->map(function ($a) use ($citas) {
            $propias = $citas->where('alumno_id', $a->id)->sortByDesc('fecha_inicio')->values();
            $ultima = $propias->first();

            return [
                'alumno' => $a,
                'total' => $propias->count(),
                'asistio' => $propias->where('resultado', 'asistio')->count(),
                'no_asistio' => $propias->where('resultado', 'no_asistio')->count(),
                'sin_registrar' => $propias->whereNull('resultado')->count(),
                'reincidente' => $propias->count() >= 3,
                'citadores' => $propias->pluck('docente')->filter()
                    ->map(fn ($d) => nombreCorto($d->name))
                    ->unique()->implode(', '),
                'ultima_fecha' => $ultima?->fecha_inicio,
                'ultimo_motivo' => $ultima?->motivo,
            ];
        })->sortByDesc('total')->values();

        $stats['reincidentes'] = $porAlumno->where('reincidente')->count();

        return view('inspeccion.citas.historial', compact(
            'niveles', 'paralelos', 'alumnos',
            'nivelId', 'paraleloId', 'alumnoSel', 'citas', 'stats', 'porAlumno',
        ));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'alumno_id' => 'required|exists:alumnos,id',
            'fecha' => 'required|date|after_or_equal:today',
            'hora' => 'required|integer|between:0,23',
            'minuto' => 'required|in:0,15,30,45',
            'motivo' => 'required|string|max:255',
            'mensaje' => 'nullable|string|max:500',
        ]);

        $fechaHora = Carbon::parse($validated['fecha']
            . ' ' . sprintf('%02d:%02d', $validated['hora'], $validated['minuto']));

        Cita::create([
            'solicitante_type' => 'App\\Models\\User',
            'solicitante_id' => $request->user()->id,
            'docente_id' => $request->user()->id,
            'alumno_id' => $validated['alumno_id'],
            'fecha_inicio' => $fechaHora,
            'fecha_fin' => $fechaHora->copy()->addHour(),
            'estado' => 'pendiente',
            'motivo' => $validated['motivo'] . ($validated['mensaje'] ? "\n\n" . $validated['mensaje'] : ''),
        ]);

        return redirect()->route('inspeccion.citas.agendar', [
            'alumno_id' => $validated['alumno_id'],
        ])->with('success', 'Cita agendada correctamente.');
    }
}
