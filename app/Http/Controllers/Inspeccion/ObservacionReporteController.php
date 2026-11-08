<?php

namespace App\Http\Controllers\Inspeccion;

use App\Http\Controllers\Controller;
use App\Models\Alumno;
use App\Models\Curso;
use App\Models\Observacion;
use App\Models\Paralelo;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class ObservacionReporteController extends Controller implements HasMiddleware
{
    /** Colores por clasificación (consistente con estadísticas). */
    private const COLORES = [
        'positiva' => '#198754',
        'leve' => '#ffc107',
        'grave' => '#fd7e14',
        'muy_grave' => '#dc3545',
    ];

    public static function middleware(): array
    {
        return [
            new Middleware('auth'),
            new Middleware('role:INSPECCION,RECTORA,VICERRECTORA,DECE'),
        ];
    }

    /** Reporte por alumno. */
    public function reportePorAlumno(Request $request)
    {
        $niveles = Curso::orderBy('nombre')->get(['id', 'nombre']);
        $nivelId = (int) $request->query('nivel') ?: null;
        $paraleloId = (int) $request->query('paralelo') ?: null;
        $alumnoId = (int) $request->query('alumno') ?: null;
        $fechaDesde = $this->parseFecha($request->query('fecha_desde'));
        $fechaHasta = $this->parseFecha($request->query('fecha_hasta'));

        $paralelos = $nivelId
            ? Paralelo::whereHas('matriculas', fn ($q) => $q->where('curso_id', $nivelId))
                ->orderBy('nombre')->get(['id', 'nombre'])
            : Paralelo::orderBy('nombre')->get(['id', 'nombre']);

        $filtroActivo = (bool) ($nivelId || $paraleloId || $alumnoId);

        $alumnos = $filtroActivo
            ? Alumno::with('representante')
                ->whereHas('matriculas', fn ($q) => $q
                    ->when($nivelId, fn ($qq) => $qq->where('curso_id', $nivelId))
                    ->when($paraleloId, fn ($qq) => $qq->where('paralelo_id', $paraleloId)))
                ->orderBy('apellidos')->orderBy('nombres')
                ->get()
            : collect();

        $alumnoSel = $alumnoId ? $alumnos->firstWhere('id', $alumnoId) : null;

        $observaciones = collect();
        if ($alumnoSel) {
            $observaciones = Observacion::with(['tipoObservacion', 'profesor', 'alumno'])
                ->where('alumno_id', $alumnoSel->id)
                ->when($fechaDesde, fn ($q) => $q->whereDate('created_at', '>=', $fechaDesde))
                ->when($fechaHasta, fn ($q) => $q->whereDate('created_at', '<=', $fechaHasta))
                ->latest()
                ->get();
        } elseif ($filtroActivo) {
            // Ver todas las del nivel/paralelo en rango
            $observaciones = Observacion::with(['tipoObservacion', 'profesor', 'alumno'])
                ->whereHas('alumno.matriculas', fn ($q) => $q
                    ->when($nivelId, fn ($qq) => $qq->where('curso_id', $nivelId))
                    ->when($paraleloId, fn ($qq) => $qq->where('paralelo_id', $paraleloId)))
                ->when($fechaDesde, fn ($q) => $q->whereDate('created_at', '>=', $fechaDesde))
                ->when($fechaHasta, fn ($q) => $q->whereDate('created_at', '<=', $fechaHasta))
                ->latest()
                ->limit(500)
                ->get();
        }

        // Resumen y hoja de vida del alumno buscado (desde la colección ya cargada).
        [$resumenAlumno, $hojaVida] = $this->resumenAlumno($observaciones, $alumnoSel);

        return view('inspeccion.observaciones.reporte-alumno', array_merge(compact(
            'niveles', 'paralelos', 'alumnos',
            'nivelId', 'paraleloId', 'alumnoId', 'alumnoSel',
            'fechaDesde', 'fechaHasta', 'observaciones', 'filtroActivo',
            'resumenAlumno', 'hojaVida'
        ), ['colores' => self::COLORES]));
    }

    public function pdfPorAlumno(Request $request)
    {
        $nivelId = (int) $request->query('nivel') ?: null;
        $paraleloId = (int) $request->query('paralelo') ?: null;
        $alumnoId = (int) $request->query('alumno') ?: null;
        $fechaDesde = $this->parseFecha($request->query('fecha_desde'));
        $fechaHasta = $this->parseFecha($request->query('fecha_hasta'));

        $alumno = $alumnoId ? Alumno::with('representante')->findOrFail($alumnoId) : null;

        $observaciones = collect();
        if ($alumno) {
            $observaciones = Observacion::with(['tipoObservacion', 'profesor'])
                ->where('alumno_id', $alumno->id)
                ->when($fechaDesde, fn ($q) => $q->whereDate('created_at', '>=', $fechaDesde))
                ->when($fechaHasta, fn ($q) => $q->whereDate('created_at', '<=', $fechaHasta))
                ->latest()
                ->get();
        } else {
            $observaciones = Observacion::with(['tipoObservacion', 'profesor', 'alumno'])
                ->whereHas('alumno.matriculas', fn ($q) => $q
                    ->when($nivelId, fn ($qq) => $qq->where('curso_id', $nivelId))
                    ->when($paraleloId, fn ($qq) => $qq->where('paralelo_id', $paraleloId)))
                ->when($fechaDesde, fn ($q) => $q->whereDate('created_at', '>=', $fechaDesde))
                ->when($fechaHasta, fn ($q) => $q->whereDate('created_at', '<=', $fechaHasta))
                ->latest()
                ->limit(1000)
                ->get();
        }

        [$resumen, $hojaVidaAlumno] = $this->resumenAlumno($observaciones, $alumno);

        $pdf = Pdf::loadView('inspeccion.observaciones.pdf-alumno', [
            'alumno' => $alumno,
            'observaciones' => $observaciones,
            'resumenAlumno' => $resumen,
            'hojaVida' => $hojaVidaAlumno,
            'nivelId' => $nivelId,
            'paraleloId' => $paraleloId,
            'fechaDesde' => $fechaDesde,
            'fechaHasta' => $fechaHasta,
        ])->setPaper('a4', 'portrait');

        $nombre = $alumno
            ? 'Observaciones-'.$alumno->apellidos.'-'.$alumno->nombres.'.pdf'
            : 'Observaciones-por-nivel-paralelo.pdf';

        return $pdf->download($nombre);
    }

    /**
     * Resumen de un alumno: total, desglose por clasificación y por tipo.
     * Devuelve [$resumen, $hojaVida]. Si no hay alumno, [$null, colección vacía].
     */
    private function resumenAlumno($observaciones, ?Alumno $alumno): array
    {
        if (! $alumno) {
            return [null, collect()];
        }

        $porClasificacion = ['positiva' => 0, 'leve' => 0, 'grave' => 0, 'muy_grave' => 0];
        foreach ($observaciones as $obs) {
            $c = $obs->tipoObservacion?->clasificacion ?? 'leve';
            $porClasificacion[$c] = ($porClasificacion[$c] ?? 0) + 1;
        }

        $hojaVida = $observaciones
            ->groupBy(fn ($o) => $o->tipoObservacion?->id ?? 0)
            ->map(function ($grupo) {
                $tipo = $grupo->first()->tipoObservacion;

                return (object) [
                    'tipo_nombre' => $tipo?->nombre ?? 'Sin tipo',
                    'clasificacion' => $tipo?->clasificacion ?? 'leve',
                    'total' => $grupo->count(),
                ];
            })->sortByDesc('total')->values();

        $resumen = [
            'total' => $observaciones->count(),
            'por_clasificacion' => $porClasificacion,
        ];

        return [$resumen, $hojaVida];
    }

    private function parseFecha($valor): ?Carbon
    {
        if (! $valor) {
            return null;
        }
        try {
            return Carbon::parse($valor);
        } catch (\Throwable $e) {
            return null;
        }
    }
}
