<?php

namespace App\Http\Controllers;

use App\Models\Alumno;
use App\Models\Agenda;
use App\Models\ClaseDocente;
use App\Models\Foro;
use App\Models\User;
use Illuminate\Http\Request;

class EstudianteController extends Controller
{
    private function alumnoPropio(User $user): ?Alumno
    {
        return $user->estudiantes()->with('matriculas')->first();
    }

    private function clasesDeAlumno(Alumno $alumno): \Illuminate\Support\Collection
    {
        $clases = collect();

        foreach ($alumno->matriculas as $m) {
            $clases = $clases->merge(
                ClaseDocente::with(['curso', 'paralelo', 'materia', 'docente', 'agendas.archivosAdjuntos'])
                    ->where('curso_id', $m->curso_id)
                    ->where('paralelo_id', $m->paralelo_id)
                    ->get()
            );
        }

        return $clases->unique('id')->values();
    }

    private function materialesDeAlumno(Alumno $alumno): \Illuminate\Support\Collection
    {
        return $this->clasesDeAlumno($alumno)
            ->flatMap(function (ClaseDocente $clase) {
                return $clase->materiales()
                    ->with('subidor')
                    ->orderByDesc('created_at')
                    ->get()
                    ->map(fn ($m) => [
                        'id' => $m->id,
                        'titulo' => $m->titulo,
                        'descripcion' => $m->descripcion,
                        'nombre_archivo' => $m->nombre_archivo,
                        'url' => $m->url,
                        'created_at' => $m->created_at,
                        'materia' => $clase->materia?->nombre ?? 'Sin materia',
                        'clase_id' => $clase->id,
                        'subidor' => $m->subidor ? trim($m->subidor->name . ' ' . $m->subidor->apellidos) : 'Docente',
                    ]);
            })
            ->sortByDesc('created_at')
            ->values();
    }

    private function materiasConAgendas(Alumno $alumno): \Illuminate\Support\Collection
    {
        $clases = $this->clasesDeAlumno($alumno);

        return $clases->groupBy('materia_id')->map(function ($grupoClases, $materiaId) use ($alumno) {
            $clase = $grupoClases->first();
            $agendas = $grupoClases->flatMap->agendas->unique('id');

            $activas = $agendas->filter(fn ($a) => !$a->estaEntregadaPor($alumno)
                && ($a->fecha_entrega_limite === null
                    || $a->fecha_entrega_limite->greaterThanOrEqualTo(\Illuminate\Support\Carbon::today())));

            return [
                'id' => $materiaId,
                'nombre' => $clase->materia->nombre,
                'color' => $clase->materia->color_tarjeta ?? '#E63946',
                'docente' => $clase->docente ? trim($clase->docente->name . ' ' . $clase->docente->apellidos) : null,
                'agendas_activas' => $activas->count(),
                'total_agendas' => $agendas->count(),
                'hay_actividad' => $activas->isNotEmpty(),
            ];
        })->values();
    }

    public function dashboard(Request $request)
    {
        $user = $request->user();
        $alumnos = $user->estudiantes()->with('matriculas')->get();
        $alumno = $this->alumnoPropio($user);

        $materias = collect();
        $forosActivos = 0;
        $clases = collect();
        if ($alumno) {
            $materias = $this->materiasConAgendas($alumno);
            $clases = $this->clasesDeAlumno($alumno);
            $forosActivos = Foro::whereIn('clase_docente_id', $clases->pluck('id'))
                ->get()
                ->filter(fn (Foro $foro) => $foro->estado() === 'activo')
                ->count();
        }

        $pendientes = $materias->sum('agendas_activas');

        $stats = [
            'mensajes' => $user->mensajesRecibidos()->where('leido', false)->count(),
            'materiales' => $alumno ? $this->materialesDeAlumno($alumno)->count() : 0,
            'pendientes' => $pendientes,
            'foros' => $forosActivos,
        ];

        return view('dashboard.estudiante', compact('user', 'alumnos', 'alumno', 'materias', 'stats'));
    }

    public function marcarEntregada(Request $request, Agenda $agenda)
    {
        $alumno = $this->alumnoPropio($request->user());

        if (!$alumno) {
            abort(403);
        }

        $agenda->marcarEntregada($alumno);

        return redirect()->back()->with('success', 'Agenda marcada como entregada.');
    }

    public function agendas(Request $request)
    {
        $user = $request->user();
        $alumno = $this->alumnoPropio($user);

        $clases = collect();
        $agendas = collect();
        $materiaSeleccionada = $request->query('materia_id');

        if ($alumno) {
            $clases = $this->clasesDeAlumno($alumno);

            if ($materiaSeleccionada) {
                $clases = $clases->where('materia_id', (int) $materiaSeleccionada)->values();
            }

            $agendas = $clases->flatMap->agendas
                ->unique('id')
                ->sortByDesc('fecha_entrega_limite')
                ->values();
        }

        $materias = collect();
        $materiales_recientes = collect();
        $materiales_por_materia = collect();
        if ($alumno) {
            $materias = $this->materiasConAgendas($alumno);

            $materiales = $this->materialesDeAlumno($alumno);
            $materiales_recientes = $materiales->filter(
                fn ($m) => $m['created_at']->gte(now()->subDays(7))
            )->values();
            $materiales_por_materia = $materiales->groupBy('materia');
        }

        return view('estudiante.agendas', compact('user', 'alumno', 'clases', 'agendas', 'materias', 'materiaSeleccionada', 'materiales_recientes', 'materiales_por_materia'));
    }
}