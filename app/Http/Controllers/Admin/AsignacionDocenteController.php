<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClaseDocente;
use App\Models\Curso;
use App\Models\CursoMateria;
use App\Models\HistorialCambio;
use App\Models\Materia;
use App\Models\Paralelo;
use App\Models\User;
use App\Services\CargaHorariaDocente;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Distributivo docente: materias ya cargadas en la malla asignadas a docentes.
 *
 * Qué hace:
 * - Lista y busca docentes.
 * - Asigna una materia de la malla del año a un docente y paralelo.
 * - Calcula la carga horaria en períodos de 40 minutos y horas reloj.
 * - Impide borrar una asignación que ya tiene notas, asistencias o materiales.
 *
 * Conecta con: App\Models\ClaseDocente, App\Models\CursoMateria,
 * App\Models\User, App\Models\Paralelo y App\Services\CargaHorariaDocente.
 *
 * Rutas: /admin/asignaciones (admin.asignaciones.*).
 */
class AsignacionDocenteController extends Controller
{
    public function index(Request $request)
    {
        $buscar = trim((string) $request->query('buscar'));

        $docentes = User::query()
            ->docentes()
            ->withCount('clasesDocentes')
            ->when($buscar !== '', function ($query) use ($buscar) {
                $query->where(function ($q) use ($buscar) {
                    $q->where('name', 'like', "%{$buscar}%")
                        ->orWhere('apellidos', 'like', "%{$buscar}%")
                        ->orWhere('email', 'like', "%{$buscar}%")
                        ->orWhere('numero_cedula', 'like', "%{$buscar}%");
                });
            })
            ->orderBy('apellidos')
            ->orderBy('name')
            ->get();

        $docenteSeleccionado = null;

        if ($request->filled('docente')) {
            $docenteSeleccionado = $docentes->firstWhere('id', (int) $request->query('docente'));
        }

        $docenteSeleccionado ??= $docentes->first();

        if ($docenteSeleccionado) {
            $docenteSeleccionado->load([
                'clasesDocentes' => fn ($q) => $q->with(['materia', 'curso', 'paralelo'])
                    ->orderBy('curso_id')
                    ->orderBy('materia_id'),
            ]);
        }

        $cursos = Curso::query()
            ->where('activo', true)
            ->with(['malla' => fn ($q) => $q->with('materia')->orderBy('orden')->orderBy('id')])
            ->enOrdenEscolar()
            ->get();

        $mallaPorCurso = $cursos->mapWithKeys(function (Curso $curso) {
            return [$curso->id => $curso->malla
                ->filter(fn (CursoMateria $fila) => $fila->materia?->activo)
                ->map(fn (CursoMateria $fila) => [
                    'id' => $fila->materia_id,
                    'nombre' => $fila->materia->nombre,
                    'periodos' => (int) $fila->periodos_semanales,
                ])
                ->values()];
        });

        return view('admin.asignaciones.index', [
            'docentes' => $docentes,
            'docenteSeleccionado' => $docenteSeleccionado,
            'buscar' => $buscar,
            'cursos' => $cursos,
            'paralelos' => Paralelo::orderBy('nombre')->get(),
            'mallaPorCurso' => $mallaPorCurso,
            'periodosPorClave' => CursoMateria::query()->get()
                ->mapWithKeys(fn (CursoMateria $fila) => [
                    $fila->curso_id.'-'.$fila->materia_id => (int) $fila->periodos_semanales,
                ]),
            'carga' => $docenteSeleccionado ? CargaHorariaDocente::calcular($docenteSeleccionado) : null,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'curso_id' => 'required|exists:cursos,id',
            'materia_id' => 'required|exists:materias,id',
            'paralelo_id' => 'required|exists:paralelos,id',
        ], [
            'user_id.required' => 'Seleccione el docente.',
            'curso_id.required' => 'Seleccione el año de educación.',
            'materia_id.required' => 'Seleccione la materia.',
            'paralelo_id.required' => 'Seleccione el paralelo.',
        ]);

        $docente = User::query()->docentes()->find($validated['user_id']);

        if (! $docente) {
            return back()->with('error', 'La cuenta seleccionada no tiene perfil de docencia.');
        }

        $curso = Curso::findOrFail($validated['curso_id']);
        $materia = Materia::findOrFail($validated['materia_id']);

        if (! $curso->activo) {
            return back()->with('error', 'El año está bloqueado. No se pueden crear nuevas asignaciones.');
        }

        if (! $materia->activo) {
            return back()->with('error', 'La materia está bloqueada. No se pueden crear nuevas asignaciones.');
        }

        $enMalla = CursoMateria::where('curso_id', $curso->id)
            ->where('materia_id', $materia->id)
            ->exists();

        if (! $enMalla) {
            return back()->with('error', 'La materia no pertenece a la malla del año seleccionado.');
        }

        $duplicada = ClaseDocente::where('user_id', $docente->id)
            ->where('curso_id', $curso->id)
            ->where('materia_id', $materia->id)
            ->where('paralelo_id', $validated['paralelo_id'])
            ->exists();

        if ($duplicada) {
            return back()->with('error', 'El docente ya tiene asignada esa materia en ese año y paralelo.');
        }

        $clase = ClaseDocente::create([
            'user_id' => $docente->id,
            'curso_id' => $curso->id,
            'materia_id' => $materia->id,
            'paralelo_id' => $validated['paralelo_id'],
        ]);

        HistorialCambio::registrar(
            'Distributivo docente',
            'crear',
            "Materia {$materia->nombre} asignada a {$docente->nombreCompleto()} en {$curso->nombre}",
            ['id' => $clase->id]
        );

        $carga = CargaHorariaDocente::calcular($docente->fresh());

        $mensaje = 'Materia asignada al docente.';

        if ($carga['excede']) {
            $mensaje .= " Atención: la carga alcanza {$carga['horas_plantel']} h de plantel y supera el máximo de {$carga['max_horas_plantel']} h.";
        }

        return redirect()
            ->route('admin.asignaciones.index', ['docente' => $docente->id])
            ->with($carga['excede'] ? 'warning' : 'success', $mensaje);
    }

    public function update(Request $request, ClaseDocente $clase)
    {
        $validated = $request->validate([
            'paralelo_id' => 'required|exists:paralelos,id',
        ], [
            'paralelo_id.required' => 'Seleccione el paralelo.',
        ]);

        $duplicada = ClaseDocente::where('user_id', $clase->user_id)
            ->where('curso_id', $clase->curso_id)
            ->where('materia_id', $clase->materia_id)
            ->where('paralelo_id', $validated['paralelo_id'])
            ->whereKeyNot($clase->id)
            ->exists();

        if ($duplicada) {
            return back()->with('error', 'El docente ya tiene esa materia en el paralelo seleccionado.');
        }

        $clase->update(['paralelo_id' => $validated['paralelo_id']]);

        HistorialCambio::registrar(
            'Distributivo docente',
            'actualizar',
            "Paralelo actualizado para {$clase->docente?->nombreCompleto()} en {$clase->materia?->nombre}",
            ['id' => $clase->id]
        );

        return redirect()
            ->route('admin.asignaciones.index', ['docente' => $clase->user_id])
            ->with('success', 'Paralelo actualizado.');
    }

    public function destroy(ClaseDocente $clase)
    {
        $docenteId = $clase->user_id;
        $dependencias = $clase->dependencias();

        if ($dependencias !== []) {
            $detalle = collect($dependencias)
                ->map(fn (int $total, string $nombre) => "{$total} {$nombre}")
                ->implode(', ');

            return redirect()
                ->route('admin.asignaciones.index', ['docente' => $docenteId])
                ->with('error', "No se puede quitar la asignación porque conserva historial: {$detalle}. Bloquee el año o la materia para dejar de ofertarla.");
        }

        $descripcion = "Asignación retirada: {$clase->materia?->nombre} · {$clase->curso?->nombre} · {$clase->docente?->nombreCompleto()}";
        $clase->delete();

        HistorialCambio::registrar('Distributivo docente', 'eliminar', $descripcion, ['id' => $clase->id]);

        return redirect()
            ->route('admin.asignaciones.index', ['docente' => $docenteId])
            ->with('success', 'Asignación retirada. La materia ya no aparece en el distributivo del docente.');
    }

    public function updateCarga(Request $request, User $user)
    {
        $validated = $request->validate([
            'horas_contrato' => ['required', Rule::in([20, 40])],
            'periodos_atencion_padres' => 'required|integer|min:0|max:20',
            'periodos_reunion_area' => 'required|integer|min:0|max:20',
            'periodos_tutoria' => 'required|integer|min:0|max:20',
        ], [
            'horas_contrato.in' => 'El contrato debe ser de 20 o 40 horas reloj.',
            'periodos_atencion_padres.max' => 'Los períodos de atención a padres no pueden superar 20.',
            'periodos_reunion_area.max' => 'Los períodos de reunión de área no pueden superar 20.',
            'periodos_tutoria.max' => 'Los períodos de tutoría no pueden superar 20.',
        ]);

        $user->update($validated);

        HistorialCambio::registrar(
            'Distributivo docente',
            'actualizar',
            "Carga complementaria actualizada para {$user->nombreCompleto()}",
            ['id' => $user->id]
        );

        return redirect()
            ->route('admin.asignaciones.index', ['docente' => $user->id])
            ->with('success', 'Carga complementaria actualizada.');
    }
}
