<?php

namespace App\Services\Dece;

use App\Models\DeceAcuerdo;
use App\Models\DeceAtencion;
use App\Models\DeceCita;
use App\Models\DeceDerivacion;
use App\Models\DeceNee;
use App\Models\Observacion;
use Carbon\Carbon;

/**
 * Indicadores agregados del DECE para el dashboard y los reportes de
 * direccion. No expone relatorias ni datos clinicos individuales.
 */
class EstadisticasDece
{
    /**
     * @return array<string, mixed>
     */
    public function resumen(?Carbon $desde = null, ?Carbon $hasta = null): array
    {
        $desde ??= now()->startOfYear();
        $hasta ??= now()->endOfDay();

        $atenciones = DeceAtencion::whereBetween('fecha_atencion', [$desde, $hasta])->get(['fecha_atencion', 'motivo']);
        $observaciones = Observacion::with(['alumno.matriculas.curso', 'tipoObservacion'])
            ->whereBetween('created_at', [$desde, $hasta])
            ->get();

        $porMes = $atenciones
            ->groupBy(fn (DeceAtencion $atencion) => $atencion->fecha_atencion->format('Y-m'))
            ->map(fn ($grupo) => $grupo->count())
            ->sortKeys();

        $porMotivo = $atenciones
            ->groupBy('motivo')
            ->map(fn ($grupo) => $grupo->count())
            ->sortDesc();

        $cursosConflicto = $observaciones
            ->filter(fn (Observacion $observacion) => in_array(
                $observacion->tipoObservacion?->clasificacion,
                ['grave', 'muy_grave'],
                true
            ))
            ->groupBy(function (Observacion $observacion) {
                $matricula = $observacion->alumno?->matriculas->sortByDesc('id')->first();

                return $matricula?->curso?->nombre ?? 'Sin curso';
            })
            ->map(fn ($grupo) => $grupo->count())
            ->sortDesc()
            ->take(10);

        return [
            'desde' => $desde,
            'hasta' => $hasta,
            'atencionesPeriodo' => $atenciones->count(),
            'atencionesMes' => DeceAtencion::whereBetween('fecha_atencion', [
                now()->startOfMonth(), now()->endOfMonth(),
            ])->count(),
            'citasProximas' => DeceCita::proximas()->count(),
            'citasSemana' => DeceCita::proximas()
                ->whereBetween('fecha_hora', [now(), now()->addDays(7)])
                ->count(),
            'acuerdosActivos' => DeceAcuerdo::activos()->count(),
            'acuerdosPorVencer' => DeceAcuerdo::activos()
                ->whereBetween('fecha_compromiso', [
                    now()->startOfDay()->toDateString(),
                    now()->addDays(7)->toDateString(),
                ])
                ->count(),
            'neeActivos' => DeceNee::activos()->count(),
            'derivacionesAbiertas' => DeceDerivacion::abiertas()->count(),
            'derivacionesVencidas' => DeceDerivacion::abiertas()
                ->whereNotNull('fecha_limite')
                ->where('fecha_limite', '<', now()->toDateString())
                ->count(),
            'porMes' => $porMes,
            'porMotivo' => $porMotivo,
            'cursosConflicto' => $cursosConflicto,
        ];
    }
}
