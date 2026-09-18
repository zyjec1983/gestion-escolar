<?php

namespace App\Http\Controllers;

use App\Models\ClaseDocente;
use App\Models\Materia;
use App\Models\PeriodoLectivo;
use App\Models\Unidad;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PlanificacionController extends Controller
{
    public const PERMISOS = ['ADMIN', 'SECRETARIA', 'RECTORA', 'VICERRECTORA', 'DOCENTE'];

    public function index(Request $request)
    {
        $materiaId = $request->query('materia_id');

        $query = Unidad::with(['materia', 'claseDocente.curso', 'claseDocente.paralelo', 'periodoLectivo', 'creador'])
            ->latest('fecha_inicio');

        if ($materiaId) {
            $query->where('materia_id', $materiaId);
        }

        $planificaciones = $query->get()->groupBy(fn ($u) => $u->materia?->nombre ?? 'Sin materia');

        $materias = Materia::query()->whereHas('unidades')->orderBy('nombre')->get();

        return view('planificaciones.index', compact('planificaciones', 'materias', 'materiaId'));
    }

    public function create()
    {
        $combos = ClaseDocente::with(['materia', 'curso', 'paralelo'])
            ->get()
            ->groupBy(fn ($c) => $c->materia_id . '-' . $c->curso_id)
            ->map(function ($items) {
                $primera = $items->first();

                return [
                    'clave' => $primera->materia_id . '-' . $primera->curso_id,
                    'materia_id' => $primera->materia_id,
                    'materia' => $primera->materia->nombre,
                    'curso_id' => $primera->curso_id,
                    'curso' => $primera->curso->nombre,
                    'paralelos' => $items->map(fn ($c) => [
                        'id' => $c->paralelo_id,
                        'nombre' => $c->paralelo->nombre,
                        'clase_docente_id' => $c->id,
                    ])->values(),
                ];
            })
            ->values()
            ->sortBy(fn ($c) => $c['curso'] . ' ' . $c['materia'])
            ->values();

        $quimestres = ['Q1' => 'Primer Quimestre', 'Q2' => 'Segundo Quimestre'];
        $parciales = ['P1' => 'Parcial 1', 'P2' => 'Parcial 2', 'P3' => 'Parcial 3'];
        $periodo = PeriodoLectivo::where('activo', true)->first();

        return view('planificaciones.create', compact('combos', 'quimestres', 'parciales', 'periodo'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'materia_id' => ['required', 'exists:materias,id'],
            'curso_id' => ['required', 'exists:cursos,id', function ($attr, $value, $fail) use ($request) {
                if (!ClaseDocente::where('materia_id', $request->input('materia_id'))->where('curso_id', $value)->exists()) {
                    $fail('No existe una clase para esa materia y nivel.');
                }
            }],
            'paralelo_ids' => ['required', 'array', 'min:1', function ($attr, $value, $fail) use ($request) {
                $disponibles = ClaseDocente::where('materia_id', $request->input('materia_id'))
                    ->where('curso_id', $request->input('curso_id'))
                    ->pluck('paralelo_id')
                    ->unique();

                if ($disponibles->intersect($value)->count() !== count(array_unique($value))) {
                    $fail('Uno de los paralelos seleccionados no pertenece a ese nivel y materia.');
                }
            }],
            'paralelo_ids.*' => 'distinct|exists:paralelos,id',
            'titulo' => 'required|string|max:255',
            'descripcion' => 'required|string',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
            'quimestre' => 'required|in:Q1,Q2',
            'parcial' => 'required|in:P1,P2,P3',
        ]);

        $periodo = PeriodoLectivo::where('activo', true)->first();
        $grupoId = (string) Str::uuid();

        $clases = ClaseDocente::where('materia_id', $validated['materia_id'])
            ->where('curso_id', $validated['curso_id'])
            ->whereIn('paralelo_id', $validated['paralelo_ids'])
            ->get();

        $creadas = 0;
        foreach ($validated['paralelo_ids'] as $paraleloId) {
            $clase = $clases->firstWhere('paralelo_id', $paraleloId);

            Unidad::create([
                'materia_id' => $validated['materia_id'],
                'clase_docente_id' => $clase?->id,
                'titulo' => $validated['titulo'],
                'descripcion' => $validated['descripcion'],
                'fecha_inicio' => $validated['fecha_inicio'],
                'fecha_fin' => $validated['fecha_fin'],
                'quimestre' => $validated['quimestre'],
                'parcial' => $validated['parcial'],
                'periodo_lectivo_id' => $periodo?->id,
                'creado_por' => $request->user()->id,
                'grupo_id' => $grupoId,
            ]);

            $creadas++;
        }

        return redirect()->route('planificaciones.index')
            ->with('success', "Planificación guardada y aplicada a {$creadas} paralelo(s).");
    }

    public function edit(Unidad $unidad)
    {
        abort_unless(in_array(request()->user()->rol, self::PERMISOS), 403);

        $grupo = $unidad->grupo_id
            ? Unidad::where('grupo_id', $unidad->grupo_id)
                ->with(['claseDocente.curso', 'claseDocente.paralelo'])
                ->get()
            : collect([$unidad])->load(['claseDocente.curso', 'claseDocente.paralelo']);

        $quimestres = ['Q1' => 'Primer Quimestre', 'Q2' => 'Segundo Quimestre'];
        $parciales = ['P1' => 'Parcial 1', 'P2' => 'Parcial 2', 'P3' => 'Parcial 3'];

        return view('planificaciones.edit', compact('unidad', 'grupo', 'quimestres', 'parciales'));
    }

    public function update(Request $request, Unidad $unidad)
    {
        abort_unless(in_array($request->user()->rol, self::PERMISOS), 403);

        $validated = $request->validate([
            'titulo' => 'required|string|max:255',
            'descripcion' => 'required|string',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
            'quimestre' => 'required|in:Q1,Q2',
            'parcial' => 'required|in:P1,P2,P3',
        ]);

        $grupo = $unidad->grupo_id
            ? Unidad::where('grupo_id', $unidad->grupo_id)->get()
            : collect([$unidad]);

        $grupo->each(function ($u) use ($validated) {
            $u->update($validated);
        });

        $total = $grupo->count();

        return redirect()->route('planificaciones.index')
            ->with('success', $total > 1
                ? "Planificación actualizada y aplicada a {$total} paralelo(s)."
                : 'Planificación actualizada correctamente.');
    }

    public function destroy(Request $request, Unidad $unidad)
    {
        abort_unless(in_array($request->user()->rol, self::PERMISOS), 403);

        $grupo = $unidad->grupo_id
            ? Unidad::where('grupo_id', $unidad->grupo_id)->get()
            : collect([$unidad]);

        $total = $grupo->count();
        $grupo->each->delete();

        return redirect()->route('planificaciones.index')
            ->with('success', $total > 1
                ? "Planificación eliminada en {$total} paralelo(s)."
                : 'Planificación eliminada.');
    }
}