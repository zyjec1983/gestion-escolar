<?php

namespace App\Http\Controllers\Inspeccion;

use App\Http\Controllers\Controller;
use App\Models\Curso;
use App\Models\Observacion;
use App\Models\Paralelo;
use App\Models\TipoObservacion;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class ObservacionEstadisticasController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('auth'),
            new Middleware('role:INSPECCION,RECTORA,VICERRECTORA,DECE'),
        ];
    }

    public function index(Request $request)
    {
        $niveles = Curso::orderBy('nombre')->get(['id', 'nombre']);
        $nivelId = (int) $request->query('nivel') ?: null;
        $paraleloId = (int) $request->query('paralelo') ?: null;
        $fechaDesde = $this->parseFecha($request->query('fecha_desde'));
        $fechaHasta = $this->parseFecha($request->query('fecha_hasta'));

        $paralelos = $nivelId
            ? Paralelo::whereHas('matriculas', fn ($q) => $q->where('curso_id', $nivelId))
                ->orderBy('nombre')->get(['id', 'nombre'])
            : Paralelo::orderBy('nombre')->get(['id', 'nombre']);

        $base = fn () => Observacion::query()
            ->whereHas('alumno.matriculas', fn ($q) => $q
                ->when($nivelId, fn ($qq) => $qq->where('curso_id', $nivelId))
                ->when($paraleloId, fn ($qq) => $qq->where('paralelo_id', $paraleloId)))
            ->when($fechaDesde, fn ($q) => $q->whereDate('created_at', '>=', $fechaDesde))
            ->when($fechaHasta, fn ($q) => $q->whereDate('created_at', '<=', $fechaHasta));

        $totalObservaciones = (clone $base())->count();

        $porTipo = (clone $base())
            ->join('tipos_observacion', 'tipos_observacion.id', '=', 'observaciones.tipo_observacion_id')
            ->selectRaw('tipos_observacion.nombre as tipo_nombre, tipos_observacion.clasificacion, count(*) as total')
            ->groupBy('tipos_observacion.id', 'tipos_observacion.nombre', 'tipos_observacion.clasificacion')
            ->orderByDesc('total')
            ->get();

        $tiposActivos = TipoObservacion::activos()->orderBy('nombre')->get(['id', 'nombre', 'clasificacion']);

        $porDocente = (clone $base())
            ->join('users', 'users.id', '=', 'observaciones.user_id')
            ->selectRaw('users.id as docente_id, users.name, users.apellidos, count(*) as total')
            ->groupBy('users.id', 'users.name', 'users.apellidos')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        $colores = [
            'positiva' => '#198754',
            'leve' => '#ffc107',
            'grave' => '#fd7e14',
            'muy_grave' => '#dc3545',
        ];

        return view('inspeccion.observaciones.estadisticas', [
            'niveles' => $niveles,
            'paralelos' => $paralelos,
            'nivelId' => $nivelId,
            'paraleloId' => $paraleloId,
            'fechaDesde' => $fechaDesde,
            'fechaHasta' => $fechaHasta,
            'totalObservaciones' => $totalObservaciones,
            'porTipo' => $porTipo,
            'tiposActivos' => $tiposActivos,
            'porDocente' => $porDocente,
            'colores' => $colores,
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
