<?php

namespace App\Http\Controllers\Docente;

use App\Http\Controllers\Controller;
use App\Models\Alumno;
use App\Models\Asistencia;
use App\Models\ClaseDocente;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class AsistenciaController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $clases = ClaseDocente::with(['curso', 'paralelo', 'materia'])
            ->where('user_id', $user->id)
            ->get();

        $claseId = $request->query('clase_docente_id', $clases->first()?->id);
        $clase = $clases->firstWhere('id', $claseId) ?? $clases->first();

        $fecha = $request->query('fecha', Carbon::today()->toDateString());

        $alumnos = collect();
        $asistenciaExistente = collect();
        $bloqueado = false;

        if ($clase) {
            $alumnos = Alumno::whereHas('matriculas', function ($q) use ($clase) {
                $q->where('curso_id', $clase->curso_id)
                    ->where('paralelo_id', $clase->paralelo_id);
            })->orderBy('apellidos')->orderBy('nombres')->get();

            $asistenciaExistente = Asistencia::where('curso_id', $clase->curso_id)
                ->where('paralelo_id', $clase->paralelo_id)
                ->whereDate('fecha', $fecha)
                ->get()
                ->keyBy('alumno_id');

            // Una vez registrada la asistencia del aula, el docente ya no puede
            // modificarla: solo Inspeccion puede hacerlo.
            $bloqueado = $asistenciaExistente->isNotEmpty();
        }

        return view('docente.asistencia.index', compact(
            'clases', 'clase', 'fecha', 'alumnos', 'asistenciaExistente', 'bloqueado'
        ));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'clase_docente_id' => 'required|exists:clases_docentes,id',
            'fecha' => 'required|date',
            'asistencia' => 'required|array',
            'asistencia.*.alumno_id' => 'required|exists:alumnos,id',
            'asistencia.*.estado' => 'required|in:presente,justificada,injustificada,atraso',
            'asistencia.*.observacion' => 'nullable|string|max:500',
        ]);

        $clase = ClaseDocente::findOrFail($validated['clase_docente_id']);

        $yaRegistrada = Asistencia::where('curso_id', $clase->curso_id)
            ->where('paralelo_id', $clase->paralelo_id)
            ->whereDate('fecha', $validated['fecha'])
            ->exists();

        if ($yaRegistrada) {
            return redirect()->route('docente.asistencia.index', [
                'clase_docente_id' => $clase->id,
                'fecha' => $validated['fecha'],
            ])->with('error', 'La asistencia de este curso ya fue registrada. Solo Inspección puede modificarla.');
        }

        foreach ($validated['asistencia'] as $item) {
            Asistencia::create([
                'curso_id' => $clase->curso_id,
                'paralelo_id' => $clase->paralelo_id,
                'alumno_id' => $item['alumno_id'],
                'fecha' => $validated['fecha'],
                'estado' => $item['estado'],
                'observacion' => $item['observacion'] ?? null,
                'clase_docente_id' => $clase->id,
                'registrado_por' => $request->user()->id,
            ]);
        }

        return redirect()->route('docente.asistencia.index', [
            'clase_docente_id' => $clase->id,
            'fecha' => $validated['fecha'],
        ])->with('success', 'Asistencia guardada correctamente.');
    }
}
