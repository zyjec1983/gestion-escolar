<?php

namespace App\Http\Controllers;

use App\Http\Resources\ClaseCollection;
use App\Http\Resources\InicioClaseResource;
use App\Models\Alumno;
use App\Models\ClaseDocente;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class AulaVirtualController extends Controller
{
    public function index(Request $request)
    {
        abort_if($request->user()->rol === 'REPRESENTANTE', 403, 'No tienes acceso a esta sección.');

        $clases = $this->clasesParaUsuario($request->user());

        return view('clases.index', (new ClaseCollection($clases))->resolve($request));
    }

    public function show($id, Request $request)
    {
        abort_if($request->user()->rol === 'REPRESENTANTE', 403, 'No tienes acceso a esta sección.');

        $clase = ClaseDocente::with(['curso', 'paralelo', 'materia.unidades'])->findOrFail($id);

        $this->verificarAcceso($clase, $request->user());

        $data = (new InicioClaseResource($clase))->resolve($request);
        $data['layout'] = 'aula';
        $data['es_docente'] = $request->user()->rol === 'DOCENTE';

        return view('aula.virtual', $data);
    }

    public function clasesParausuario(User $user): Collection
    {
        if ($user->rol === 'DOCENTE') {
            return ClaseDocente::with(['curso', 'paralelo', 'materia'])
                ->where('user_id', $user->id)
                ->get();
        }

        $clases = collect();
        $this->alumnosDelUsuario($user)->each(function (Alumno $alumno) use (&$clases) {
            $pares = $alumno->matriculas->map(fn ($m) => [$m->curso_id, $m->paralelo_id])->unique();
            foreach ($pares as [$cursoId, $paraleloId]) {
                $clases = $clases->merge(
                    ClaseDocente::with(['curso', 'paralelo', 'materia'])
                        ->where('curso_id', $cursoId)
                        ->where('paralelo_id', $paraleloId)
                        ->get()
                );
            }
        });

        return $clases->unique('id')->values();
    }

    public function alumnosDelUsuario(User $user): Collection
    {
        return match ($user->rol) {
            'ESTUDIANTE' => Alumno::with('matriculas')->where('user_id', $user->id)->get(),
            'REPRESENTANTE' => $user->representados()->with('matriculas')->get(),
            default => collect(),
        };
    }

    private function verificarAcceso(ClaseDocente $clase, User $user): void
    {
        if ($user->rol === 'DOCENTE') {
            abort_if($clase->user_id !== $user->id, 403, 'No tienes acceso a esta clase.');
            return;
        }
        if ($user->rol === 'ADMIN') {
            return;
        }

        $tieneAcceso = $this->alumnosDelUsuario($user)->contains(function (Alumno $alumno) use ($clase) {
            return $alumno->matriculas->contains(
                fn ($m) => $m->curso_id === $clase->curso_id && $m->paralelo_id === $clase->paralelo_id
            );
        });

        abort_unless($tieneAcceso, 403, 'No tienes acceso a esta clase.');
    }
}