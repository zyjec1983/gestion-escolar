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

        if ($clase) {
            $matriculas = $clase->curso_id;
            $alumnos = Alumno::whereHas('matriculas', function ($q) use ($clase) {
                $q->where('curso_id', $clase->curso_id)
                  ->where('paralelo_id', $clase->paralelo_id);
            })->orderBy('apellidos')->orderBy('nombres')->get();

            $asistenciaExistente = Asistencia::where('clase_docente_id', $clase->id)
                ->where('fecha', $fecha)
                ->get()
                ->keyBy('alumno_id');
        }

        return view('docente.asistencia.index', compact(
            'clases', 'clase', 'fecha', 'alumnos', 'asistenciaExistente'
        ));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'clase_docente_id' => 'required|exists:clases_docentes,id',
            'fecha' => 'required|date',
            'asistencia' => 'required|array',
            'asistencia.*.alumno_id' => 'required|exists:alumnos,id',
            'asistencia.*.estado' => 'required|in:presente,justificada,injustificada',
            'asistencia.*.observacion' => 'nullable|string|max:500',
        ]);

        foreach ($validated['asistencia'] as $item) {
            Asistencia::updateOrCreate(
                [
                    'clase_docente_id' => $validated['clase_docente_id'],
                    'alumno_id' => $item['alumno_id'],
                    'fecha' => $validated['fecha'],
                ],
                [
                    'estado' => $item['estado'],
                    'observacion' => $item['observacion'] ?? null,
                ]
            );
        }

        return redirect()->route('docente.asistencia.index', [
            'clase_docente_id' => $validated['clase_docente_id'],
            'fecha' => $validated['fecha'],
        ])->with('success', 'Asistencia guardada correctamente.');
    }
}
