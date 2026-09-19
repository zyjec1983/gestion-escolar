<?php

namespace App\Http\Controllers;

use App\Http\Resources\InicioClaseResource;
use App\Models\Alumno;
use App\Models\ClaseDocente;
use App\Models\Foro;
use App\Models\ForoIntervencion;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class ForoController extends Controller
{
    public function index($id, Request $request)
    {
        $clase = ClaseDocente::with(['curso', 'paralelo', 'materia'])->findOrFail($id);
        $this->verificarAcceso($clase, $request->user());

        $data = $this->dataAula($clase, $request, 'foros');
        $data['clase'] = $clase;
        $data['foros'] = $clase->foros()
            ->withCount('intervenciones')
            ->latest()
            ->get()
            ->map(function (Foro $foro) {
                $foro->estado = $foro->estado();
                return $foro;
            });

        return view('aula.foros.index', $data);
    }

    public function create($id, Request $request)
    {
        $clase = ClaseDocente::with(['curso', 'paralelo', 'materia'])->findOrFail($id);
        $this->verificarDocente($request->user(), $clase);

        $data = $this->dataAula($clase, $request, 'foros');
        $data['clase'] = $clase;

        return view('aula.foros.form', $data);
    }

    public function store($id, Request $request)
    {
        $clase = ClaseDocente::findOrFail($id);
        $this->verificarDocente($request->user(), $clase);

        $validated = $request->validate([
            'titulo' => 'required|string|max:255',
            'pregunta' => 'required|string|max:5000',
            'fecha_inicio' => 'required|date',
            'fecha_cierre' => 'required|date|after:fecha_inicio',
            'links' => 'nullable|array|max:3',
            'links.*.nombre' => 'nullable|string|max:100',
            'links.*.url' => 'nullable|url|max:2048',
        ]);

        $links = collect($validated['links'] ?? [])
            ->filter(fn ($link) => !empty(trim((string) ($link['url'] ?? ''))))
            ->map(fn ($link) => [
                'nombre' => trim((string) ($link['nombre'] ?? '')),
                'url' => trim((string) $link['url']),
            ])
            ->values()
            ->all();

        Foro::create([
            'clase_docente_id' => $clase->id,
            'titulo' => $validated['titulo'],
            'pregunta' => $validated['pregunta'],
            'fecha_inicio' => $validated['fecha_inicio'],
            'fecha_cierre' => $validated['fecha_cierre'],
            'links' => $links,
        ]);

        return redirect()->route('clases.foros', $clase->id)->with('success', 'Tema de foro creado correctamente.');
    }

    public function show(Foro $foro, Request $request)
    {
        $clase = $foro->claseDocente()->with(['curso', 'paralelo', 'materia'])->firstOrFail();
        $this->verificarAcceso($clase, $request->user());

        $intervenciones = $foro->intervenciones()
            ->with('user')
            ->orderBy('created_at')
            ->get();

        $esDocente = $request->user()->rol === 'DOCENTE';

        $data = $this->dataAula($clase, $request, 'foros');
        $data['clase'] = $clase;
        $data['foro'] = $foro;
        $data['estado'] = $foro->estado();
        $data['es_docente'] = $esDocente;

        $data['principales'] = $intervenciones
            ->where('tipo', 'respuesta')
            ->sortByDesc('created_at')
            ->values();

        $data['replicas_por_padre'] = $intervenciones
            ->where('tipo', 'replica')
            ->groupBy('padre_id');

        $data['participacion'] = $this->resumenParticipacion($clase, $intervenciones);

        $usuarioId = $request->user()->id;
        $data['ya_respondio'] = (bool) $intervenciones->first(fn ($i) => $i->tipo === 'respuesta' && $i->user_id === $usuarioId);
        $data['ya_concluyo'] = (bool) $intervenciones->first(fn ($i) => $i->tipo === 'conclusion' && $i->user_id === $usuarioId);
        $data['mi_conclusion'] = optional($intervenciones->first(fn ($i) => $i->tipo === 'conclusion' && $i->user_id === $usuarioId))->cuerpo;

        return view('aula.foros.show', $data);
    }

    public function storeIntervencion(Foro $foro, Request $request)
    {
        $clase = $foro->claseDocente()->with(['curso', 'paralelo', 'materia'])->firstOrFail();
        $this->verificarAcceso($clase, $request->user());

        abort_if($request->user()->rol !== 'ESTUDIANTE', 403, 'Solo los estudiantes pueden participar en el foro.');

        $validated = $request->validate([
            'tipo' => 'required|in:respuesta,replica,conclusion',
            'cuerpo' => 'required|string|min:3|max:5000',
            'padre_id' => 'nullable|integer',
        ]);

        $cuerpo = trim($validated['cuerpo']);
        if ($cuerpo === '') {
            return back()->with('error', 'Escribe tu intervenciÃ³n antes de publicarla.');
        }

        $tipo = $validated['tipo'];

        $estado = $foro->estado();
        if ($estado !== 'activo') {
            $mensaje = $estado === 'no_iniciado'
                ? 'El foro aÃºn no ha iniciado. Espera la fecha de apertura para participar.'
                : 'El foro ya cerrÃ³: no se aceptan mÃ¡s intervenciones.';

            return redirect()->route('foros.show', $foro->id)->with('error', $mensaje);
        }

        $padreId = null;

        if ($tipo === 'replica') {
            $padre = ForoIntervencion::where('foro_id', $foro->id)
                ->where('tipo', 'respuesta')
                ->find((int) ($validated['padre_id'] ?? 0));

            if (!$padre) {
                return back()->with('error', 'La intervenciÃ³n a la que intentas responder no existe.');
            }

            $padreId = $padre->id;
        } else {
            $yaExiste = ForoIntervencion::where('foro_id', $foro->id)
                ->where('user_id', $request->user()->id)
                ->where('tipo', $tipo)
                ->exists();

            if ($yaExiste) {
                $mensaje = $tipo === 'respuesta'
                    ? 'Ya publicaste tu respuesta principal al docente. Solo se permite una.'
                    : 'Ya guardaste tu conclusiÃ³n final. Solo se permite una por estudiante.';

                return back()->with('error', $mensaje);
            }
        }

        ForoIntervencion::create([
            'foro_id' => $foro->id,
            'user_id' => $request->user()->id,
            'tipo' => $tipo,
            'padre_id' => $padreId,
            'cuerpo' => $cuerpo,
        ]);

        $mensaje = match ($tipo) {
            'conclusion' => 'ConclusiÃ³n final guardada correctamente.',
            default => 'Tu intervenciÃ³n fue publicada en el foro.',
        };

        return redirect()->route('foros.show', $foro->id)->with('success', $mensaje);
    }

    public function destroy(Foro $foro, Request $request)
    {
        $clase = $foro->claseDocente()->firstOrFail();
        $this->verificarDocente($request->user(), $clase);

        $foro->delete();

        return redirect()->route('clases.foros', $clase->id)->with('success', 'Tema de foro eliminado.');
    }

    private function resumenParticipacion(ClaseDocente $clase, Collection $intervenciones): Collection
    {
        $alumnos = $clase->curso->matriculas()
            ->where('paralelo_id', $clase->paralelo_id)
            ->with('alumno')
            ->get()
            ->map(function ($matricula) {
                return [
                    'nombre' => trim((string) $matricula->alumno->apellidos . ' ' . $matricula->alumno->nombres),
                    'user_id' => $matricula->alumno->user_id,
                ];
            });

        return $alumnos->map(function ($alumno) use ($intervenciones) {
            $dea = $intervenciones->where('user_id', $alumno['user_id']);

            $alumno['total'] = $dea->count();
            $alumno['respuestas'] = $dea->where('tipo', 'respuesta')->count();
            $alumno['replicas'] = $dea->where('tipo', 'replica')->count();
            $alumno['conclusiones'] = $dea->where('tipo', 'conclusion')->count();

            return $alumno;
        })->sortByDesc('total')->values();
    }

    private function dataAula(ClaseDocente $clase, Request $request, string $activo): array
    {
        $data = (new InicioClaseResource($clase))->resolve($request);

        $data['menu_aula_virtual'] = collect($data['menu_aula_virtual'])
            ->map(fn ($item) => $item + ['active' => ($item['key'] ?? '') === $activo])
            ->all();

        if ($request->user()->rol === 'REPRESENTANTE') {
            $data['menu_aula_virtual'] = collect($data['menu_aula_virtual'])
                ->filter(fn ($item) => in_array($item['key'] ?? '', ['grupos', 'materiales', 'mensajes', 'citas']))
                ->values()
                ->all();
        }

        $data['layout'] = 'aula';
        $data['es_docente'] = $request->user()->rol === 'DOCENTE';
        $data['url_volver'] = $request->user()->rol === 'REPRESENTANTE'
            ? route('representante.agendas')
            : '/clases';

        return $data;
    }

    private function alumnosDelUsuario(User $user): Collection
    {
        return match ($user->rol) {
            'ESTUDIANTE' => Alumno::with('matriculas')->where('user_id', $user->id)->get(),
            'REPRESENTANTE' => $user->representados()->with('matriculas')->get(),
            default => collect(),
        };
    }

    public function contadores($id, Request $request)
    {
        $clase = ClaseDocente::with(['curso', 'paralelo', 'materia'])->findOrFail($id);
        $this->verificarAcceso($clase, $request->user());

        $contadores = $clase->foros()
            ->withCount('intervenciones')
            ->get()
            ->mapWithKeys(fn ($foro) => [$foro->id => $foro->intervenciones_count]);

        return response()->json($contadores);
    }

    private function verificarAcceso(ClaseDocente $clase, User $user): void
    {
        if ($user->rol === 'DOCENTE') {
            abort_if($clase->user_id !== $user->id, 403, 'No tienes acceso a esta clase.');
            return;
        }

        if ($user->rol === 'ADMIN') {
            return;
        }

        $tieneAcceso = $this->alumnosDelUsuario($user)->contains(function (Alumno $alumno) use ($clase) {
            return $alumno->matriculas->contains(
                fn ($matricula) => $matricula->curso_id === $clase->curso_id && $matricula->paralelo_id === $clase->paralelo_id
            );
        });

        abort_unless($tieneAcceso, 403, 'No tienes acceso a esta clase.');
    }

    private function verificarDocente(User $user, ClaseDocente $clase): void
    {
        abort_if($user->rol !== 'DOCENTE', 403, 'Solo el docente puede realizar esta acciÃ³n.');
        abort_if($clase->user_id !== $user->id, 403, 'No tienes acceso a esta clase.');
    }
}