<?php

namespace App\Http\Controllers\Dece;

use App\Http\Controllers\Controller;
use App\Http\Resources\DashboardResource;
use App\Models\DeceAcuerdo;
use App\Models\DeceAtencion;
use App\Models\DeceCita;
use App\Services\Dece\AlertasDece;
use App\Services\Dece\EstadisticasDece;
use Illuminate\Http\Request;

/**
 * Panel de inicio del DECE: indicadores, proximas citas, ultimas atenciones y
 * alertas de focos rojos. Rectorado y Vicerrectorado ven el mismo resumen
 * agregado, sin relatorias.
 */
class DashboardController extends Controller
{
    public function __construct(
        private readonly EstadisticasDece $estadisticas,
        private readonly AlertasDece $alertas
    ) {}

    public function index(Request $request)
    {
        DeceAcuerdo::marcarVencidos();

        $stats = $this->estadisticas->resumen();

        $proximas = DeceCita::with(['alumno', 'representante', 'profesional'])
            ->where('estado', DeceCita::ESTADO_PROGRAMADA)
            ->where('fecha_hora', '>=', now())
            ->orderBy('fecha_hora')
            ->limit(8)
            ->get();

        $ultimas = DeceAtencion::with(['alumno', 'profesional'])
            ->latest('fecha_atencion')
            ->limit(6)
            ->get();

        $alertas = $this->alertas->focosRojos();

        $data = (new DashboardResource($request->user()))->resolve($request);

        return view('dece.dashboard', [
            'stats' => $stats,
            'proximas' => $proximas,
            'ultimas' => $ultimas,
            'alertas' => $alertas,
            'circulares' => $data['circulares'] ?? [],
            'notificaciones' => $data['usuario']['notificaciones_pendientes'] ?? [],
        ]);
    }
}
