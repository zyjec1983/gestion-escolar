<?php

namespace App\Services;

use App\Models\Cita;
use App\Models\ClaseDocente;
use App\Models\Curso;
use App\Models\DeceAtencion;
use App\Models\Matricula;
use App\Models\Nota;
use App\Models\Observacion;
use App\Models\Paralelo;
use App\Models\PreRegistro;
use App\Models\Unidad;
use App\Models\User;
use App\Models\VentanaNota;

/**
 * Indicadores del panel institucional del Rectorado.
 *
 * Qué hace: reúne en un solo lugar los contadores y alertas que el rector
 * necesita para dirigir la institución. No modifica datos: solo consulta los
 * módulos existentes.
 * Conecta con: Matricula, PreRegistro, User, Curso, Paralelo, Asistencia,
 * Nota, DeceAtencion, Unidad, VentanaNota y Cita.
 * Rutas: GET /rector (rector.panel).
 */
class IndicadoresInstitucionales
{
    public function __construct(private RendimientoAcademico $rendimiento) {}

    /**
     * Tarjetas del panel institucional.
     *
     * @return array<string, array{etiqueta: string, valor: string|int, detalle: ?string, icono: string, color: string, url: ?string}>
     */
    public function kpis(): array
    {
        $periodo = $this->rendimiento->periodo();
        $periodoId = $periodo?->id;

        $matriculas = Matricula::query()
            ->when($periodoId, fn ($q) => $q->where('periodo_id', $periodoId));

        $totalMatriculas = (clone $matriculas)->count();
        $definitivas = (clone $matriculas)->where('estado', Matricula::ESTADO_DEFINITIVA)->count();
        $provisionales = (clone $matriculas)->where('estado', Matricula::ESTADO_PROVISIONAL)->count();

        $docentes = User::docentes()->count();
        $cursos = Curso::count();
        $paralelos = Paralelo::count();
        $solicitudes = PreRegistro::pendiente()->count();

        $asistencia = $periodoId ? $this->rendimiento->asistenciaPromedio($periodoId) : null;
        $promedio = $periodoId
            ? $this->rendimiento->promedioGeneral($this->rendimiento->notasPorClase($periodoId))
            : null;

        $casosDece = DeceAtencion::where('estado', DeceAtencion::ESTADO_ABIERTA)->count();
        $ventanas = VentanaNota::where('activo', true)->count();
        $planificaciones = $this->planificaciones($periodoId);

        return [
            'matriculas' => $this->kpi(
                'Matrícula total',
                number_format($totalMatriculas, 0, ',', '.'),
                'Definitivas: ' . number_format($definitivas, 0, ',', '.')
                    . ' · Provisionales: ' . number_format($provisionales, 0, ',', '.'),
                'bi-people-fill',
                'primary',
                route('rector.rendimiento')
            ),
            'docentes' => $this->kpi(
                'Docentes',
                number_format($docentes, 0, ',', '.'),
                'Personal con carga o rol docente',
                'bi-person-badge-fill',
                'success',
                null
            ),
            'cursos' => $this->kpi(
                'Cursos y paralelos',
                $cursos . ' / ' . $paralelos,
                'Oferta académica registrada',
                'bi-building-fill',
                'dark',
                null
            ),
            'solicitudes' => $this->kpi(
                'Solicitudes pendientes',
                number_format($solicitudes, 0, ',', '.'),
                'Pre-registros por revisar',
                'bi-hourglass-split',
                'warning',
                null
            ),
            'asistencia' => $this->kpi(
                'Asistencia del periodo',
                $asistencia === null ? 'Sin registros' : number_format($asistencia, 1, ',', '.') . ' %',
                'Asistencia efectiva de estudiantes',
                'bi-calendar-check-fill',
                'info',
                null
            ),
            'promedio' => $this->kpi(
                'Promedio institucional',
                $promedio === null ? 'Sin notas' : number_format($promedio, 2, ',', '.') . ' / 10',
                'Notas finales del periodo',
                'bi-graph-up-arrow',
                'primary',
                route('rector.rendimiento')
            ),
            'dece' => $this->kpi(
                'Casos DECE abiertos',
                number_format($casosDece, 0, ',', '.'),
                'Atenciones sin cerrar',
                'bi-heart-pulse-fill',
                'danger',
                route('dece.reportes.index')
            ),
            'planificaciones' => $this->kpi(
                'Planificaciones vigentes',
                number_format($planificaciones['vigentes'], 0, ',', '.'),
                'Atrasadas: ' . number_format($planificaciones['atrasadas'], 0, ',', '.'),
                'bi-journal-bookmark-fill',
                'secondary',
                route('planificaciones.index')
            ),
            'ventanas' => $this->kpi(
                'Ventanas de notas activas',
                number_format($ventanas, 0, ',', '.'),
                'Carga de calificaciones habilitada',
                'bi-calendar-range-fill',
                'success',
                null
            ),
        ];
    }

    /**
     * Alertas institucionales ordenadas por severidad.
     *
     * @return array<int, array{titulo: string, detalle: string, icono: string, severidad: string, url: ?string}>
     */
    public function alertas(): array
    {
        $periodo = $this->rendimiento->periodo();
        $periodoId = $periodo?->id;
        $alertas = [];

        $solicitudes = PreRegistro::pendiente()->count();

        if ($solicitudes > 0) {
            $alertas[] = $this->alerta(
                'Solicitudes de matrícula pendientes',
                $solicitudes . ' pre-registro(s) esperan revisión de Secretaría.',
                'bi-hourglass-split',
                'warning',
                null
            );
        }

        $planificaciones = $this->planificaciones($periodoId);

        if ($planificaciones['atrasadas'] > 0) {
            $alertas[] = $this->alerta(
                'Planificaciones atrasadas',
                $planificaciones['atrasadas'] . ' planificación(es) con fecha de fin vencida.',
                'bi-journal-x',
                'danger',
                route('planificaciones.index')
            );
        }

        $pendientes = $this->calificacionesPendientes();

        if ($pendientes > 0) {
            $alertas[] = $this->alerta(
                'Calificaciones pendientes',
                $pendientes . ' clase(s) sin notas del parcial en curso.',
                'bi-clipboard2-x',
                'warning',
                null
            );
        }

        $casos = DeceAtencion::where('estado', DeceAtencion::ESTADO_ABIERTA)->count();

        if ($casos > 0) {
            $alertas[] = $this->alerta(
                'Casos DECE abiertos',
                $casos . ' atención(es) continúan abiertas.',
                'bi-heart-pulse',
                'info',
                route('dece.reportes.index')
            );
        }

        $citas = Cita::pendientes()->count();

        if ($citas > 0) {
            $alertas[] = $this->alerta(
                'Citas pendientes de registro',
                $citas . ' cita(s) agendadas sin registrar.',
                'bi-calendar-event',
                'info',
                route('inspeccion.citas.historial')
            );
        }

        $observaciones = Observacion::where('created_at', '>=', now()->subDays(7))->count();

        if ($observaciones > 0) {
            $alertas[] = $this->alerta(
                'Novedades de la semana',
                $observaciones . ' observación(es) registradas en los últimos 7 días.',
                'bi-exclamation-triangle',
                'warning',
                route('inspeccion.observaciones.estadisticas')
            );
        }

        return $alertas;
    }

    /** Planificaciones vigentes y atrasadas del periodo. */
    public function planificaciones(?int $periodoId): array
    {
        $hoy = now()->startOfDay();

        $unidades = Unidad::query()
            ->when($periodoId, fn ($q) => $q->where(function ($sub) use ($periodoId) {
                $sub->where('periodo_lectivo_id', $periodoId)->orWhereNull('periodo_lectivo_id');
            }))
            ->get();

        return [
            'total' => $unidades->count(),
            'vigentes' => $unidades->filter(fn (Unidad $u) => $u->fecha_inicio
                && $u->fecha_fin
                && $u->fecha_inicio->lte($hoy)
                && $u->fecha_fin->gte($hoy))->count(),
            'atrasadas' => $unidades->filter(fn (Unidad $u) => $u->fecha_fin && $u->fecha_fin->lt($hoy))->count(),
        ];
    }

    /** Clases sin ninguna nota en el parcial en curso. */
    public function calificacionesPendientes(): int
    {
        $parcial = VentanaNota::vigente()->first()?->parcial ?? 1;

        $clases = ClaseDocente::count();
        $conNotas = Nota::where('parcial', $parcial)
            ->distinct()
            ->count('clase_docente_id');

        return max(0, $clases - $conNotas);
    }

    private function kpi(
        string $etiqueta,
        string|int $valor,
        ?string $detalle,
        string $icono,
        string $color,
        ?string $url
    ): array {
        return compact('etiqueta', 'valor', 'detalle', 'icono', 'color', 'url');
    }

    private function alerta(
        string $titulo,
        string $detalle,
        string $icono,
        string $severidad,
        ?string $url
    ): array {
        return compact('titulo', 'detalle', 'icono', 'severidad', 'url');
    }
}
