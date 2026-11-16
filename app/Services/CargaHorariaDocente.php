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
 * - El contrato se escribe en horas reloj (40, 30, 20...), no en una lista fija.
 * - El 75 % del contrato es carga de plantel y el 25 % horas autónomas.
 *   Un contrato de 40 h admite 30 h de plantel + 10 h autónomas; uno de 20 h
 *   admite 15 h + 5 h; uno de 30 h admite 22,5 h + 7,5 h.
 * - Las horas de reunión de área, atención a padres y tutoría son horas
 *   académicas de 40 minutos y se suman a la carga de clases.
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
     *     max_horas_plantel: float,
     *     horas_autonomas: float,
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
        $contrato = max(1, (int) $docente->horas_contrato);
        $maxHorasPlantel = round($contrato * 0.75, 2);
        $horasAutonomas = round($contrato * 0.25, 2);
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

    /** Formatea horas reloj sin ceros innecesarios: 30, 22,5 o 7,25. */
    public static function formatearHoras(float $horas): string
    {
        $texto = number_format($horas, 2, ',', '.');

        return rtrim(rtrim($texto, '0'), ',');
    }
}
