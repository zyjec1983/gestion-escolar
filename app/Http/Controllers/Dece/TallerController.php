<?php

namespace App\Http\Controllers\Dece;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Dece\Concerns\AutorizaDece;
use App\Models\Alumno;
use App\Models\Curso;
use App\Models\DeceAuditoria;
use App\Models\DeceTaller;
use App\Models\DeceTallerAsistente;
use App\Models\User;
use App\Services\Dece\RegistrarAuditoria;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Talleres colectivos de prevencion y orientacion, con control de asistencia
 * para estudiantes, representantes o docentes.
 */
class TallerController extends Controller
{
    use AutorizaDece;

    public function __construct(private readonly RegistrarAuditoria $auditoria) {}

    public function index(Request $request)
    {
        $filtros = $request->validate([
            'buscar' => ['nullable', 'string', 'max:100'],
            'tipo' => ['nullable', 'string', Rule::in(array_keys(DeceTaller::TIPOS))],
            'dirigido_a' => ['nullable', 'string', Rule::in(array_keys(DeceTaller::DIRIGIDOS))],
            'desde' => ['nullable', 'date'],
            'hasta' => ['nullable', 'date'],
        ]);

        $talleres = DeceTaller::with(['curso', 'creador'])
            ->withCount('asistentes')
            ->buscar($filtros['buscar'] ?? null)
            ->when($filtros['tipo'] ?? null, fn ($query, string $tipo) => $query->where('tipo', $tipo))
            ->when($filtros['dirigido_a'] ?? null, fn ($query, string $dirigido) => $query->where('dirigido_a', $dirigido))
            ->when($filtros['desde'] ?? null, fn ($query, string $desde) => $query->whereDate('fecha', '>=', $desde))
            ->when($filtros['hasta'] ?? null, fn ($query, string $hasta) => $query->whereDate('fecha', '<=', $hasta))
            ->orderByDesc('fecha')
            ->paginate(15)
            ->withQueryString();

        $tipos = DeceTaller::TIPOS;
        $dirigidos = DeceTaller::DIRIGIDOS;

        return view('dece.talleres.index', compact('talleres', 'tipos', 'dirigidos', 'filtros'));
    }

    public function create()
    {
        $cursos = Curso::enOrdenEscolar()->get(['id', 'nombre']);
        $tipos = DeceTaller::TIPOS;
        $dirigidos = DeceTaller::DIRIGIDOS;

        return view('dece.talleres.create', compact('cursos', 'tipos', 'dirigidos'));
    }

    public function store(Request $request)
    {
        $this->soloGestion($request);

        $validated = $request->validate([
            'titulo' => ['required', 'string', 'max:255'],
            'tipo' => ['required', 'string', Rule::in(array_keys(DeceTaller::TIPOS))],
            'descripcion' => ['nullable', 'string', 'max:5000'],
            'fecha' => ['required', 'date'],
            'dirigido_a' => ['required', 'string', Rule::in(array_keys(DeceTaller::DIRIGIDOS))],
            'curso_id' => ['nullable', 'integer', 'exists:cursos,id'],
            'facilitador' => ['nullable', 'string', 'max:255'],
            'lugar' => ['nullable', 'string', 'max:255'],
        ]);

        $taller = DeceTaller::create($validated + ['created_by' => $request->user()->id]);

        $this->auditoria->registrar(
            $request,
            $taller,
            DeceAuditoria::ACCION_CREACION,
            'Taller registrado: '.$taller->titulo.'.',
            null,
            $taller->only(['tipo', 'fecha', 'dirigido_a', 'curso_id'])
        );

        return redirect()
            ->route('dece.talleres.show', $taller)
            ->with('success', 'Taller registrado. Ya puede marcar la asistencia.');
    }

    public function show(Request $request, DeceTaller $taller)
    {
        $taller->load(['curso', 'creador', 'asistentes.alumno', 'asistentes.usuario']);

        $this->auditoria->registrar(
            $request,
            $taller,
            DeceAuditoria::ACCION_CONSULTA,
            'Consulta del taller y su asistencia.',
            null,
            null,
            null
        );

        $candidatos = $this->candidatos($taller);
        $asistentesAlumnos = $taller->asistentes->whereNotNull('alumno_id')->pluck('alumno_id')->all();
        $asistentesUsuarios = $taller->asistentes->whereNotNull('user_id')->pluck('user_id')->all();

        return view('dece.talleres.show', compact(
            'taller',
            'candidatos',
            'asistentesAlumnos',
            'asistentesUsuarios'
        ));
    }

    public function registrarAsistencia(Request $request, DeceTaller $taller)
    {
        $this->soloGestion($request);

        $validated = $request->validate([
            'alumno_ids' => ['nullable', 'array'],
            'alumno_ids.*' => ['integer', 'exists:alumnos,id'],
            'user_ids' => ['nullable', 'array'],
            'user_ids.*' => ['integer', 'exists:users,id'],
        ]);

        $alumnoIds = $validated['alumno_ids'] ?? [];
        $userIds = $validated['user_ids'] ?? [];

        $taller->asistentes()->delete();

        foreach ($alumnoIds as $alumnoId) {
            DeceTallerAsistente::create([
                'taller_id' => $taller->id,
                'alumno_id' => $alumnoId,
                'asistio' => true,
            ]);
        }

        foreach ($userIds as $userId) {
            DeceTallerAsistente::create([
                'taller_id' => $taller->id,
                'user_id' => $userId,
                'asistio' => true,
            ]);
        }

        $this->auditoria->registrar(
            $request,
            $taller,
            DeceAuditoria::ACCION_ACTUALIZACION,
            'Asistencia al taller actualizada: '.(count($alumnoIds) + count($userIds)).' asistentes.',
            null,
            ['asistentes' => count($alumnoIds) + count($userIds)]
        );

        return back()->with('success', 'Asistencia al taller guardada.');
    }

    /**
     * Personas que pueden figurar como asistentes segun el publico objetivo.
     *
     * @return array<int, array{clave: string, nombre: string}>
     */
    private function candidatos(DeceTaller $taller): array
    {
        if ($taller->dirigido_a === 'docentes') {
            return User::where('rol', 'DOCENTE')
                ->orderBy('apellidos')
                ->orderBy('name')
                ->get()
                ->map(fn (User $docente) => [
                    'clave' => 'user:'.$docente->id,
                    'nombre' => $docente->nombreCompleto(),
                ])
                ->all();
        }

        $alumnos = Alumno::query()
            ->with('representante')
            ->when($taller->curso_id, fn ($query, int $cursoId) => $query->whereHas(
                'matriculas',
                fn ($matricula) => $matricula->where('curso_id', $cursoId)
            ))
            ->orderBy('apellidos')
            ->orderBy('nombres')
            ->get();

        if ($taller->dirigido_a === 'representantes') {
            return $alumnos
                ->pluck('representante')
                ->filter()
                ->unique('id')
                ->sortBy('apellidos')
                ->map(fn (User $representante) => [
                    'clave' => 'user:'.$representante->id,
                    'nombre' => $representante->nombreCompleto(),
                ])
                ->values()
                ->all();
        }

        return $alumnos
            ->map(fn (Alumno $alumno) => [
                'clave' => 'alumno:'.$alumno->id,
                'nombre' => $alumno->nombreCompleto(),
            ])
            ->all();
    }
}
