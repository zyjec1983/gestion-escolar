<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClaseDocente;
use App\Models\Curso;
use App\Models\CursoMateria;
use App\Models\HistorialCambio;
use App\Models\Materia;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Malla curricular: materias por año de educación.
 *
 * Qué hace:
 * - Administra el catálogo de materias (nombre, área, código y estado).
 * - Agrega, edita y quita materias de la malla de cada año con sus períodos
 *   semanales de 40 minutos.
 * - Bloquea años y materias sin borrar notas ni clases históricas.
 *
 * Conecta con: App\Models\Curso, App\Models\Materia, App\Models\CursoMateria
 * y App\Models\ClaseDocente.
 *
 * Rutas: /admin/materias (admin.materias.*) y /admin/cursos/{curso}/estado.
 */
class MateriaController extends Controller
{
    public function index(Request $request)
    {
        $cursos = Curso::with(['malla' => fn ($q) => $q->with('materia')->orderBy('orden')->orderBy('id')])
            ->enOrdenEscolar()
            ->get();

        $cursoSeleccionado = $request->filled('curso')
            ? $cursos->firstWhere('id', (int) $request->query('curso'))
            : null;

        $cursoSeleccionado ??= $cursos->first();

        $materias = Materia::orderBy('area')->orderBy('nombre')->get();

        $materiasDisponibles = $cursoSeleccionado
            ? $materias->where('activo', true)
                ->whereNotIn('id', $cursoSeleccionado->malla->pluck('materia_id'))
                ->values()
            : collect();

        return view('admin.materias.index', [
            'cursos' => $cursos,
            'cursoSeleccionado' => $cursoSeleccionado,
            'materias' => $materias,
            'materiasDisponibles' => $materiasDisponibles,
            'totalPeriodos' => $cursoSeleccionado?->malla->sum('periodos_semanales') ?? 0,
        ]);
    }

    public function storeMateria(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'area' => 'nullable|string|max:255',
            'codigo' => 'nullable|string|max:50|unique:materias,codigo',
        ], [
            'nombre.required' => 'Escriba el nombre de la materia.',
            'codigo.unique' => 'Ya existe una materia con ese código.',
        ]);

        $materia = Materia::create([
            'nombre' => $validated['nombre'],
            'area' => $validated['area'] ?? null,
            'codigo' => $validated['codigo'] ?? null,
            'activo' => true,
        ]);

        HistorialCambio::registrar('Malla curricular', 'crear', "Materia creada: {$materia->nombre}", ['id' => $materia->id]);

        return redirect()
            ->route('admin.materias.index', $this->parametrosCurso($request))
            ->with('success', 'Materia agregada al catálogo.');
    }

    public function updateMateria(Request $request, Materia $materia)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'area' => 'nullable|string|max:255',
            'codigo' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('materias', 'codigo')->ignore($materia->id),
            ],
        ], [
            'nombre.required' => 'Escriba el nombre de la materia.',
            'codigo.unique' => 'Ya existe una materia con ese código.',
        ]);

        $materia->update([
            'nombre' => $validated['nombre'],
            'area' => $validated['area'] ?? null,
            'codigo' => $validated['codigo'] ?? null,
        ]);

        HistorialCambio::registrar('Malla curricular', 'actualizar', "Materia actualizada: {$materia->nombre}", ['id' => $materia->id]);

        return redirect()
            ->route('admin.materias.index', $this->parametrosCurso($request))
            ->with('success', 'Materia actualizada.');
    }

    public function estadoMateria(Materia $materia)
    {
        $materia->update(['activo' => ! $materia->activo]);

        HistorialCambio::registrar(
            'Malla curricular',
            $materia->activo ? 'reactivar' : 'bloquear',
            "Materia ".($materia->activo ? 'reactivada' : 'bloqueada').": {$materia->nombre}",
            ['id' => $materia->id]
        );

        return back()->with(
            'success',
            $materia->activo
                ? 'Materia reactivada. Ya se puede volver a asignar.'
                : 'Materia bloqueada. No se podrá asignar, pero sus notas se conservan.'
        );
    }

    public function estadoCurso(Curso $curso)
    {
        $curso->update(['activo' => ! $curso->activo]);

        HistorialCambio::registrar(
            'Malla curricular',
            $curso->activo ? 'reactivar' : 'bloquear',
            "Año ".($curso->activo ? 'reactivado' : 'bloqueado').": {$curso->nombre}",
            ['id' => $curso->id]
        );

        return back()->with(
            'success',
            $curso->activo
                ? 'Año reactivado. Ya se puede volver a asignar.'
                : 'Año bloqueado. No se podrá asignar, pero sus notas se conservan.'
        );
    }

    /** Agrega un año de educación al catálogo de cursos. */
    public function storeCurso(Request $request)
    {
        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:255', Rule::unique('cursos', 'nombre'), $this->reglaGrado()],
            'codigo' => ['nullable', 'string', 'max:50', Rule::unique('cursos', 'codigo')],
        ], [
            'nombre.required' => 'Escriba el nombre del año de educación.',
            'nombre.unique' => 'Ya existe un año con ese nombre.',
            'codigo.unique' => 'Ya existe un año con ese código.',
        ]);

        $curso = Curso::create([
            'nombre' => $validated['nombre'],
            'codigo' => $validated['codigo'] ?? null,
            'activo' => true,
        ]);

        HistorialCambio::registrar('Malla curricular', 'crear', "Año creado: {$curso->nombre}", ['id' => $curso->id]);

        return redirect()
            ->route('admin.materias.index', ['curso' => $curso->id])
            ->with('success', 'Año agregado. Ya puede configurar su malla curricular.');
    }

    /** Cambia el nombre o el código de un año de educación. */
    public function updateCurso(Request $request, Curso $curso)
    {
        $validated = $request->validate([
            'nombre' => [
                'required',
                'string',
                'max:255',
                Rule::unique('cursos', 'nombre')->ignore($curso->id),
                $this->reglaGrado(),
            ],
            'codigo' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('cursos', 'codigo')->ignore($curso->id),
            ],
        ], [
            'nombre.required' => 'Escriba el nombre del año de educación.',
            'nombre.unique' => 'Ya existe un año con ese nombre.',
            'codigo.unique' => 'Ya existe un año con ese código.',
        ]);

        $curso->update([
            'nombre' => $validated['nombre'],
            'codigo' => $validated['codigo'] ?? null,
        ]);

        HistorialCambio::registrar('Malla curricular', 'actualizar', "Año actualizado: {$curso->nombre}", ['id' => $curso->id]);

        return redirect()
            ->route('admin.materias.index', ['curso' => $curso->id])
            ->with('success', 'Año actualizado.');
    }

    /**
     * Elimina un año solo si no tiene historial. Si tiene matrículas, clases,
     * asistencias o pre-registros, se debe bloquear para dejar de ofertarlo.
     */
    public function destroyCurso(Curso $curso)
    {
        $dependencias = array_filter([
            'matrículas' => $curso->matriculas()->count(),
            'clases docentes' => $curso->clasesDocentes()->count(),
            'asistencias' => $curso->asistencias()->count(),
            'pre-registros' => $curso->preRegistros()->count(),
        ], fn (int $total) => $total > 0);

        if ($dependencias !== []) {
            $detalle = collect($dependencias)
                ->map(fn (int $total, string $nombre) => "{$total} {$nombre}")
                ->implode(', ');

            return back()->with(
                'error',
                "No se puede eliminar este año porque conserva historial: {$detalle}. Use \"Bloquear año\" para dejar de ofertarlo sin perder datos."
            );
        }

        $nombre = $curso->nombre;
        $id = $curso->id;
        $curso->delete();

        HistorialCambio::registrar('Malla curricular', 'eliminar', "Año eliminado: {$nombre}", ['id' => $id]);

        return redirect()
            ->route('admin.materias.index')
            ->with('success', "Año {$nombre} eliminado junto con su malla curricular.");
    }

    public function storeMalla(Request $request, Curso $curso)
    {
        if (! $curso->activo) {
            return back()->with('error', 'El año está bloqueado. Reactívelo antes de agregar materias.');
        }

        $validated = $request->validate([
            'materia_id' => 'required|exists:materias,id',
            'periodos_semanales' => 'required|integer|min:1|max:20',
            'orden' => 'nullable|integer|min:0|max:999',
        ], [
            'materia_id.required' => 'Seleccione una materia.',
            'periodos_semanales.min' => 'Cada materia debe tener al menos 1 período semanal.',
            'periodos_semanales.max' => 'El máximo es 20 períodos semanales por materia.',
        ]);

        $materia = Materia::findOrFail($validated['materia_id']);

        if (! $materia->activo) {
            return back()->with('error', 'La materia está bloqueada. Reactívela antes de agregarla a la malla.');
        }

        $existe = CursoMateria::where('curso_id', $curso->id)
            ->where('materia_id', $materia->id)
            ->exists();

        if ($existe) {
            return back()->with('error', 'Esa materia ya está en la malla de este año.');
        }

        $registro = CursoMateria::create([
            'curso_id' => $curso->id,
            'materia_id' => $materia->id,
            'periodos_semanales' => $validated['periodos_semanales'],
            'orden' => $validated['orden'] ?? 0,
        ]);

        HistorialCambio::registrar(
            'Malla curricular',
            'crear',
            "Materia {$materia->nombre} agregada a {$curso->nombre}",
            ['id' => $registro->id]
        );

        return redirect()
            ->route('admin.materias.index', ['curso' => $curso->id])
            ->with('success', 'Materia agregada a la malla del año.');
    }

    public function updateMalla(Request $request, Curso $curso, CursoMateria $cursoMateria)
    {
        $this->asegurarPertenece($curso, $cursoMateria);

        if (! $curso->activo) {
            return back()->with('error', 'El año está bloqueado. Reactívelo antes de cambiar su malla.');
        }

        $validated = $request->validate([
            'periodos_semanales' => 'required|integer|min:1|max:20',
            'orden' => 'nullable|integer|min:0|max:999',
        ], [
            'periodos_semanales.min' => 'Cada materia debe tener al menos 1 período semanal.',
            'periodos_semanales.max' => 'El máximo es 20 períodos semanales por materia.',
        ]);

        $cursoMateria->update([
            'periodos_semanales' => $validated['periodos_semanales'],
            'orden' => $validated['orden'] ?? 0,
        ]);

        HistorialCambio::registrar(
            'Malla curricular',
            'actualizar',
            "Malla actualizada en {$curso->nombre}",
            ['id' => $cursoMateria->id]
        );

        return redirect()
            ->route('admin.materias.index', ['curso' => $curso->id])
            ->with('success', 'Períodos actualizados.');
    }

    public function destroyMalla(Curso $curso, CursoMateria $cursoMateria)
    {
        $this->asegurarPertenece($curso, $cursoMateria);

        if (! $curso->activo) {
            return back()->with('error', 'El año está bloqueado. Reactívelo antes de cambiar su malla.');
        }

        $tieneClases = ClaseDocente::where('curso_id', $curso->id)
            ->where('materia_id', $cursoMateria->materia_id)
            ->exists();

        if ($tieneClases) {
            return back()->with(
                'error',
                'No se puede quitar la materia: ya tiene docentes asignados. Bloquee la materia o el año para dejar de ofertarla.'
            );
        }

        $nombre = $cursoMateria->materia?->nombre ?? 'Materia';
        $cursoMateria->delete();

        HistorialCambio::registrar(
            'Malla curricular',
            'eliminar',
            "Materia {$nombre} retirada de {$curso->nombre}",
            ['id' => $cursoMateria->id]
        );

        return redirect()
            ->route('admin.materias.index', ['curso' => $curso->id])
            ->with('success', 'Materia retirada de la malla.');
    }

    private function asegurarPertenece(Curso $curso, CursoMateria $cursoMateria): void
    {
        abort_unless((int) $cursoMateria->curso_id === (int) $curso->id, 404);
    }

    /**
     * El nombre del año debe incluir un grado conocido para que el orden
     * escolar, la matrícula y la especialización sigan funcionando.
     */
    private function reglaGrado(): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail) {
            $normalizado = Str::upper(Str::ascii((string) $value));

            foreach (array_keys(Curso::GRADOS) as $grado) {
                if (Str::contains($normalizado, $grado)) {
                    return;
                }
            }

            $fail('El nombre debe incluir el grado: OCTAVO, NOVENO, DÉCIMO, PRIMERO, SEGUNDO o TERCERO.');
        };
    }

    /** Conserva el año seleccionado al volver al listado. */
    private function parametrosCurso(Request $request): array
    {
        return $request->filled('curso') ? ['curso' => (int) $request->input('curso')] : [];
    }
}
