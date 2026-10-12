<?php

namespace App\Http\Controllers\Docente;

use App\Http\Controllers\ObservacionBaseController;
use App\Models\Alumno;
use App\Models\ClaseDocente;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ObservacionController extends ObservacionBaseController
{
    protected function rutaIndex(): string
    {
        return 'docente.observaciones.index';
    }

    protected function prefijoRuta(): string
    {
        return 'docente.observaciones';
    }

    protected function selector(): string
    {
        return 'clase';
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $clases = ClaseDocente::with(['curso', 'paralelo', 'materia'])
            ->where('user_id', $user->id)
            ->get();

        $claseId = $request->query('clase_docente_id', $clases->first()?->id);
        $clase = $clases->firstWhere('id', $claseId) ?? $clases->first();

        $alumnos = collect();
        $alumnoSeleccionado = null;
        $observaciones = collect();

        $alumnoId = $request->query('alumno_id');

        if ($clase) {
            $alumnos = Alumno::whereHas('matriculas', function ($q) use ($clase) {
                $q->where('curso_id', $clase->curso_id)
                  ->where('paralelo_id', $clase->paralelo_id);
            })->orderBy('apellidos')->orderBy('nombres')->get();

            if ($alumnoId) {
                $alumnoSeleccionado = $alumnos->firstWhere('id', $alumnoId);
            }
            if (!$alumnoSeleccionado) {
                $alumnoSeleccionado = $alumnos->first();
            }

            if ($alumnoSeleccionado) {
                $observaciones = $this->historial($alumnoSeleccionado->id);
            }
        }

        return view($this->vistaIndex(), $this->datosVista([
            'clases' => $clases,
            'clase' => $clase,
            'alumnos' => $alumnos,
            'alumnoSeleccionado' => $alumnoSeleccionado,
            'observaciones' => $observaciones,
        ]));
    }

    /**
     * Un docente solo puede registrar observaciones de alumnos de sus clases.
     * Se replica el criterio del listado: mismo curso y mismo paralelo que
     * alguna de sus clases, sin importar la clase que envie el formulario.
     */
    protected function validarAlcance(Request $request, Alumno $alumno): void
    {
        $clases = ClaseDocente::where('user_id', $request->user()->id)
            ->get(['id', 'curso_id', 'paralelo_id']);

        if ($clases->isEmpty()) {
            throw ValidationException::withMessages([
                'alumno_id' => 'No tienes clases asignadas, asi que no puedes registrar observaciones.',
            ]);
        }

        $matriculas = $alumno->matriculas()->get(['curso_id', 'paralelo_id']);

        $pertenece = $clases->contains(fn (ClaseDocente $clase) => $matriculas->contains(
            fn ($matricula) => $matricula->curso_id == $clase->curso_id
                && $matricula->paralelo_id == $clase->paralelo_id
        ));

        if (!$pertenece) {
            throw ValidationException::withMessages([
                'alumno_id' => 'Ese alumno no pertenece a ninguna de tus clases.',
            ]);
        }
    }

    protected function parametrosTrasGuardar(Request $request, Alumno $alumno): array
    {
        return [
            'clase_docente_id' => $request->input('clase_docente_id'),
            'alumno_id' => $alumno->id,
        ];
    }
}