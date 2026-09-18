<?php

namespace App\Http\Controllers\Directivo;

use App\Http\Controllers\Controller;
use App\Models\Alumno;
use App\Models\Cita;
use Illuminate\Http\Request;

class HistorialCitaController extends Controller
{
    public function index(Request $request)
    {
        $estudianteId = $request->query('alumno_id');

        $citas = Cita::with(['alumno.representante', 'docente', 'registrador'])
            ->when($estudianteId, fn ($q) => $q->where('alumno_id', $estudianteId))
            ->latest('fecha_inicio')
            ->get();

        $resumen = $citas
            ->reject(fn ($c) => $c->alumno === null)
            ->groupBy('alumno_id')
            ->map(function ($items) {
                $alumno = $items->first()->alumno;
                $matricula = $alumno->matriculas->last();

                return [
                    'id' => $alumno->id,
                    'alumno' => trim($alumno->apellidos . ' ' . $alumno->nombres),
                    'nivel' => $matricula
                        ? trim($matricula->curso?->nombre . ' ' . $matricula->paralelo?->nombre)
                        : '—',
                    'total' => $items->count(),
                    'asistio' => $items->where('resultado', 'asistio')->count(),
                    'no_asistio' => $items->where('resultado', 'no_asistio')->count(),
                    'sin_registrar' => $items->whereNull('resultado')->count(),
                    'reincidente' => $items->count() >= 3,
                    'docentes' => $items->pluck('docente')
                        ->filter()
                        ->map(fn ($d) => trim($d->name . ' ' . $d->apellidos))
                        ->unique()
                        ->implode(', '),
                    'ultimo_motivo' => $items->sortByDesc('fecha_inicio')->first()->motivo,
                ];
            })
            ->sortByDesc('total')
            ->values();

        $stats = [
            'total' => $citas->count(),
            'registradas' => $citas->whereNotNull('resultado')->count(),
            'asistencia' => $citas->whereNotNull('resultado')->count()
                ? (int) round($citas->where('resultado', 'asistio')->count()
                    / $citas->whereNotNull('resultado')->count() * 100)
                : 0,
            'reincidentes' => $resumen->where('reincidente')->count(),
        ];

        $alumnos = Alumno::with('matriculas')->has('matriculas')->orderBy('apellidos')->orderBy('nombres')->get();

        return view('directivo.citas.index', compact('citas', 'resumen', 'stats', 'alumnos', 'estudianteId'));
    }
}