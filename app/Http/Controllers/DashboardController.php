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
            default => $this->general($request),
        };
    }

    private function general(Request $request)
    {
        $data = (new DashboardResource($request->user()))->resolve($request);

        return view('dashboard.index', $data);
    }

    private function representante(Request $request)
    {
        return app(RepresentanteController::class)->dashboard($request);
    }

    private function estudiante(Request $request)
    {
        $user = $request->user();
        $alumnos = $user->estudiantes()->with('matriculas')->get();
        $noLeidos = $user->mensajesRecibidos()->where('leido', false)->count();

        return view('dashboard.estudiante', compact('alumnos', 'noLeidos', 'user'));
    }
}