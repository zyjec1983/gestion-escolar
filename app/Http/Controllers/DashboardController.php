<?php

namespace App\Http\Controllers;

use App\Http\Resources\DashboardResource;
use App\Models\Alumno;
use App\Models\Circular;
use App\Models\Cita;
use App\Models\Curso;
use App\Models\Matricula;
use App\Models\Observacion;
use App\Models\PeriodoLectivo;
use App\Models\PreRegistro;
use App\Models\User;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        return match ($user->rol) {
            'ADMIN' => $this->rector($request),
            'SECRETARIA' => $this->secretaria($request),
            'REPRESENTANTE' => $this->representante($request),
            'ESTUDIANTE' => $this->estudiante($request),
            'INSPECCION' => $this->inspector($request),
            default => $this->general($request),
        };
    }

    /**
     * Panel de Rectorado / Vicerrectorado / Administracion.
     * Tarjetas de resumen institucional + circulares publicadas.
     */
    private function rector(Request $request)
    {
        $user = $request->user();
        $data = (new DashboardResource($user))->resolve($request);

        $periodoActivo = PeriodoLectivo::where('activo', true)->first();

        $data['kpis'] = [
            'estudiantes' => $periodoActivo
                ? Matricula::where('periodo_id', $periodoActivo->id)->count()
                : Alumno::count(),
            'docentes' => User::where('rol', 'DOCENTE')->count(),
            'pendientes' => PreRegistro::pendiente()->count(),
            'cursos' => Curso::count(),
        ];

        $data['pendientes_recientes'] = PreRegistro::pendiente()
            ->latest('created_at')
            ->take(6)
            ->get();

        return view('dashboard.rector', $data);
    }

    /**
     * Panel operativo de Secretaria: admisiones, fichas por regularizar, citas
     * y observaciones en modo lectura. Es distinto del panel de Rectorado.
     */
    private function secretaria(Request $request)
    {
        $user = $request->user();
        $data = (new DashboardResource($user))->resolve($request);

        $periodoActivo = PeriodoLectivo::where('activo', true)->first();

        $data['kpis'] = [
            'matriculados' => $periodoActivo
                ? Matricula::where('periodo_id', $periodoActivo->id)->count()
                : Matricula::count(),
            'solicitudes' => PreRegistro::pendiente()->count(),
            'fichas' => Alumno::representantePorRegularizar()->count(),
            'citas' => Cita::pendientes()->count(),
            'docentes' => User::where('rol', 'DOCENTE')->count(),
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

        return view('dashboard.index', $data);
    }

    private function inspector(Request $request)
    {
        $user = $request->user();
        $data = (new DashboardResource($user))->resolve($request);

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
