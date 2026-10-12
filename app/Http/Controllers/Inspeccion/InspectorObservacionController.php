<?php

namespace App\Http\Controllers\Inspeccion;

use App\Http\Controllers\ObservacionBaseController;
use App\Models\Alumno;
use App\Models\Curso;
use App\Models\Paralelo;
use Illuminate\Http\Request;

class InspectorObservacionController extends ObservacionBaseController
{
    protected function rutaIndex(): string
    {
        return 'inspeccion.observaciones.index';
    }

    protected function prefijoRuta(): string
    {
        return 'inspeccion.observaciones';
    }

    protected function selector(): string
    {
        return 'curso';
    }

    /** El inspector siempre deja constancia de que el representante fue notificado. */
    protected function notificarEsObligatorio(): bool
    {
        return true;
    }

    public function index(Request $request)
    {
        $cursos = Curso::orderBy('nombre')->get(['id', 'nombre']);

        $cursoId = $request->query('curso_id') ?: null;
        $paraleloId = $request->query('paralelo_id') ?: null;
        $alumnoId = (int) $request->query('alumno_id') ?: null;

        $paralelos = $cursoId
            ? Paralelo::whereHas('matriculas', fn ($q) => $q->where('curso_id', $cursoId))
                ->orderBy('nombre')->get(['id', 'nombre'])
            : Paralelo::orderBy('nombre')->get(['id', 'nombre']);

        $alumnos = Alumno::with('representante')
            ->whereHas('matriculas', fn ($q) => $q
                ->when($cursoId, fn ($qq) => $qq->where('curso_id', $cursoId))
                ->when($paraleloId, fn ($qq) => $qq->where('paralelo_id', $paraleloId)))
            ->orderBy('apellidos')->orderBy('nombres')
            ->get();

        $alumnoSeleccionado = $alumnoId ? $alumnos->firstWhere('id', $alumnoId) : null;

        $observaciones = $alumnoSeleccionado
            ? $this->historial($alumnoSeleccionado->id)
            : collect();

        return view($this->vistaIndex(), $this->datosVista([
            'cursos' => $cursos,
            'paralelos' => $paralelos,
            'cursoId' => $cursoId,
            'paraleloId' => $paraleloId,
            'alumnos' => $alumnos,
            'alumnoSeleccionado' => $alumnoSeleccionado,
            'observaciones' => $observaciones,
        ]));
    }

    protected function parametrosTrasGuardar(Request $request, Alumno $alumno): array
    {
        return [
            'curso_id' => $request->input('curso_id'),
            'paralelo_id' => $request->input('paralelo_id'),
            'alumno_id' => $alumno->id,
        ];
    }
}