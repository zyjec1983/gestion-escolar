<?php

namespace App\Services\Dece;

use App\Models\Alumno;
use App\Models\DeceAtencion;
use App\Models\DeceCita;
use App\Models\Observacion;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Detecta focos rojos: estudiantes que acumulan atenciones, faltas graves o
 * inasistencias a citas en una ventana de tiempo. El puntaje permite priorizar
 * la intervencion sin exponer datos clinicos en el panel de direccion.
 */
class AlertasDece
{
    /**
     * @return Collection<int, array{alumno: Alumno, atenciones: int, observaciones: int, inasistencias: int, puntaje: int, nivel: string}>
     */
    public function focosRojos(int $dias = 60, int $umbral = 3): Collection
    {
        $desde = now()->subDays($dias);

        $atenciones = DeceAtencion::query()
            ->select('alumno_id', DB::raw('COUNT(*) as total'))
            ->where('fecha_atencion', '>=', $desde)
            ->groupBy('alumno_id')
            ->havingRaw('COUNT(*) >= ?', [$umbral])
            ->pluck('total', 'alumno_id');

        $observaciones = Observacion::query()
            ->select('alumno_id', DB::raw('COUNT(*) as total'))
            ->where('created_at', '>=', $desde)
            ->whereHas('tipoObservacion', fn ($query) => $query->whereIn('clasificacion', ['grave', 'muy_grave']))
            ->groupBy('alumno_id')
            ->havingRaw('COUNT(*) >= ?', [$umbral])
            ->pluck('total', 'alumno_id');

        $inasistencias = DeceCita::query()
            ->select('alumno_id', DB::raw('COUNT(*) as total'))
            ->where('estado', DeceCita::ESTADO_NO_ASISTIO)
            ->where('fecha_hora', '>=', $desde)
            ->groupBy('alumno_id')
            ->havingRaw('COUNT(*) >= ?', [2])
            ->pluck('total', 'alumno_id');

        $ids = $atenciones->keys()
            ->merge($observaciones->keys())
            ->merge($inasistencias->keys())
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return collect();
        }

        return Alumno::whereIn('id', $ids)
            ->orderBy('apellidos')
            ->orderBy('nombres')
            ->get()
            ->map(function (Alumno $alumno) use ($atenciones, $observaciones, $inasistencias) {
                $totalAtenciones = (int) ($atenciones[$alumno->id] ?? 0);
                $totalObservaciones = (int) ($observaciones[$alumno->id] ?? 0);
                $totalInasistencias = (int) ($inasistencias[$alumno->id] ?? 0);
                $puntaje = $totalAtenciones + $totalObservaciones + $totalInasistencias;

                return [
                    'alumno' => $alumno,
                    'atenciones' => $totalAtenciones,
                    'observaciones' => $totalObservaciones,
                    'inasistencias' => $totalInasistencias,
                    'puntaje' => $puntaje,
                    'nivel' => $puntaje >= 6 ? 'alto' : ($puntaje >= 4 ? 'medio' : 'bajo'),
                ];
            })
            ->sortByDesc('puntaje')
            ->values();
    }
}
