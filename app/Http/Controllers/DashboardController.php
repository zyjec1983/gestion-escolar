<?php

namespace App\Http\Controllers;

use App\Http\Resources\DashboardResource;
use App\Models\Alumno;
use App\Models\Circular;
use App\Models\Cita;
use App\Models\ComunidadMiembro;
use App\Models\Curso;
use App\Models\ExpedienteTraslado;
use App\Models\HistorialCambio;
use App\Models\Matricula;
use App\Models\Observacion;
use App\Models\PeriodoLectivo;
use App\Models\PreRegistro;
use App\Models\User;
use App\Models\VentanaNota;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        return match ($user->perfilActivo()) {
            // Cada autoridad tiene su panel propio: Rectorado dirige la
            // institución y Vicerrectorado coordina la academia.
            'ADMIN' => $this->admin($request),
            'RECTORA' => redirect()->route('rector.panel'),
            'VICERRECTORA' => redirect()->route('vicerrector.panel'),
            'SECRETARIA' => $this->secretaria($request),
            'REPRESENTANTE' => $this->representante($request),
            'ESTUDIANTE' => $this->estudiante($request),
            'INSPECCION' => $this->inspector($request),
            'DECE' => $this->dece($request),
            default => $this->general($request),
        };
    }

    /**
     * Panel exclusivo del rol ADMIN.
     *
     * Qué hace: reúne los accesos a los módulos administrables (sitio web,
     * comunidad, carruseles, circulares, observaciones, fechas de notas,
     * usuarios e historial) y muestra contadores y cambios recientes.
     * Conecta con: DashboardResource, HistorialCambio, Circular y los modelos
     * de los módulos administrables.
     * Ruta: GET /dashboard (dashboard).
     */
    private function admin(Request $request)
    {
        $data = (new DashboardResource($request->user()))->resolve($request);

        // DashboardResource entrega `institucion` como arreglo para la API.
        // En las vistas se quita para no tapar el modelo $institucion que
        // comparte AppServiceProvider con el layout (nombre, subtítulo y logo).
        unset($data['institucion']);

        $periodoActivo = PeriodoLectivo::where('activo', true)->first();

        $data['panel_titulo'] = 'Panel de Administración';
        $data['kpis'] = [
            'estudiantes' => $periodoActivo
                ? Matricula::where('periodo_id', $periodoActivo->id)->count()
                : Alumno::count(),
            'usuarios' => User::count(),
            'circulares' => Circular::count(),
            'comunidad' => ComunidadMiembro::where('activo', true)->count(),
            'observaciones' => Observacion::count(),
            'ventanas' => VentanaNota::where('activo', true)->count(),
        ];

        $data['cambios_recientes'] = HistorialCambio::with('usuario')
            ->latest()
            ->take(8)
            ->get();

        $data['circulares_recientes'] = Circular::orderByDesc('fecha_publicacion')
            ->orderByDesc('id')
            ->take(5)
            ->get();

        return view('dashboard.admin', $data);
    }

    /**
     * Panel operativo de Secretaria: admisiones, fichas por regularizar, citas
     * y observaciones en modo lectura. Es distinto del panel de Rectorado.
     */
    private function secretaria(Request $request)
    {
        $user = $request->user();
        $data = (new DashboardResource($user))->resolve($request);
        unset($data['institucion']);

        $periodoActivo = PeriodoLectivo::where('activo', true)->first();

        // Fichas por regularizar: estudiantes cuyo representante aun no cumple
        // la regla de mayoria de edad y estudiantes con un expediente de
        // traslado pendiente. Se unifican por alumno_id para no contar dos veces
        // a quien aparece en ambos casos.
        $fichasPorRepresentante = Alumno::representantePorRegularizar()->pluck('id');
        $fichasPorTraslado = ExpedienteTraslado::porRegularizar()->pluck('alumno_id');
        $fichasPorRegularizar = $fichasPorRepresentante
            ->merge($fichasPorTraslado)
            ->filter()
            ->unique()
            ->count();

        $data['kpis'] = [
            'matriculados' => $periodoActivo
                ? Matricula::where('periodo_id', $periodoActivo->id)->count()
                : Matricula::count(),
            'solicitudes' => PreRegistro::pendiente()->count(),
            'fichas' => $fichasPorRegularizar,
            'citas' => Cita::pendientes()->count(),
            'docentes' => User::docentes()->count(),
            'circulares' => Circular::count(),
        ];

        $data['solicitudes_recientes'] = PreRegistro::with(['curso', 'paralelo'])
            ->pendiente()
            ->latest()
            ->take(5)
            ->get();

        $data['citas_proximas'] = Cita::with(['alumno', 'docente'])
            ->pendientes()
            ->orderBy('fecha_inicio')
            ->take(5)
            ->get();

        $data['observaciones_recientes'] = Observacion::with(['alumno', 'tipoObservacion'])
            ->latest()
            ->take(5)
            ->get();

        return view('dashboard.secretaria', $data);
    }

    private function general(Request $request)
    {
        $data = (new DashboardResource($request->user()))->resolve($request);
        unset($data['institucion']);

        return view('dashboard.index', $data);
    }

    /**
     * El rol DECE comparte el panel institucional con el modulo de consejeria:
     * el mismo panel de /dece se muestra en /dashboard.
     */
    private function dece(Request $request)
    {
        return app(Dece\DashboardController::class)->index($request);
    }

    private function inspector(Request $request)
    {
        $user = $request->user();
        $data = (new DashboardResource($user))->resolve($request);
        unset($data['institucion']);

        // Mensajes sin leer cuyo remitente es un representante.
        $data['mensajes_representantes'] = $user->mensajesRecibidos()
            ->where('leido', false)
            ->whereHasMorph('emisor', [User::class], fn ($q) => $q->where('rol', 'REPRESENTANTE'))
            ->count();

        // Citas del dia donde el inspector es quien cita o quien las solicito.
        $data['citas_hoy'] = Cita::with(['alumno.representante', 'docente'])
            ->whereDate('fecha_inicio', now()->toDateString())
            ->where(function ($q) use ($user) {
                $q->where('docente_id', $user->id)
                    ->orWhere(fn ($qq) => $qq->where('solicitante_type', User::class)
                        ->where('solicitante_id', $user->id));
            })
            ->orderBy('fecha_inicio')
            ->get();

        return view('dashboard.inspector', $data);
    }

    private function representante(Request $request)
    {
        return app(RepresentanteController::class)->dashboard($request);
    }

    private function estudiante(Request $request)
    {
        return app(EstudianteController::class)->dashboard($request);
    }
}
