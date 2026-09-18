<?php

namespace App\Http\Controllers;

use App\Models\Alumno;
use App\Models\Cita;
use App\Models\ClaseDocente;
use App\Models\Observacion;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class RepresentanteController extends Controller
{
    private function representados(User $user): Collection
    {
        return $user->representados()->with('matriculas')->get();
    }

    private function alumnoSeleccionado(Request $request, Collection $representados): ?Alumno
    {
        $alumnoId = (int) $request->query('alumno_id', $representados->first()?->id);

        return $representados->firstWhere('id', $alumnoId) ?? $representados->first();
    }

    private function clasesDeAlumno(Alumno $alumno): Collection
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

    public function dashboard(Request $request)
    {
        $user = $request->user();
        $representados = $this->representados($user);
        $alumno = $this->alumnoSeleccionado($request, $representados);

        $materias = collect();
        if ($alumno) {
            $materias = $this->materiasConAgendas($alumno);
        }

        $stats = [
            'mensajes' => $user->mensajesRecibidos()->where('leido', false)->count(),
            'citas_pendientes' => Cita::where('solicitante_type', User::class)
                ->where('solicitante_id', $user->id)
                ->where('estado', 'pendiente')
                ->count(),
            'observaciones' => $alumno ? Observacion::where('alumno_id', $alumno->id)->count() : 0,
        ];

        return view('dashboard.representante', compact('representados', 'alumno', 'materias', 'stats'));
    }

    private function materiasConAgendas(Alumno $alumno): Collection
    {
        $clases = $this->clasesDeAlumno($alumno);

        return $clases->groupBy('materia_id')->map(function ($grupoClases, $materiaId) {
            $clase = $grupoClases->first();
            $agendas = $grupoClases->flatMap->agendas->unique('id');

            $activas = $agendas->filter(fn ($a) => $a->fecha_entrega_limite === null
                || $a->fecha_entrega_limite->greaterThanOrEqualTo(\Illuminate\Support\Carbon::today()));

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

    private function materiasParaFormulario(Alumno $alumno): Collection
    {
        $clases = $this->clasesDeAlumno($alumno);

        return $clases->groupBy('materia_id')->map(function ($items, $materiaId) {
            $primera = $items->first();
            return [
                'id' => $materiaId,
                'nombre' => $primera->materia->nombre,
                'docentes' => $items->map(fn ($c) => [
                    'id' => $c->docente?->id,
                    'nombre' => $c->docente ? trim($c->docente->name . ' ' . $c->docente->apellidos) : null,
                ])->filter(fn ($d) => $d['id'] !== null)->values()->all(),
            ];
        })->values();
    }

    public function observaciones(Request $request)
    {
        $user = $request->user();
        $representados = $this->representados($user);
        $alumno = $this->alumnoSeleccionado($request, $representados);

        $observaciones = collect();
        if ($alumno) {
            $observaciones = Observacion::with(['tipoObservacion', 'profesor'])
                ->where('alumno_id', $alumno->id)
                ->latest()
                ->get();
        }

        return view('representante.observaciones', compact('representados', 'alumno', 'observaciones'));
    }

    public function citas(Request $request)
    {
        $user = $request->user();
        $representados = $this->representados($user);
        $alumno = $this->alumnoSeleccionado($request, $representados);

        $materias = collect();
        if ($alumno) {
            $materias = $this->materiasParaFormulario($alumno);
        }

        $citas = Cita::with(['alumno', 'docente'])
            ->where('solicitante_type', User::class)
            ->where('solicitante_id', $user->id)
            ->orderByDesc('fecha_inicio')
            ->get();

        [$calendar, $month, $year, $prevMonth, $prevYear, $nextMonth, $nextYear, $diasConCitas] = $this->calendario($request, $citas);

        $diaSeleccionado = $request->query('dia');
        $citasDelDia = collect();
        if ($diaSeleccionado) {
            $fechaSel = Carbon::createFromDate($year, $month, $diaSeleccionado)->format('Y-m-d');
            $citasDelDia = $citas->filter(fn ($c) => $c->fecha_inicio->format('Y-m-d') === $fechaSel)->values();
        }

        $proximas = $citas->filter(fn ($c) => $c->fecha_inicio->greaterThanOrEqualTo(now()))
            ->sortBy('fecha_inicio')
            ->take(5)
            ->values();

        $motivos = ['Consulta', 'Rendimiento académico', 'Tareas', 'Faltas', 'Disciplina', 'Otro'];

        return view('representante.citas', compact(
            'representados', 'alumno', 'materias', 'citas', 'proximas',
            'calendar', 'month', 'year', 'prevMonth', 'prevYear', 'nextMonth', 'nextYear',
            'diasConCitas', 'diaSeleccionado', 'citasDelDia', 'motivos'
        ));
    }

    private function calendario(Request $request, Collection $citas): array
    {
        $month = (int) $request->query('mes', Carbon::now()->month);
        $year = (int) $request->query('anio', Carbon::now()->year);

        $date = Carbon::createFromDate($year, $month, 1);
        $daysInMonth = $date->daysInMonth;
        $firstDayOfWeek = $date->dayOfWeek;

        $calendar = [];
        $week = [];
        for ($i = 0; $i < $firstDayOfWeek; $i++) {
            $week[] = null;
        }
        for ($day = 1; $day <= $daysInMonth; $day++) {
            $week[] = $day;
            if (count($week) == 7) {
                $calendar[] = $week;
                $week = [];
            }
        }
        if (count($week) > 0) {
            while (count($week) < 7) {
                $week[] = null;
            }
            $calendar[] = $week;
        }

        $prevMonth = $month == 1 ? 12 : $month - 1;
        $prevYear = $month == 1 ? $year - 1 : $year;
        $nextMonth = $month == 12 ? 1 : $month + 1;
        $nextYear = $month == 12 ? $year + 1 : $year;

        $diasConCitas = $citas->filter(function (Cita $c) use ($month, $year) {
            return $c->fecha_inicio->month === $month && $c->fecha_inicio->year === $year;
        })->map(fn (Cita $c) => (int) $c->fecha_inicio->format('d'))->unique()->values()->toArray();

        return [$calendar, $month, $year, $prevMonth, $prevYear, $nextMonth, $nextYear, $diasConCitas];
    }

    public function storeCita(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'alumno_id' => ['required', 'exists:alumnos,id', function ($attr, $value, $fail) use ($user) {
                $alumno = Alumno::find($value);
                if (!$alumno || $alumno->representante_user_id !== $user->id) {
                    $fail('El estudiante seleccionado no corresponde a su representación.');
                }
            }],
            'docente_id' => ['required', 'exists:users,id', function ($attr, $value, $fail) use ($request) {
                $alumno = Alumno::find($request->input('alumno_id'));
                if ($alumno) {
                    $pertenece = $alumno->matriculas->contains(function ($m) use ($value) {
                        return ClaseDocente::where('user_id', $value)
                            ->where('curso_id', $m->curso_id)
                            ->where('paralelo_id', $m->paralelo_id)
                            ->exists();
                    });
                    if (!$pertenece) {
                        $fail('El docente seleccionado no dicta clases en el curso de su representado.');
                    }
                }
            }],
            'motivo' => 'required|string|max:255',
            'motivo_otro' => 'nullable|string|max:500',
            'fecha' => 'required|date|after_or_equal:today',
            'hora' => 'required|integer|between:0,23',
            'minuto' => 'required|in:0,15,30,45',
        ]);

        $motivo = $validated['motivo'] === 'Otro' && $validated['motivo_otro']
            ? 'Otro: ' . $validated['motivo_otro']
            : $validated['motivo'];

        $fechaHora = Carbon::parse($validated['fecha'] . ' ' . sprintf('%02d:%02d', $validated['hora'], $validated['minuto']));

        Cita::create([
            'solicitante_type' => User::class,
            'solicitante_id' => $user->id,
            'docente_id' => $validated['docente_id'],
            'alumno_id' => $validated['alumno_id'],
            'fecha_inicio' => $fechaHora,
            'fecha_fin' => $fechaHora->copy()->addHour(),
            'estado' => 'pendiente',
            'motivo' => $motivo,
        ]);

        return redirect()->route('representante.citas', ['alumno_id' => $validated['alumno_id']])
            ->with('success', 'Solicitud de cita enviada al docente.');
    }

    public function agendas(Request $request)
    {
        $user = $request->user();
        $representados = $this->representados($user);
        $alumno = $this->alumnoSeleccionado($request, $representados);

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
        if ($alumno) {
            $materias = $this->materiasConAgendas($alumno);
        }

        return view('representante.agendas', compact('representados', 'alumno', 'clases', 'agendas', 'materias', 'materiaSeleccionada'));
    }
}