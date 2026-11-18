<?php

namespace App\Http\Controllers\Directivo;

use App\Http\Controllers\Controller;
use App\Http\Resources\DashboardResource;
use App\Services\IndicadoresAcademicos;
use Illuminate\Http\Request;

/**
 * Panel académico del Vicerrectorado.
 *
 * Qué hace: muestra el estado de la planificación, las calificaciones, la
 * asistencia y los estudiantes en riesgo, con accesos de coordinación
 * académica. Es distinto del panel institucional del Rectorado.
 * Conecta con: IndicadoresAcademicos y DashboardResource.
 * Ruta: GET /vicerrector (vicerrector.panel).
 */
class PanelVicerrectorController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $data = (new DashboardResource($user))->resolve($request);

        // En la web se conserva el modelo $institucion compartido por el
        // layout; el arreglo del resource solo se usa en la API.
        unset($data['institucion']);

        $indicadores = app(IndicadoresAcademicos::class);

        $data['panel_titulo'] = 'Panel académico';
        $data['panel_rol'] = 'Vicerrectorado';
        $data['prefijo'] = 'vicerrector';
        $data['kpis'] = $indicadores->kpis();
        $data['alertas'] = $indicadores->alertas();
        $data['accesos'] = [
            ['titulo' => 'Rendimiento académico', 'icono' => 'bi-graph-up-arrow', 'url' => route('vicerrector.rendimiento')],
            ['titulo' => 'Estudiantes en riesgo', 'icono' => 'bi-person-exclamation', 'url' => route('vicerrector.estudiantes-riesgo')],
            ['titulo' => 'Planificación docente', 'icono' => 'bi-journal-bookmark', 'url' => route('planificaciones.index')],
            ['titulo' => 'Expedientes DECE', 'icono' => 'bi-heart-pulse', 'url' => route('dece.expedientes.index')],
            ['titulo' => 'Circulares', 'icono' => 'bi-megaphone', 'url' => route('admin.circulares.index')],
        ];

        return view('directivo.vicerrector.panel', $data);
    }
}
