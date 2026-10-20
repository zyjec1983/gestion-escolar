<?php

namespace App\Http\Controllers;

use App\Http\Resources\DashboardResource;
use App\Models\Alumno;
use App\Models\Cita;
use App\Models\ClaseDocente;
use App\Models\Observacion;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        return match ($user->rol) {
            'REPRESENTANTE' => $this->representante($request),
            'ESTUDIANTE' => $this->estudiante($request),
            'INSPECCION' => $this->inspector($request),
            default => $this->general($request),
        };
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
        $data['citas_hoy'] = Cita::with(['alumno', 'docente'])
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