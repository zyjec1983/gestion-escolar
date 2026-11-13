<?php

namespace App\Services\Dece;

use App\Models\Alumno;
use App\Models\Asistencia;
use App\Models\DeceAcuerdo;
use App\Models\DeceAtencion;
use App\Models\DeceCita;
use App\Models\DeceDerivacion;
use App\Models\DeceNee;
use App\Models\Nota;
use App\Models\Observacion;
use App\Services\CalculoNotas;
use App\Services\ConfiguracionNotas;

/**
 * Arma el expediente integral de un estudiante para que el profesional del
 * DECE entienda el contexto antes de intervenir: notas, asistencia,
 * observaciones de inspeccion y antecedentes del departamento.
 */
class ResumenExpediente
{
    public function __construct(
        private readonly ConfiguracionNotas $configuracion,
        private readonly CalculoNotas $calculo
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function para(Alumno $alumno): array
    {
        $alumno->load([
            'representante',
            'matriculas.curso',
            'matriculas.paralelo',
            'matriculas.periodo',
        ]);

        $matricula = $alumno->matriculas->sortByDesc('id')->first();
        $sistema = $this->configuracion->sistema();

        $notas = Nota::with(['claseDocente.materia', 'claseDocente.docente'])
            ->where('alumno_id', $alumno->id)
            ->get();

        $resumenNotas = $notas
            ->groupBy('clase_docente_id')
            ->map(function ($grupo) use ($alumno, $sistema) {
                $primera = $grupo->first();
                $resumen = $this->calculo->resumen(
                    collect([$alumno]),
                    collect([$alumno->id => $grupo]),
                    $sistema
                );

                return [
                    'materia' => $primera->claseDocente?->materia?->nombre ?? 'Sin materia',
                    'docente' => $primera->claseDocente?->docente,
                    'anio' => $resumen[0]['anio'] ?? null,
                ];
            })
            ->sortBy('materia')
            ->values();

        $promedioGeneral = $resumenNotas->pluck('anio')->filter(fn ($valor) => $valor !== null)->avg();

        $asistencias = Asistencia::where('alumno_id', $alumno->id)->get();
        $totalAsistencias = $asistencias->count();
        $asistidas = $asistencias->whereIn('estado', ['presente', 'justificada', 'atraso'])->count();

        $observaciones = Observacion::with(['tipoObservacion', 'profesor'])
            ->where('alumno_id', $alumno->id)
            ->latest()
            ->get();

        $atenciones = DeceAtencion::with(['profesional', 'archivos'])
            ->where('alumno_id', $alumno->id)
            ->latest('fecha_atencion')
            ->get();

        $citas = DeceCita::with(['representante', 'profesional'])
            ->where('alumno_id', $alumno->id)
            ->orderByDesc('fecha_hora')
            ->get();

        $acuerdos = DeceAcuerdo::with(['profesional', 'seguimientos'])
            ->where('alumno_id', $alumno->id)
            ->latest('fecha_inicio')
            ->get();

        $nee = DeceNee::with('diagnostico')
            ->where('alumno_id', $alumno->id)
            ->latest()
            ->get();

        $derivaciones = DeceDerivacion::with('derivador')
            ->where('alumno_id', $alumno->id)
            ->latest('fecha_derivacion')
            ->get();

        return [
            'alumno' => $alumno,
            'matricula' => $matricula,
            'resumenNotas' => $resumenNotas,
            'promedioGeneral' => $promedioGeneral,
            'asistencia' => [
                'total' => $totalAsistencias,
                'asistidas' => $asistidas,
                'porcentaje' => $totalAsistencias > 0
                    ? round($asistidas / $totalAsistencias * 100, 2)
                    : null,
            ],
            'observaciones' => $observaciones,
            'faltasGraves' => $observaciones
                ->filter(fn (Observacion $observacion) => in_array(
                    $observacion->tipoObservacion?->clasificacion,
                    ['grave', 'muy_grave'],
                    true
                ))
                ->count(),
            'atenciones' => $atenciones,
            'citas' => $citas,
            'acuerdos' => $acuerdos,
            'nee' => $nee,
            'derivaciones' => $derivaciones,
        ];
    }
}
