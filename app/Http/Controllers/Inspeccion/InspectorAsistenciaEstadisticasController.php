<?php

namespace App\Http\Controllers\Inspeccion;

use App\Http\Controllers\Controller;
use App\Models\Alumno;
use App\Models\Asistencia;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;

class InspectorAsistenciaEstadisticasController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('auth'),
            new Middleware('role:INSPECCION,RECTORA,VICERRECTORA,DECE,SECRETARIA'),
        ];
    }

    public function index(Request $request)
    {
        $aulas = InspectorAsistenciaController::aulasDisponibles();
        $primera = $aulas->first();

        $cursoId = (int) $request->query('curso_id', $primera->curso_id ?? 0);
        $paraleloId = (int) $request->query('paralelo_id', $primera->paralelo_id ?? 0);

        $aula = $aulas->first(fn ($a) => $a->curso_id == $cursoId && $a->paralelo_id == $paraleloId)
            ?? $primera;

        $fechaDesde = $this->parseFecha($request->query('fecha_desde'));
        $fechaHasta = $this->parseFecha($request->query('fecha_hasta'));

        $alumnos = collect();
        $conteos = collect();
        $totales = [
            'presente' => 0,
            'justificada' => 0,
            'injustificada' => 0,
            'atraso' => 0,
            'faltas' => 0,
            'total' => 0,
        ];

        if ($aula) {
            $alumnos = Alumno::whereHas('matriculas', function ($q) use ($aula) {
                $q->where('curso_id', $aula->curso_id)
                    ->where('paralelo_id', $aula->paralelo_id);
            })->orderBy('apellidos')->orderBy('nombres')->get();

            $filas = Asistencia::where('curso_id', $aula->curso_id)
                ->where('paralelo_id', $aula->paralelo_id)
                ->when($fechaDesde, fn ($q) => $q->whereDate('fecha', '>=', $fechaDesde))
                ->when($fechaHasta, fn ($q) => $q->whereDate('fecha', '<=', $fechaHasta))
                ->select('alumno_id', 'estado', DB::raw('COUNT(*) as total'))
                ->groupBy('alumno_id', 'estado')
                ->get();

            $porAlumno = $filas->groupBy('alumno_id');

            foreach ($alumnos as $alumno) {
                $conteo = $porAlumno->get($alumno->id, collect());
                $presente = (int) ($conteo->firstWhere('estado', 'presente')?->total ?? 0);
                $justificada = (int) ($conteo->firstWhere('estado', 'justificada')?->total ?? 0);
                $injustificada = (int) ($conteo->firstWhere('estado', 'injustificada')?->total ?? 0);
                $atraso = (int) ($conteo->firstWhere('estado', 'atraso')?->total ?? 0);
                $faltas = $justificada + $injustificada;
                $total = $presente + $faltas + $atraso;

                $conteos->put($alumno->id, [
                    'presente' => $presente,
                    'justificada' => $justificada,
                    'injustificada' => $injustificada,
                    'atraso' => $atraso,
                    'faltas' => $faltas,
                    'total' => $total,
                    'porcentaje' => $total > 0 ? round(($presente / $total) * 100, 1) : null,
                ]);

                $totales['presente'] += $presente;
                $totales['justificada'] += $justificada;
                $totales['injustificada'] += $injustificada;
                $totales['atraso'] += $atraso;
                $totales['faltas'] += $faltas;
                $totales['total'] += $total;
            }
        }

        $porcentajeGeneral = $totales['total'] > 0
            ? round(($totales['presente'] / $totales['total']) * 100, 1)
            : null;

        return view('inspeccion.asistencia.estadisticas', [
            'aulas' => $aulas,
            'aula' => $aula,
            'fechaDesde' => $fechaDesde,
            'fechaHasta' => $fechaHasta,
            'alumnos' => $alumnos,
            'conteos' => $conteos,
            'totales' => $totales,
            'porcentajeGeneral' => $porcentajeGeneral,
        ]);
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
