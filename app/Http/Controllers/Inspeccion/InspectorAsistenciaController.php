<?php

namespace App\Http\Controllers\Inspeccion;

use App\Http\Controllers\Controller;
use App\Models\Alumno;
use App\Models\Asistencia;
use App\Models\Matricula;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class InspectorAsistenciaController extends Controller
{
    public static function aulasDisponibles()
    {
        return Matricula::query()
            ->join('cursos', 'matriculas.curso_id', '=', 'cursos.id')
            ->join('paralelos', 'matriculas.paralelo_id', '=', 'paralelos.id')
            ->select(
                'matriculas.curso_id',
                'matriculas.paralelo_id',
                'cursos.nombre as curso_nombre',
                'paralelos.nombre as paralelo_nombre'
            )
            ->distinct()
            ->orderByRaw("CASE
                WHEN cursos.nombre LIKE '%OCTAVO%' THEN 1
                WHEN cursos.nombre LIKE '%NOVENO%' THEN 2
                WHEN cursos.nombre LIKE '%DECIMO%' THEN 3
                WHEN cursos.nombre LIKE '%PRIMERO DE BACHILLERATO%' THEN 4
                WHEN cursos.nombre LIKE '%SEGUNDO%' THEN 5
                WHEN cursos.nombre LIKE '%TERCERO%' THEN 6
                ELSE 99 END")
            ->orderBy('cursos.nombre')
            ->orderBy('paralelos.nombre')
            ->get();
    }

    public function index(Request $request)
    {
        $aulas = self::aulasDisponibles();

        $primera = $aulas->first();

        $cursoId = (int) $request->query('curso_id', $primera->curso_id ?? 0);
        $paraleloId = (int) $request->query('paralelo_id', $primera->paralelo_id ?? 0);

        $aula = $aulas->first(fn ($a) => $a->curso_id == $cursoId && $a->paralelo_id == $paraleloId)
            ?? $primera;

        $fecha = $request->query('fecha', Carbon::today()->toDateString());

        $alumnos = collect();
        $asistenciaExistente = collect();

        if ($aula) {
            $alumnos = Alumno::whereHas('matriculas', function ($q) use ($aula) {
                $q->where('curso_id', $aula->curso_id)
                    ->where('paralelo_id', $aula->paralelo_id);
            })->orderBy('apellidos')->orderBy('nombres')->get();

            $asistenciaExistente = Asistencia::where('curso_id', $aula->curso_id)
                ->where('paralelo_id', $aula->paralelo_id)
                ->whereDate('fecha', $fecha)
                ->get()
                ->keyBy('alumno_id');
        }

        return view('inspeccion.asistencia.index', compact(
            'aulas', 'aula', 'fecha', 'alumnos', 'asistenciaExistente'
        ));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'curso_id' => 'required|exists:cursos,id',
            'paralelo_id' => 'required|exists:paralelos,id',
            'fecha' => 'required|date',
            'asistencia' => 'required|array',
            'asistencia.*.alumno_id' => 'required|exists:alumnos,id',
            'asistencia.*.estado' => 'required|in:presente,justificada,injustificada,atraso',
            'asistencia.*.observacion' => 'nullable|string|max:500',
        ]);

        $userId = $request->user()->id;

        foreach ($validated['asistencia'] as $item) {
            $existente = Asistencia::where('curso_id', $validated['curso_id'])
                ->where('paralelo_id', $validated['paralelo_id'])
                ->where('alumno_id', $item['alumno_id'])
                ->whereDate('fecha', $validated['fecha'])
                ->first();

            if ($existente) {
                $existente->update([
                    'estado' => $item['estado'],
                    'observacion' => $item['observacion'] ?? null,
                    'editado_por' => $userId,
                    'editado_en' => Carbon::now(),
                ]);
            } else {
                Asistencia::create([
                    'curso_id' => $validated['curso_id'],
                    'paralelo_id' => $validated['paralelo_id'],
                    'alumno_id' => $item['alumno_id'],
                    'fecha' => $validated['fecha'],
                    'estado' => $item['estado'],
                    'observacion' => $item['observacion'] ?? null,
                    'registrado_por' => $userId,
                ]);
            }
        }

        return redirect()->route('inspeccion.asistencia.index', [
            'curso_id' => $validated['curso_id'],
            'paralelo_id' => $validated['paralelo_id'],
            'fecha' => $validated['fecha'],
        ])->with('success', 'Asistencia guardada correctamente.');
    }
}
