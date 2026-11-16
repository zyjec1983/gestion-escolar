<?php

namespace App\Services;

use App\Models\CursoMateria;
use App\Models\User;

/**
 * Carga horaria semanal de un docente.
 *
 * Qué hace: convierte los períodos pedagógicos de 40 minutos en horas reloj y
 * compara el total con el máximo permitido según el contrato.
 * Conecta con: App\Models\User, App\Models\ClaseDocente y la malla
 * `curso_materias`.
 *
 * Regla del template:
 * - Contrato de 40 h: 30 h de plantel + 10 h autónomas (45 períodos).
 * - Contrato de 20 h: 15 h de plantel + 5 h autónomas (22,5 períodos).
 */
class CargaHorariaDocente
{
    public const MINUTOS_PERIODO = 40;

    /**
     * @return array{
     *     periodos_clase: int,
     *     periodos_atencion: int,
     *     periodos_reunion: int,
     *     periodos_tutoria: int,
     *     total_periodos: int,
     *     horas_plantel: float,
     *     max_horas_plantel: int,
     *     horas_autonomas: int,
     *     max_periodos: float,
     *     horas_contingencia: float,
     *     periodos_disponibles: float,
     *     total_horas_jornada: float,
     *     excede: bool
     * }
     */
    public static function calcular(User $docente): array
    {
        $periodosClase = static::periodosClase($docente);
        $periodosAtencion = (int) $docente->periodos_atencion_padres;
        $periodosReunion = (int) $docente->periodos_reunion_area;
        $periodosTutoria = (int) $docente->periodos_tutoria;

        $totalPeriodos = $periodosClase + $periodosAtencion + $periodosReunion + $periodosTutoria;
        $horasPlantel = static::horasReloj($totalPeriodos);
        $contrato = (int) $docente->horas_contrato === 20 ? 20 : 40;
        $maxHorasPlantel = $contrato === 40 ? 30 : 15;
        $horasAutonomas = $contrato === 40 ? 10 : 5;
        $maxPeriodos = ($maxHorasPlantel * 60) / static::MINUTOS_PERIODO;

        return [
            'periodos_clase' => $periodosClase,
            'periodos_atencion' => $periodosAtencion,
            'periodos_reunion' => $periodosReunion,
            'periodos_tutoria' => $periodosTutoria,
            'total_periodos' => $totalPeriodos,
            'horas_plantel' => $horasPlantel,
            'max_horas_plantel' => $maxHorasPlantel,
            'horas_autonomas' => $horasAutonomas,
            'max_periodos' => $maxPeriodos,
            'horas_contingencia' => max(0, round($maxHorasPlantel - $horasPlantel, 2)),
            'periodos_disponibles' => max(0, round($maxPeriodos - $totalPeriodos, 2)),
            'total_horas_jornada' => round($horasPlantel + $horasAutonomas, 2),
            'excede' => $horasPlantel > $maxHorasPlantel,
        ];
    }

    /** Períodos semanales de todas las clases del docente según la malla. */
    public static function periodosClase(User $docente): int
    {
        return (int) CursoMateria::query()
            ->join('clases_docentes', function ($join) use ($docente) {
                $join->on('clases_docentes.curso_id', '=', 'curso_materias.curso_id')
                    ->on('clases_docentes.materia_id', '=', 'curso_materias.materia_id')
                    ->where('clases_docentes.user_id', '=', $docente->id);
            })
            ->sum('curso_materias.periodos_semanales');
    }

    public static function horasReloj(int $periodos): float
    {
        return round(($periodos * static::MINUTOS_PERIODO) / 60, 2);
    }
}
