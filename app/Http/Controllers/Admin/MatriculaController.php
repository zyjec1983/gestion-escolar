<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\ValidaDatosAlumno;
use App\Http\Controllers\Controller;
use App\Models\Alumno;
use App\Models\Curso;
use App\Models\Matricula;
use App\Models\Paralelo;
use App\Models\PeriodoLectivo;
use Illuminate\Http\Request;

class MatriculaController extends Controller
{
    use ValidaDatosAlumno;

    public function edit($id)
    {
        $matricula = Matricula::with('alumno.representante', 'curso', 'paralelo', 'periodo')->findOrFail($id);
        $cursos = Curso::orderBy('nombre')->get();
        $paralelos = Paralelo::orderBy('nombre')->get();
        $periodos = PeriodoLectivo::orderByDesc('id')->get();

        return view('admin.matriculas.edit', compact('matricula', 'cursos', 'paralelos', 'periodos'));
    }

    public function update(Request $request, $id)
    {
        $matricula = Matricula::with('alumno')->findOrFail($id);

        $validated = $request->validate(array_merge(
            $this->reglasAlumno(),
            [
                'codigo_matricula' => 'nullable|string|max:20',
                'curso_id' => 'required|exists:cursos,id',
                'paralelo_id' => 'required|exists:paralelos,id',
                'periodo_id' => 'required|exists:periodos_lectivos,id',
                'especializacion' => 'nullable|string|max:100',
                'fecha_matricula' => 'nullable|date',
                'firma_rectora_completada' => 'boolean',
                'firma_secretaria_completada' => 'boolean',
                'firma_representante_completada' => 'boolean',
            ]
        ));

        if (empty($validated['representante_alterno_parentesco']) && !empty($validated['representante_alterno_parentesco_otro'])) {
            $validated['representante_alterno_parentesco'] = $validated['representante_alterno_parentesco_otro'];
        }

        $alumnoData = $this->datosAlumno($validated);

        $matriculaData = collect($validated)->only([
            'codigo_matricula', 'curso_id', 'paralelo_id', 'periodo_id', 'especializacion',
            'firma_rectora_completada', 'firma_secretaria_completada', 'firma_representante_completada',
        ])->toArray();

        $matricula->alumno->update($alumnoData);
        $matricula->update($matriculaData);

        return redirect()->route('admin.matriculas.edit', $matricula->id)
            ->with('success', 'Ficha de matrícula actualizada correctamente.');
    }
}
