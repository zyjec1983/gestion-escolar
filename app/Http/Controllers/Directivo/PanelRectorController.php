<?php

namespace App\Http\Controllers\Directivo;

use App\Http\Controllers\Controller;
use App\Http\Resources\DashboardResource;
use App\Services\IndicadoresInstitucionales;
use Illuminate\Http\Request;

/**
 * Panel institucional del Rectorado.
 *
 * Qué hace: muestra los indicadores de dirección, las alertas y los accesos
 * de supervisión. Es distinto del panel académico del Vicerrectorado.
 * Conecta con: IndicadoresInstitucionales y DashboardResource.
 * Ruta: GET /rector (rector.panel).
 */
class PanelRectorController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $data = (new DashboardResource($user))->resolve($request);

        // En la web se conserva el modelo $institucion compartido por el
        // layout; el arreglo del resource solo se usa en la API.
        unset($data['institucion']);

        $indicadores = app(IndicadoresInstitucionales::class);

        $data['panel_titulo'] = 'Panel institucional';
        $data['panel_rol'] = 'Rectorado';
        $data['prefijo'] = 'rector';
        $data['kpis'] = $indicadores->kpis();
        $data['alertas'] = $indicadores->alertas();
        $data['accesos'] = [
            ['titulo' => 'Rendimiento académico', 'icono' => 'bi-graph-up-arrow', 'url' => route('rector.rendimiento')],
            ['titulo' => 'Estudiantes en riesgo', 'icono' => 'bi-person-exclamation', 'url' => route('rector.estudiantes-riesgo')],
            ['titulo' => 'Planificación docente', 'icono' => 'bi-journal-bookmark', 'url' => route('planificaciones.index')],
            ['titulo' => 'Reportes DECE', 'icono' => 'bi-heart-pulse', 'url' => route('dece.reportes.index')],
            ['titulo' => 'Circulares', 'icono' => 'bi-megaphone', 'url' => route('admin.circulares.index')],
        ];

        return view('directivo.rector.panel', $data);
    }
}
