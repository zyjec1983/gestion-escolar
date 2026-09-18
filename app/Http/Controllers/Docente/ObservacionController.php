<?php

namespace App\Http\Controllers\Docente;

use App\Http\Controllers\Controller;
use App\Models\Alumno;
use App\Models\ClaseDocente;
use App\Models\Observacion;
use App\Models\TipoObservacion;
use Illuminate\Http\Request;

class ObservacionController extends Controller
{
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
        $tiposObservacion = TipoObservacion::orderBy('nombre')->get();

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
                $observaciones = Observacion::with(['tipoObservacion', 'profesor'])
                    ->where('alumno_id', $alumnoSeleccionado->id)
                    ->latest()
                    ->get();
            }
        }

        return view('docente.observaciones.index', compact(
            'clases', 'clase', 'alumnos', 'alumnoSeleccionado',
            'observaciones', 'tiposObservacion'
        ));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'alumno_id' => 'required|exists:alumnos,id',
            'tipo_observacion_id' => 'required|exists:tipos_observacion,id',
            'descripcion_texto' => 'required|string|max:1000',
            'notificar_representante' => 'boolean',
        ]);

        Observacion::create([
            'alumno_id' => $validated['alumno_id'],
            'tipo_observacion_id' => $validated['tipo_observacion_id'],
            'user_id' => $request->user()->id,
            'descripcion_texto' => $validated['descripcion_texto'],
            'notificar_representante' => $request->boolean('notificar_representante'),
        ]);

        return redirect()->route('docente.observaciones.index', [
            'clase_docente_id' => $request->query('clase_docente_id'),
            'alumno_id' => $validated['alumno_id'],
        ])->with('success', 'Observación registrada correctamente.');
    }

    public function print($alumnoId)
    {
        $alumno = Alumno::findOrFail($alumnoId);
        $observaciones = Observacion::with(['tipoObservacion', 'profesor'])
            ->where('alumno_id', $alumnoId)
            ->latest()
            ->get();

        return view('docente.observaciones.print', compact('alumno', 'observaciones'));
    }
}
