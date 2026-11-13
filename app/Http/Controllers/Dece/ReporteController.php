<?php

namespace App\Http\Controllers\Dece;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Dece\Concerns\AutorizaDece;
use App\Models\Curso;
use App\Models\DeceAtencion;
use App\Models\DeceCita;
use App\Models\DeceDerivacion;
use App\Models\DeceNee;
use App\Models\DeceTaller;
use App\Models\Institucion;
use App\Services\Dece\EstadisticasDece;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * Reportes agregados para direccion. Nunca incluye relatorias ni datos
 * clinicos individuales: solo conteos, motivos y cursos.
 */
class ReporteController extends Controller
{
    use AutorizaDece;

    public function __construct(private readonly EstadisticasDece $estadisticas) {}

    public function index(Request $request)
    {
        $datos = $this->datos($request);

        return view('dece.reportes.index', $datos);
    }

    public function pdf(Request $request)
    {
        $datos = $this->datos($request);

        $pdf = Pdf::loadView('dece.pdf.reporte-general', $datos + [
            'institucion' => Institucion::first(),
            'fechaEmision' => now(),
        ])->setPaper('a4', 'portrait');

        return $pdf->download('reporte-dece-'.$datos['desde']->format('Ymd').'-'.$datos['hasta']->format('Ymd').'.pdf');
    }

    /**
     * @return array<string, mixed>
     */
    private function datos(Request $request): array
    {
        $filtros = $request->validate([
            'desde' => ['nullable', 'date'],
            'hasta' => ['nullable', 'date', 'after_or_equal:desde'],
            'curso_id' => ['nullable', 'integer', 'exists:cursos,id'],
        ]);

        $desde = isset($filtros['desde'])
            ? Carbon::parse($filtros['desde'])->startOfDay()
            : now()->startOfMonth();

        $hasta = isset($filtros['hasta'])
            ? Carbon::parse($filtros['hasta'])->endOfDay()
            : now()->endOfDay();

        $cursoId = $filtros['curso_id'] ?? null;

        $stats = $this->estadisticas->resumen($desde, $hasta);

        $atenciones = DeceAtencion::with(['alumno.matriculas.curso'])
            ->whereBetween('fecha_atencion', [$desde, $hasta])
            ->get();

        $atencionesPorCurso = $atenciones
            ->groupBy(function (DeceAtencion $atencion) {
                $matricula = $atencion->alumno?->matriculas->sortByDesc('id')->first();

                return $matricula?->curso?->nombre ?? 'Sin curso';
            })
            ->map(fn ($grupo) => $grupo->count())
            ->sortDesc()
            ->take(10);

        $citas = DeceCita::whereBetween('fecha_hora', [$desde, $hasta])
            ->when($cursoId, fn ($query, int $id) => $query->whereHas(
                'alumno.matriculas',
                fn ($matricula) => $matricula->where('curso_id', $id)
            ))
            ->get();

        $citasPorEstado = collect(DeceCita::ESTADOS)
            ->map(fn (string $label, string $estado) => $citas->where('estado', $estado)->count());

        $derivaciones = DeceDerivacion::whereBetween('fecha_derivacion', [$desde->toDateString(), $hasta->toDateString()])->get();

        $derivacionesPorTipo = collect(DeceDerivacion::TIPOS)
            ->map(fn (string $label, string $tipo) => $derivaciones->where('tipo', $tipo)->count());

        $nee = DeceNee::with('diagnostico')
            ->where('activo', true)
            ->get();

        $neePorCategoria = $nee
            ->groupBy(fn (DeceNee $registro) => $registro->diagnostico?->categoria ?? 'Sin diagnóstico')
            ->map(fn ($grupo) => $grupo->count())
            ->sortDesc();

        $talleres = DeceTaller::with('curso')
            ->withCount('asistentes')
            ->whereBetween('fecha', [$desde->toDateString(), $hasta->toDateString()])
            ->orderBy('fecha')
            ->get();

        $cursos = Curso::enOrdenEscolar()->get(['id', 'nombre']);

        return compact(
            'desde',
            'hasta',
            'cursoId',
            'filtros',
            'stats',
            'atencionesPorCurso',
            'citasPorEstado',
            'derivacionesPorTipo',
            'neePorCategoria',
            'talleres',
            'cursos'
        );
    }
}
