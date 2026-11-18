<?php

namespace App\Services;

use App\Models\ClaseDocente;
use App\Models\DeceNee;
use App\Models\Unidad;
use App\Models\User;
use App\Models\VentanaNota;

/**
 * Indicadores del panel académico del Vicerrectorado.
 *
 * Qué hace: concentra el estado de la planificación, las calificaciones, la
 * asistencia y los estudiantes en riesgo para que el vicerrector coordine la
 * academia sin duplicar los módulos operativos.
 * Conecta con: IndicadoresInstitucionales, RendimientoAcademico, Unidad,
 * ClaseDocente, Nota, VentanaNota y DeceNee.
 * Rutas: GET /vicerrector (vicerrector.panel).
 */
class IndicadoresAcademicos
{
    public function __construct(
        private RendimientoAcademico $rendimiento,
        private IndicadoresInstitucionales $institucionales
    ) {}

    /**
     * Tarjetas del panel académico.
     *
     * @return array<string, array{etiqueta: string, valor: string|int, detalle: ?string, icono: string, color: string, url: ?string}>
     */
    public function kpis(): array
    {
        $periodo = $this->rendimiento->periodo();
        $periodoId = $periodo?->id;

        $docentes = User::docentes()->count();
        $clases = ClaseDocente::count();
        $planificaciones = $this->institucionales->planificaciones($periodoId);
        $pendientes = $this->institucionales->calificacionesPendientes();

        $asistencia = $periodoId ? $this->rendimiento->asistenciaPromedio($periodoId) : null;

        $filas = $periodoId ? $this->rendimiento->notasPorClase($periodoId) : collect();
        $promedio = $periodoId ? $this->rendimiento->promedioGeneral($filas) : null;

        $riesgo = $periodoId
            ? $this->rendimiento->estudiantesEnRiesgo($periodoId)
            : collect();

        $nee = DeceNee::activos()->count();

        return [
            'docentes' => $this->kpi(
                'Docentes',
                number_format($docentes, 0, ',', '.'),
                $clases . ' clase(s) asignadas',
                'bi-person-badge-fill',
                'success',
                null
            ),
            'planificaciones' => $this->kpi(
                'Planificaciones entregadas',
                number_format($planificaciones['total'], 0, ',', '.'),
                'Vigentes: ' . $planificaciones['vigentes']
                    . ' · Atrasadas: ' . $planificaciones['atrasadas'],
                'bi-journal-bookmark-fill',
                'primary',
                route('planificaciones.index')
            ),
            'calificaciones' => $this->kpi(
                'Calificaciones pendientes',
                number_format($pendientes, 0, ',', '.'),
                'Clases sin notas del parcial en curso',
                'bi-clipboard2-x',
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
                route('vicerrector.rendimiento')
            ),
            'riesgo' => $this->kpi(
                'Estudiantes en riesgo',
                number_format($riesgo->count(), 0, ',', '.'),
                'Rojo: ' . $riesgo->where('nivel', 'rojo')->count()
                    . ' · Amarillo: ' . $riesgo->where('nivel', 'amarillo')->count(),
                'bi-person-exclamation',
                'danger',
                route('vicerrector.estudiantes-riesgo')
            ),
            'nee' => $this->kpi(
                'NEE activas',
                number_format($nee, 0, ',', '.'),
                'Registros del DECE vigentes',
                'bi-universal-access',
                'secondary',
                route('dece.expedientes.index')
            ),
        ];
    }

    /**
     * Alertas académicas ordenadas por severidad.
     *
     * @return array<int, array{titulo: string, detalle: string, icono: string, severidad: string, url: ?string}>
     */
    public function alertas(): array
    {
        $periodo = $this->rendimiento->periodo();
        $periodoId = $periodo?->id;
        $alertas = [];

        $atrasadas = $this->planificacionesAtrasadasPorDocente($periodoId);

        if ($atrasadas->isNotEmpty()) {
            $alertas[] = $this->alerta(
                'Docentes con planificación atrasada',
                $atrasadas->count() . ' docente(s): ' . $atrasadas->take(3)->implode(', ')
                    . ($atrasadas->count() > 3 ? '…' : ''),
                'bi-journal-x',
                'danger',
                route('planificaciones.index')
            );
        }

        $pendientes = $this->institucionales->calificacionesPendientes();

        if ($pendientes > 0) {
            $alertas[] = $this->alerta(
                'Clases sin calificaciones',
                $pendientes . ' clase(s) sin notas del parcial en curso.',
                'bi-clipboard2-x',
                'warning',
                null
            );
        }

        if ($periodoId) {
            $riesgo = $this->rendimiento->estudiantesEnRiesgo($periodoId);
            $rojos = $riesgo->where('nivel', 'rojo');

            if ($rojos->isNotEmpty()) {
                $alertas[] = $this->alerta(
                    'Estudiantes con bajo rendimiento',
                    $rojos->count() . ' estudiante(s) en rojo por promedio, materias o asistencia.',
                    'bi-person-exclamation',
                    'danger',
                    route('vicerrector.estudiantes-riesgo')
                );
            }

            $sinAdaptacion = $riesgo->filter(fn (array $fila) => $fila['nee'] !== null);

            if ($sinAdaptacion->isNotEmpty()) {
                $alertas[] = $this->alerta(
                    'NEE sin adaptación registrada',
                    $sinAdaptacion->count() . ' estudiante(s) requieren revisión de adaptación.',
                    'bi-universal-access',
                    'info',
                    route('dece.expedientes.index')
                );
            }
        }

        $porCerrar = VentanaNota::where('activo', true)
            ->whereBetween('fecha_fin', [now(), now()->addDays(3)])
            ->count();

        if ($porCerrar > 0) {
            $alertas[] = $this->alerta(
                'Ventanas de notas por cerrar',
                $porCerrar . ' ventana(s) cierran en los próximos 3 días.',
                'bi-calendar-range',
                'warning',
                null
            );
        }

        return $alertas;
    }

    /** Docentes con al menos una planificación vencida. */
    private function planificacionesAtrasadasPorDocente(?int $periodoId): \Illuminate\Support\Collection
    {
        $hoy = now()->startOfDay();

        return Unidad::with(['claseDocente.docente', 'creador'])
            ->when($periodoId, fn ($q) => $q->where(function ($sub) use ($periodoId) {
                $sub->where('periodo_lectivo_id', $periodoId)->orWhereNull('periodo_lectivo_id');
            }))
            ->whereNotNull('fecha_fin')
            ->where('fecha_fin', '<', $hoy)
            ->get()
            ->map(fn (Unidad $unidad) => $unidad->docenteTitular())
            ->filter()
            ->map(fn (User $docente) => trim($docente->apellidos . ' ' . $docente->name))
            ->unique()
            ->values();
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
