<?php

namespace App\Services;

use App\Models\Alumno;
use App\Models\Asistencia;
use App\Models\ClaseDocente;
use App\Models\DeceNee;
use App\Models\Institucion;
use App\Models\Matricula;
use App\Models\Nota;
use App\Models\Observacion;
use App\Models\PeriodoLectivo;
use Illuminate\Support\Collection;

/**
 * Rendimiento académico consolidado para Rectorado y Vicerrectorado.
 *
 * Qué hace: reutiliza CalculoNotas para obtener la nota final de cada
 * estudiante en cada clase y agrupa los resultados por nivel, curso,
 * paralelo y asignatura. También detecta estudiantes en riesgo combinando
 * promedio, materias reprobadas, asistencia y NEE sin adaptación.
 * Conecta con: CalculoNotas, Nota, Matricula, ClaseDocente, Asistencia,
 * DeceNee y Observacion.
 * Rutas: /rector/rendimiento, /vicerrector/rendimiento y sus exportaciones.
 */
class RendimientoAcademico
{
    /** Nota mínima de aprobación, igual que las actas institucionales. */
    public const NOTA_MINIMA = 7.0;

    /** Asistencia mínima aceptable para no considerar riesgo. */
    public const ASISTENCIA_MINIMA = 85.0;

    public function __construct(private CalculoNotas $calculo) {}

    /** Periodo solicitado o, en su defecto, el activo. */
    public function periodo(?int $periodoId = null): ?PeriodoLectivo
    {
        if ($periodoId) {
            return PeriodoLectivo::find($periodoId);
        }

        return PeriodoLectivo::where('activo', true)->first()
            ?? PeriodoLectivo::orderByDesc('fecha_inicio')->first();
    }

    /** Sistema de evaluación configurado en la institución. */
    public function sistemaEvaluacion(): string
    {
        return Institucion::first()?->sistema_evaluacion ?? 'quimestre';
    }

    /**
     * Notas finales por estudiante y clase para el corte pedido.
     *
     * Cortes válidos: anual, q1, q2, p1, p2, p3, p4, p5, p6.
     *
     * @return Collection<int, array{clase: ClaseDocente, alumno: Alumno, matricula: ?Matricula, nota: ?float, aprobado: ?bool}>
     */
    public function notasPorClase(int $periodoId, string $corte = 'anual', array $filtros = []): Collection
    {
        $sistema = $this->sistemaEvaluacion();

        $clases = ClaseDocente::with(['docente', 'curso', 'paralelo', 'materia'])
            ->when(! empty($filtros['curso_id']), fn ($q) => $q->where('curso_id', $filtros['curso_id']))
            ->when(! empty($filtros['paralelo_id']), fn ($q) => $q->where('paralelo_id', $filtros['paralelo_id']))
            ->when(! empty($filtros['materia_id']), fn ($q) => $q->where('materia_id', $filtros['materia_id']))
            ->orderBy('curso_id')
            ->orderBy('paralelo_id')
            ->orderBy('materia_id')
            ->get();

        if ($clases->isEmpty()) {
            return collect();
        }

        $matriculas = Matricula::with('alumno')
            ->where('periodo_id', $periodoId)
            ->when(! empty($filtros['curso_id']), fn ($q) => $q->where('curso_id', $filtros['curso_id']))
            ->when(! empty($filtros['paralelo_id']), fn ($q) => $q->where('paralelo_id', $filtros['paralelo_id']))
            ->get();

        if ($matriculas->isEmpty()) {
            return collect();
        }

        $notas = Nota::whereIn('matricula_id', $matriculas->pluck('id'))
            ->whereIn('clase_docente_id', $clases->pluck('id'))
            ->get()
            ->groupBy('clase_docente_id');

        $filas = collect();
        $parcial = $this->parcialDelCorte($corte);

        foreach ($clases as $clase) {
            $delCurso = $matriculas->filter(
                fn ($m) => (int) $m->curso_id === (int) $clase->curso_id
                    && (int) $m->paralelo_id === (int) $clase->paralelo_id
            );

            if ($delCurso->isEmpty()) {
                continue;
            }

            $notasClase = ($notas[$clase->id] ?? collect())->groupBy('alumno_id');
            $alumnos = $delCurso->pluck('alumno')->filter()->values();

            if ($parcial !== null) {
                foreach ($alumnos as $alumno) {
                    $registro = ($notasClase[$alumno->id] ?? collect())->firstWhere('parcial', $parcial);
                    $nota = $this->notaDeParcial($registro, $parcial, $sistema);

                    $filas->push($this->fila(
                        $clase,
                        $alumno,
                        $delCurso->firstWhere('alumno_id', $alumno->id),
                        $nota
                    ));
                }

                continue;
            }

            foreach ($this->calculo->resumen($alumnos, $notasClase, $sistema) as $resumen) {
                $nota = match ($corte) {
                    'q1' => $resumen['q1'] ?? null,
                    'q2' => $resumen['q2'] ?? null,
                    default => $resumen['anio'] ?? null,
                };

                $filas->push($this->fila(
                    $clase,
                    $resumen['alumno'],
                    $delCurso->firstWhere('alumno_id', $resumen['alumno']->id),
                    $nota
                ));
            }
        }

        return $filas;
    }

    /**
     * Agrupa las filas de notas por nivel, curso, paralelo o asignatura.
     *
     * @return Collection<int, array{etiqueta: string, promedio: ?float, evaluados: int, aprobados: int, reprobados: int}>
     */
    public function resumenAgrupado(Collection $filas, string $llave): Collection
    {
        return $filas
            ->groupBy(fn (array $fila) => $this->etiquetaDe($fila['clase'], $llave))
            ->map(function (Collection $grupo, string $etiqueta) {
                $notas = $grupo->pluck('nota')->filter(fn ($n) => $n !== null);
                $aprobados = $notas->filter(fn ($n) => $n >= self::NOTA_MINIMA)->count();

                return [
                    'etiqueta' => $etiqueta,
                    'promedio' => $notas->isNotEmpty() ? round((float) $notas->avg(), 2) : null,
                    'evaluados' => $notas->count(),
                    'aprobados' => $aprobados,
                    'reprobados' => $notas->count() - $aprobados,
                ];
            })
            ->sortBy('etiqueta')
            ->values();
    }

    /** Promedio general de todas las notas evaluadas. */
    public function promedioGeneral(Collection $filas): ?float
    {
        $notas = $filas->pluck('nota')->filter(fn ($n) => $n !== null);

        return $notas->isNotEmpty() ? round((float) $notas->avg(), 2) : null;
    }

    /**
     * Distribución de notas por rango.
     *
     * @return array<int, array{etiqueta: string, rango: string, total: int, porcentaje: float, color: string}>
     */
    public function distribucion(Collection $filas): array
    {
        $notas = $filas->pluck('nota')->filter(fn ($n) => $n !== null);
        $total = $notas->count();

        $rangos = [
            ['etiqueta' => 'Insuficiente', 'rango' => 'Menos de 7', 'color' => 'danger', 'filtro' => fn ($n) => $n < 7],
            ['etiqueta' => 'Regular', 'rango' => '7 a 7,99', 'color' => 'warning', 'filtro' => fn ($n) => $n >= 7 && $n < 8],
            ['etiqueta' => 'Bueno', 'rango' => '8 a 8,99', 'color' => 'info', 'filtro' => fn ($n) => $n >= 8 && $n < 9],
            ['etiqueta' => 'Muy bueno', 'rango' => '9 a 9,49', 'color' => 'primary', 'filtro' => fn ($n) => $n >= 9 && $n < 9.5],
            ['etiqueta' => 'Excelente', 'rango' => '9,5 a 10', 'color' => 'success', 'filtro' => fn ($n) => $n >= 9.5],
        ];

        return collect($rangos)->map(function (array $rango) use ($notas, $total) {
            $cuenta = $notas->filter($rango['filtro'])->count();

            return [
                'etiqueta' => $rango['etiqueta'],
                'rango' => $rango['rango'],
                'total' => $cuenta,
                'porcentaje' => $total > 0 ? round($cuenta / $total * 100, 1) : 0.0,
                'color' => $rango['color'],
            ];
        })->all();
    }

    /**
     * Resumen por estudiante: promedio, materias evaluadas y reprobadas.
     *
     * @return Collection<int, array{alumno: Alumno, curso: ?string, paralelo: ?string, promedio: ?float, materias: int, reprobadas: int, estado: string}>
     */
    public function resumenAlumnos(Collection $filas): Collection
    {
        return $filas
            ->groupBy(fn (array $fila) => $fila['alumno']->id)
            ->map(function (Collection $grupo) {
                $notas = $grupo->pluck('nota')->filter(fn ($n) => $n !== null);
                $reprobadas = $notas->filter(fn ($n) => $n < self::NOTA_MINIMA)->count();
                $promedio = $notas->isNotEmpty() ? round((float) $notas->avg(), 2) : null;
                $primera = $grupo->first();

                return [
                    'alumno' => $primera['alumno'],
                    'curso' => $primera['clase']->curso?->nombre,
                    'paralelo' => $primera['clase']->paralelo?->nombre,
                    'promedio' => $promedio,
                    'materias' => $notas->count(),
                    'reprobadas' => $reprobadas,
                    'estado' => $promedio === null
                        ? 'Sin notas'
                        : ($promedio >= self::NOTA_MINIMA ? 'Aprobado' : 'Reprobado'),
                ];
            })
            ->sortBy(fn (array $fila) => [$fila['promedio'] ?? 999, $fila['alumno']->apellidos])
            ->values();
    }

    /**
     * Estudiantes con algún indicador de riesgo académico.
     *
     * Reglas: promedio menor a 7, tres o más materias reprobadas, asistencia
     * menor al 85 % o NEE activa sin grado de adaptación registrado.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function estudiantesEnRiesgo(int $periodoId, string $corte = 'anual', array $filtros = []): Collection
    {
        $filas = $this->notasPorClase($periodoId, $corte, $filtros);
        $porAlumno = $filas->groupBy(fn (array $fila) => $fila['alumno']->id);
        $asistencia = $this->asistenciaPorAlumno($periodoId);

        $nee = DeceNee::activos()
            ->whereNotNull('alumno_id')
            ->get()
            ->keyBy('alumno_id');

        $matriculas = Matricula::with(['alumno', 'curso', 'paralelo'])
            ->where('periodo_id', $periodoId)
            ->when(! empty($filtros['curso_id']), fn ($q) => $q->where('curso_id', $filtros['curso_id']))
            ->when(! empty($filtros['paralelo_id']), fn ($q) => $q->where('paralelo_id', $filtros['paralelo_id']))
            ->get();

        $resultado = collect();

        foreach ($matriculas as $matricula) {
            $alumno = $matricula->alumno;

            if (! $alumno) {
                continue;
            }

            $notas = ($porAlumno[$alumno->id] ?? collect())->pluck('nota')->filter(fn ($n) => $n !== null);
            $promedio = $notas->isNotEmpty() ? round((float) $notas->avg(), 2) : null;
            $reprobadas = $notas->filter(fn ($n) => $n < self::NOTA_MINIMA)->count();
            $asistenciaAlumno = $asistencia[$alumno->id] ?? null;
            $neeAlumno = $nee[$alumno->id] ?? null;
            $neeSinAdaptacion = $neeAlumno !== null && empty($neeAlumno->grado_adaptacion);

            $motivos = [];

            if ($promedio !== null && $promedio < self::NOTA_MINIMA) {
                $motivos[] = 'Promedio general menor a 7';
            }

            if ($reprobadas >= 3) {
                $motivos[] = 'Tres o más materias reprobadas';
            }

            if ($asistenciaAlumno !== null && $asistenciaAlumno < self::ASISTENCIA_MINIMA) {
                $motivos[] = 'Asistencia menor al 85 %';
            }

            if ($neeSinAdaptacion) {
                $motivos[] = 'NEE sin adaptación registrada';
            }

            if ($motivos === []) {
                continue;
            }

            $rojo = ($promedio !== null && $promedio < self::NOTA_MINIMA)
                || $reprobadas >= 3
                || ($asistenciaAlumno !== null && $asistenciaAlumno < self::ASISTENCIA_MINIMA);

            $resultado->push([
                'alumno' => $alumno,
                'matricula' => $matricula,
                'curso' => $matricula->curso?->nombre,
                'paralelo' => $matricula->paralelo?->nombre,
                'promedio' => $promedio,
                'materias' => $notas->count(),
                'reprobadas' => $reprobadas,
                'asistencia' => $asistenciaAlumno,
                'nee' => $neeAlumno,
                'nivel' => $rojo ? 'rojo' : 'amarillo',
                'motivos' => $motivos,
            ]);
        }

        $prioridad = ['rojo' => 0, 'amarillo' => 1];

        return $resultado
            ->sortBy(fn (array $fila) => [
                $prioridad[$fila['nivel']] ?? 2,
                $fila['promedio'] ?? 999,
                $fila['alumno']->apellidos,
            ])
            ->values();
    }

    /**
     * Ficha académica de un estudiante, sin datos clínicos del DECE.
     *
     * @return array<string, mixed>
     */
    public function fichaEstudiante(Alumno $alumno, int $periodoId, string $corte = 'anual'): array
    {
        $filas = $this->notasPorClase($periodoId, $corte)
            ->filter(fn (array $fila) => $fila['alumno']->id === $alumno->id)
            ->values();

        $notas = $filas->pluck('nota')->filter(fn ($n) => $n !== null);
        $promedio = $notas->isNotEmpty() ? round((float) $notas->avg(), 2) : null;
        $reprobadas = $notas->filter(fn ($n) => $n < self::NOTA_MINIMA)->count();

        $matricula = Matricula::with(['curso', 'paralelo'])
            ->where('alumno_id', $alumno->id)
            ->where('periodo_id', $periodoId)
            ->first();

        $asistencia = $this->asistenciaDetalleAlumno($periodoId, $alumno->id);

        $nee = DeceNee::activos()
            ->where('alumno_id', $alumno->id)
            ->first();

        $observaciones = Observacion::with('tipoObservacion')
            ->where('alumno_id', $alumno->id)
            ->latest()
            ->take(5)
            ->get();

        return [
            'alumno' => $alumno,
            'matricula' => $matricula,
            'filas' => $filas,
            'promedio' => $promedio,
            'reprobadas' => $reprobadas,
            'materias' => $notas->count(),
            'asistencia' => $asistencia,
            'nee' => $nee,
            'observaciones' => $observaciones,
        ];
    }

    /**
     * Porcentaje de asistencia efectiva por estudiante en el periodo.
     * Efectiva = presente + justificada + atraso.
     *
     * @return Collection<int, float>
     */
    public function asistenciaPorAlumno(int $periodoId): Collection
    {
        return $this->asistenciasDelPeriodo($periodoId)
            ->groupBy('alumno_id')
            ->map(function (Collection $registros) {
                $total = $registros->count();

                if ($total === 0) {
                    return null;
                }

                $efectivas = $registros->whereIn('estado', ['presente', 'justificada', 'atraso'])->count();

                return round($efectivas / $total * 100, 2);
            })
            ->filter(fn ($valor) => $valor !== null);
    }

    /** Promedio institucional de asistencia efectiva del periodo. */
    public function asistenciaPromedio(int $periodoId): ?float
    {
        $registros = $this->asistenciasDelPeriodo($periodoId);

        if ($registros->isEmpty()) {
            return null;
        }

        $efectivas = $registros->whereIn('estado', ['presente', 'justificada', 'atraso'])->count();

        return round($efectivas / $registros->count() * 100, 2);
    }

    /**
     * Detalle de asistencia de un estudiante o del periodo completo.
     *
     * @return array{presente: int, ausente: int, atraso: int, justificada: int, total: int, porcentaje: ?float}
     */
    public function asistenciaDetalleAlumno(int $periodoId, ?int $alumnoId = null): array
    {
        $registros = $this->asistenciasDelPeriodo($periodoId)
            ->when($alumnoId, fn (Collection $c) => $c->where('alumno_id', $alumnoId));

        $total = $registros->count();
        $efectivas = $registros->whereIn('estado', ['presente', 'justificada', 'atraso'])->count();

        return [
            'presente' => $registros->where('estado', 'presente')->count(),
            'ausente' => $registros->where('estado', 'ausente')->count(),
            'atraso' => $registros->where('estado', 'atraso')->count(),
            'justificada' => $registros->where('estado', 'justificada')->count(),
            'total' => $total,
            'porcentaje' => $total > 0 ? round($efectivas / $total * 100, 2) : null,
        ];
    }

    /** Registros de asistencia dentro del rango del periodo. */
    private function asistenciasDelPeriodo(int $periodoId): Collection
    {
        $periodo = PeriodoLectivo::find($periodoId);

        return Asistencia::query()
            ->when($periodo, fn ($q) => $q->whereBetween('fecha', [
                $periodo->fecha_inicio,
                $periodo->fecha_fin,
            ]))
            ->get();
    }

    /** Parcial numérico cuando el corte es p1..p6. */
    private function parcialDelCorte(string $corte): ?int
    {
        if (preg_match('/^p([1-6])$/', $corte, $coincidencias)) {
            return (int) $coincidencias[1];
        }

        return null;
    }

    /** Nota de un parcial; los exámenes quimestrales usan nota_examen. */
    private function notaDeParcial(?Nota $registro, int $parcial, string $sistema): ?float
    {
        if ($sistema === 'quimestre' && in_array($parcial, [3, 6], true)) {
            return $registro?->nota_examen !== null ? (float) $registro->nota_examen : null;
        }

        return $this->calculo->notaParcialDe($registro);
    }

    /** Etiqueta usada para agrupar una clase. */
    private function etiquetaDe(ClaseDocente $clase, string $llave): string
    {
        return match ($llave) {
            'paralelo' => $clase->paralelo?->nombre ?? 'Sin paralelo',
            'materia' => $clase->materia?->nombre ?? 'Sin asignatura',
            'curso' => trim(implode(' ', array_filter([
                $clase->curso?->nombre,
                $clase->paralelo?->nombre,
            ]))) ?: 'Sin curso',
            default => $clase->curso?->nombre ?? 'Sin nivel',
        };
    }

    /** Fila uniforme de nota por estudiante y clase. */
    private function fila(ClaseDocente $clase, Alumno $alumno, ?Matricula $matricula, ?float $nota): array
    {
        $nota = $nota === null ? null : round($nota, 2);

        return [
            'clase' => $clase,
            'alumno' => $alumno,
            'matricula' => $matricula,
            'nota' => $nota,
            'aprobado' => $nota === null ? null : $nota >= self::NOTA_MINIMA,
        ];
    }
}
