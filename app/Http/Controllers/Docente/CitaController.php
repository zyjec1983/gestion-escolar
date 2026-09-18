<?php

namespace App\Http\Controllers\Docente;

use App\Http\Controllers\Controller;
use App\Models\Alumno;
use App\Models\Cita;
use App\Models\ClaseDocente;
use Carbon\Carbon;
use Illuminate\Http\Request;

class CitaController extends Controller
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
        if ($clase) {
            $alumnos = Alumno::with('representante')
                ->whereHas('matriculas', function ($q) use ($clase) {
                    $q->where('curso_id', $clase->curso_id)
                      ->where('paralelo_id', $clase->paralelo_id);
                })->orderBy('apellidos')->orderBy('nombres')->get();
        }

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

        $startOfMonth = $date->copy()->startOfMonth();
        $endOfMonth = $date->copy()->endOfMonth();
        $diasConCitas = Cita::where('docente_id', $user->id)
            ->whereDate('fecha_inicio', '>=', $startOfMonth)
            ->whereDate('fecha_inicio', '<=', $endOfMonth)
            ->selectRaw("strftime('%d', fecha_inicio) as dia")
            ->pluck('dia')
            ->map(fn($d) => (int) $d)
            ->toArray();

        $diaSeleccionado = $request->query('dia');
        $citasDelDia = collect();
        if ($diaSeleccionado) {
            $fechaSel = Carbon::createFromDate($year, $month, $diaSeleccionado)->format('Y-m-d');
            $citasDelDia = Cita::with(['alumno.representante'])
                ->where('docente_id', $user->id)
                ->whereDate('fecha_inicio', $fechaSel)
                ->orderBy('fecha_inicio')
                ->get();
        }

        $motivos = ['Bajo rendimiento académico', 'Problemas de conducta', 'Inasistencias',
            'Reunión de padres', 'Seguimiento académico', 'Otro'];

        return view('docente.citas.index', compact(
            'clases', 'clase', 'alumnos', 'calendar', 'month', 'year', 'date',
            'prevMonth', 'prevYear', 'nextMonth', 'nextYear', 'diasConCitas',
            'diaSeleccionado', 'citasDelDia', 'motivos'
        ));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'clase_docente_id' => 'required|exists:clases_docentes,id',
            'alumno_id' => 'required|exists:alumnos,id',
            'fecha' => 'required|date|after_or_equal:today',
            'hora' => 'required|integer|between:0,23',
            'minuto' => 'required|in:0,15,30,45',
            'motivo' => 'required|string|max:255',
            'mensaje' => 'nullable|string|max:500',
        ]);

        $horaStr = sprintf('%02d:%02d', $validated['hora'], $validated['minuto']);
        $fechaHora = Carbon::parse($validated['fecha'] . ' ' . $horaStr);
        $fechaFin = $fechaHora->copy()->addHour();

        $alumno = Alumno::with('representante')->findOrFail($validated['alumno_id']);

        Cita::create([
            'solicitante_type' => 'App\\Models\\User',
            'solicitante_id' => $request->user()->id,
            'docente_id' => $request->user()->id,
            'alumno_id' => $validated['alumno_id'],
            'fecha_inicio' => $fechaHora,
            'fecha_fin' => $fechaFin,
            'estado' => 'pendiente',
            'motivo' => $validated['motivo'] . ($validated['mensaje'] ? "\n\n" . $validated['mensaje'] : ''),
        ]);

        $parts = explode('-', $validated['fecha']);
        return redirect()->route('docente.citas.index', [
            'anio' => $parts[0],
            'mes' => (int) $parts[1],
            'dia' => (int) $parts[2],
        ])->with('success', 'Cita agendada correctamente.');
    }
}
