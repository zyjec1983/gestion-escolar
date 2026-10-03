<?php

namespace App\Http\Controllers;

use App\Models\Alumno;
use App\Models\ArchivoAdjunto;
use App\Models\ClaseDocente;
use App\Models\Curso;
use App\Models\Mensaje;
use App\Models\Paralelo;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class MensajeController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $recibidos = Mensaje::with(['emisor', 'contextoEstudiante'])
            ->where('destinatario_id', $user->id)
            ->where('borrado_destinatario', false)
            ->latest('fecha_envio')
            ->get();

        $enviados = Mensaje::with(['destinatario', 'contextoEstudiante'])
            ->where('emisor_id', $user->id)
            ->where('emisor_type', User::class)
            ->where('borrado_emisor', false)
            ->latest('fecha_envio')
            ->get();

        $hilos = $recibidos->concat($enviados)
            ->sortByDesc('fecha_envio')
            ->values()
            ->groupBy(fn (Mensaje $m) => $this->raizDelHilo($m))
            ->map(function ($grupo) use ($user) {
                $ultimo = $grupo->first();
                $interlocutor = $ultimo->destinatario_id === $user->id
                    ? $ultimo->emisor()->getResults()
                    : $ultimo->destinatario;

                return [
                    'id' => $ultimo->id,
                    'interlocutor' => $interlocutor ? trim("{$interlocutor->name} {$interlocutor->apellidos}") : 'Usuario',
                    'interlocutor_id' => $ultimo->destinatario_id === $user->id
                        ? $ultimo->emisor()->getResults()?->id
                        : $ultimo->destinatario_id,
                    'ultimo_asunto' => $ultimo->asunto,
                    'ultimo_cuerpo' => $ultimo->cuerpo_contenido,
                    'fecha' => $ultimo->fecha_envio?->format('d/m/Y H:i'),
                    'no_leidos' => $grupo->where('destinatario_id', $user->id)->where('leido', false)->count(),
                    'contexto_nombre' => $ultimo->contextoEstudiante
                        ? trim("{$ultimo->contextoEstudiante->nombres} {$ultimo->contextoEstudiante->apellidos}")
                        : null,
                ];
            })
            ->values();

        $datosHilo = null;
        if ($request->query('hilo')) {
            try {
                $datosHilo = $this->conversacionPara($user, (int) $request->query('hilo'));
            } catch (ModelNotFoundException $e) {
                $datosHilo = null;
            }
        }

        return view('mensajes.index', [
            'hilos' => $hilos,
            'compositor' => $this->compositorData($user),
            'no_leidos_total' => $recibidos->where('leido', false)->count(),
            'datosHilo' => $datosHilo,
        ]);
    }

    public function show($id, Request $request)
    {
        $user = $request->user();
        $datos = $this->conversacionPara($user, (int) $id);

        return view('mensajes.show', $datos + [
            'retorno' => route('mensajes.index'),
            'no_leidos_total' => Mensaje::where('destinatario_id', $user->id)
                ->where('borrado_destinatario', false)
                ->where('leido', false)
                ->count(),
        ]);
    }

    public function conversacion($id, Request $request)
    {
        $user = $request->user();
        $datos = $this->conversacionPara($user, (int) $id);

        return view('mensajes._conversacion', $datos + [
            'retorno' => route('mensajes.index', ['hilo' => (int) $id]),
        ]);
    }

    public function destroy(Request $request)
    {
        $user = $request->user();
        $ids = collect($request->input('ids', []))->filter()->map(fn ($id) => (int) $id)->unique();

        if ($ids->isEmpty()) {
            return redirect()->route('mensajes.index')->with('exito', 'No se seleccionó ningún mensaje.');
        }

        foreach ($ids as $id) {
            try {
                $anchors = $this->hiloDeAnclado($user, $id);
            } catch (ModelNotFoundException $e) {
                continue;
            }

            foreach ($anchors as $mensaje) {
                if ($mensaje->destinatario_id === $user->id) {
                    $mensaje->update(['borrado_destinatario' => true]);
                } elseif ($mensaje->emisor_id === $user->id && $mensaje->emisor_type === User::class) {
                    $mensaje->update(['borrado_emisor' => true]);
                }
            }
        }

        return redirect()->route('mensajes.index')->with('exito', 'Mensajes eliminados de tu bandeja.');
    }

    public function store(Request $request)
    {
        $esInspeccion = $request->user()->rol === 'INSPECCION';
        $esRespuesta = $request->filled('hilo_id');
        $alcance = $request->input('alcance');

        $validated = $request->validate([
            'asunto' => 'required|string|max:255',
            'cuerpo_contenido' => 'nullable|string',
            'destinatario_id' => $esInspeccion ? 'nullable|exists:users,id' : 'required|exists:users,id',
            'contexto_estudiante_id' => 'nullable|exists:alumnos,id',
            'hilo_id' => 'nullable|exists:mensajes,id',
            'adjunto' => 'nullable|file|max:5120',
            // El inspector difunde por alcance, salvo que esté respondiendo.
            'alcance' => $esInspeccion && ! $esRespuesta ? 'required|in:estudiante,nivel,curso,comunidad' : 'nullable|in:estudiante,nivel,curso,comunidad',
            'alumno_id' => Rule::requiredIf($alcance === 'estudiante') . '|nullable|exists:alumnos,id',
            'nivel_educativo' => Rule::requiredIf($alcance === 'nivel') . '|nullable|in:basica,bachillerato',
            'curso_id' => Rule::requiredIf($alcance === 'curso') . '|nullable|exists:cursos,id',
        ]);

        if ($esInspeccion && !empty($validated['alcance'])) {
            return $this->enviarDifusion($request, $validated);
        }

        if (!empty($validated['hilo_id'])) {
            $hiloOrigen = Mensaje::find($validated['hilo_id']);
            if (!$hiloOrigen || !$this->participaEn($request->user(), $hiloOrigen)) {
                abort(403, 'Hilo de conversación inválido.');
            }
        }


        if ($request->user()->rol === 'ESTUDIANTE') {
            $destinatario = User::find($validated['destinatario_id']);
            $docentes = $this->compositorData($request->user())
                ->get('materias', collect())
                ->flatMap(fn ($materia) => collect($materia['docentes'] ?? []))
                ->pluck('id');

            if (!$destinatario || $destinatario->rol !== 'DOCENTE' || !$docentes->contains($destinatario->id)) {
                abort(403, 'Solo puedes enviar mensajes a tus docentes.');
            }
        }

        $mensaje = Mensaje::create([
            'asunto' => $validated['asunto'],
            'cuerpo_contenido' => $validated['cuerpo_contenido'] ?? null,
            'carpeta' => 'enviados',
            'emisor_id' => $request->user()->id,
            'emisor_type' => User::class,
            'destinatario_id' => $validated['destinatario_id'],
            'hilo_id' => $validated['hilo_id'] ?? null,
            'contexto_estudiante_id' => $validated['contexto_estudiante_id'] ?? null,
            'leido' => false,
            'fecha_envio' => now(),
        ]);

        if ($request->hasFile('adjunto')) {
            $archivo = $request->file('adjunto');
            $ruta = $archivo->store('mensajes/' . $request->user()->id, 'public');

            ArchivoAdjunto::create([
                'attachable_type' => Mensaje::class,
                'attachable_id' => $mensaje->id,
                'nombre' => $archivo->getClientOriginalName(),
                'url' => Storage::url($ruta),
            ]);
        }

        $retorno = $request->input('retorno', route('mensajes.index'));
        return redirect($retorno)->with('exito', 'Mensaje enviado correctamente.');
    }

    /**
     * Envío del inspector a un grupo: un estudiante con su representante, todo
     * un curso, todo un nivel educativo o toda la comunidad. Cada destinatario
     * recibe su propio mensaje, sin copia para el emisor.
     */
    private function enviarDifusion(Request $request, array $validated)
    {
        $emisor = $request->user();
        $alcance = $validated['alcance'];

        $contextoId = null;

        if ($alcance === 'estudiante') {
            $alumno = Alumno::find($validated['alumno_id']);

            if (! $alumno) {
                return back()->withErrors(['alumno_id' => 'El estudiante no existe.'])->withInput();
            }

            $contextoId = $alumno->id;
            $destinatarios = collect([$alumno->user_id, $alumno->representante_user_id]);
        } else {
            $destinatarios = $this->destinatariosDeGrupo($emisor, $alcance, $validated);
        }

        $destinatarios = $destinatarios
            ->reject(fn ($id) => !$id || $id === $emisor->id)
            ->unique()
            ->values();

        if ($destinatarios->isEmpty()) {
            return back()->with('error', 'El alcance seleccionado no tiene destinatarios.')->withInput();
        }

        // El archivo se guarda una sola vez y se referencia desde cada copia.
        $urlAdjunto = null;
        $nombreAdjunto = null;

        if ($request->hasFile('adjunto')) {
            $archivo = $request->file('adjunto');
            $urlAdjunto = Storage::url(
                $archivo->store('mensajes/' . $emisor->id, 'public')
            );
            $nombreAdjunto = $archivo->getClientOriginalName();
        }

        $total = $destinatarios->count();

        DB::transaction(function () use ($destinatarios, $emisor, $validated, $contextoId, $urlAdjunto, $nombreAdjunto) {
            foreach ($destinatarios as $destinatarioId) {
                $mensaje = Mensaje::create([
                    'asunto' => $validated['asunto'],
                    'cuerpo_contenido' => $validated['cuerpo_contenido'] ?? null,
                    'carpeta' => 'enviados',
                    'emisor_id' => $emisor->id,
                    'emisor_type' => User::class,
                    'destinatario_id' => $destinatarioId,
                    'contexto_estudiante_id' => $contextoId,
                    'leido' => false,
                    'fecha_envio' => now(),
                ]);

                if ($urlAdjunto) {
                    ArchivoAdjunto::create([
                        'attachable_type' => Mensaje::class,
                        'attachable_id' => $mensaje->id,
                        'nombre' => $nombreAdjunto,
                        'url' => $urlAdjunto,
                    ]);
                }
            }
        });

        $retorno = $request->input('retorno', route('mensajes.index'));

        return redirect($retorno)
            ->with('exito', "Mensaje enviado a {$total} destinatario" . ($total === 1 ? '' : 's') . '.');
    }

    /**
     * Destinatarios de un envío por curso, nivel educativo o comunidad completa.
     * Siempre incluye al alumno y a su representante.
     *
     * @return Collection<int, int>
     */
    private function destinatariosDeGrupo(User $emisor, string $alcance, array $validated): Collection
    {
        if ($alcance === 'comunidad') {
            return User::where('id', '!=', $emisor->id)->pluck('id');
        }

        $query = Alumno::query()->whereNotNull('user_id');

        if ($alcance === 'nivel') {
            $esBasica = ($validated['nivel_educativo'] ?? null) === 'basica';

            $cursoIds = Curso::where('nombre', $esBasica ? 'not like' : 'like', '%BACHILLERATO%')
                ->pluck('id');

            if ($cursoIds->isEmpty()) {
                return collect();
            }

            $query->whereHas('matriculas', fn ($q) => $q->whereIn('curso_id', $cursoIds));
        } elseif ($alcance === 'curso') {
            $cursoId = (int) ($validated['curso_id'] ?? 0);

            if (! $cursoId) {
                return collect();
            }

            $query->whereHas('matriculas', fn ($q) => $q->where('curso_id', $cursoId));
        }

        // Ojo: van los usuarios, no los alumnos. El id del alumno y el de su
        // cuenta no coinciden.
        $alumnos = $query->get(['user_id', 'representante_user_id']);

        return $alumnos->pluck('user_id')
            ->merge($alumnos->pluck('representante_user_id'))
            ->filter()
            ->reject(fn ($id) => $id === $emisor->id)
            ->unique()
            ->values();
    }

    /**
     * Cursos del centro con sus paralelos y alumnos, para que el modal del
     * inspector pueda encadenar curso → paralelo → estudiante sin recargar.
     */
    private function cursosParaDifusion(): Collection
    {
        return Curso::orderBy('nombre')->get(['id', 'nombre'])->map(function (Curso $curso) {
            $paralelos = Paralelo::orderBy('nombre')->get(['id', 'nombre']);

            return [
                'id' => $curso->id,
                'nombre' => $curso->nombre,
                'es_bachillerato' => str_contains($curso->nombre, 'BACHILLERATO'),
                'total_alumnos' => Alumno::whereHas('matriculas', fn ($q) => $q->where('curso_id', $curso->id))->count(),
                'paralelos' => $paralelos->map(function ($paralelo) use ($curso) {
                    $alumnos = Alumno::with('representante')
                        ->whereHas('matriculas', fn ($q) => $q
                            ->where('curso_id', $curso->id)
                            ->where('paralelo_id', $paralelo->id))
                        ->orderBy('apellidos')->orderBy('nombres')
                        ->get();

                    return [
                        'id' => $paralelo->id,
                        'nombre' => $paralelo->nombre,
                        'alumnos' => $alumnos->map(fn ($a) => [
                            'id' => $a->id,
                            'label' => trim($a->apellidos . ' ' . $a->nombres),
                            'estudiante_user_id' => $a->user_id,
                            'representante_id' => $a->representante_user_id,
                            'representante_nombre' => $a->representante
                                ? nombreCorto($a->representante->name)
                                : null,
                        ])->values(),
                    ];
                })->values(),
            ];
        })->values();
    }

    private function esEmisor(User $user, Mensaje $mensaje): bool
    {
        return $mensaje->emisor_id === $user->id && $mensaje->emisor_type === User::class;
    }

    private function participaEn(User $user, Mensaje $mensaje): bool
    {
        return $mensaje->destinatario_id === $user->id || $this->esEmisor($user, $mensaje);
    }

    private function raizDelHilo(Mensaje $mensaje): int
    {
        return $mensaje->hilo_id ? (int) $mensaje->hilo_id : (int) $mensaje->id;
    }

    private function conversacionPara(User $user, int $id): array
    {
        $mensaje = Mensaje::with(['emisor', 'destinatario', 'contextoEstudiante'])->findOrFail($id);

        if (!$this->participaEn($user, $mensaje)) {
            abort(403, 'No tienes acceso a este mensaje.');
        }

        $interlocutorId = $user->id === $mensaje->destinatario_id
            ? $mensaje->emisor()->getResults()?->id
            : $mensaje->destinatario_id;

        $hilo = $this->hiloDeAnclado($user, $id);

        foreach ($hilo->where('destinatario_id', $user->id)->where('leido', false) as $noLeido) {
            $noLeido->update(['leido' => true, 'carpeta' => 'leidos']);
        }

        return [
            'hilo' => $hilo,
            'interlocutor_id' => $interlocutorId,
            'contexto_estudiante_id' => $mensaje->contexto_estudiante_id,
            'alumno_contexto' => $mensaje->contextoEstudiante,
            'anchor_id' => $id,
            'hilo_id' => $this->raizDelHilo($mensaje),
        ];
    }

    private function hiloDeAnclado(User $user, int $id): Collection
    {
        $mensaje = Mensaje::with(['emisor', 'destinatario', 'contextoEstudiante'])->findOrFail($id);

        $raiz = $this->raizDelHilo($mensaje);
        $interlocutorId = $user->id === $mensaje->destinatario_id
            ? $mensaje->emisor()->getResults()?->id
            : $mensaje->destinatario_id;

        return Mensaje::with(['emisor', 'destinatario', 'contextoEstudiante', 'archivosAdjuntos'])
            ->where(function ($q) use ($raiz) {
                $q->where('id', $raiz)->orWhere('hilo_id', $raiz);
            })
            ->where(function ($q) use ($user, $interlocutorId) {
                $q->where(fn ($sub) => $sub->where('emisor_id', $user->id)->where('destinatario_id', $interlocutorId)->where('borrado_emisor', false))
                    ->orWhere(fn ($sub) => $sub->where('emisor_id', $interlocutorId)->where('destinatario_id', $user->id)->where('borrado_destinatario', false));
            })
            ->orderBy('fecha_envio')
            ->get();
    }

    public function compositorData(User $user): Collection
    {
        if ($user->rol === 'DOCENTE') {
            $clases = ClaseDocente::with(['curso', 'paralelo', 'materia'])
                ->where('user_id', $user->id)
                ->get();

            $clasesData = $clases->map(function ($clase) {
                $matriculas = $clase->curso->matriculas()
                    ->where('paralelo_id', $clase->paralelo_id)
                    ->with(['alumno.usuarioEstudiante', 'alumno.representante'])
                    ->get();

                $estudiantes = $matriculas->map(function ($matricula) {
                    $alumno = $matricula->alumno;

                    return [
                        'id' => $alumno->id,
                        'label' => trim($alumno->apellidos . ' ' . $alumno->nombres),
                        'estudiante_user_id' => $alumno->usuarioEstudiante?->id,
                        'representante_id' => $alumno->representante?->id,
                        'representante_nombre' => $alumno->representante
                            ? trim($alumno->representante->name . ' ' . $alumno->representante->apellidos)
                            : null,
                    ];
                })->sortBy('label')->values();

                return [
                    'id' => $clase->id,
                    'label' => trim(($clase->curso->nombre ?? '') . ' ' . ($clase->paralelo->nombre ?? '') . ' - ' . ($clase->materia->nombre ?? '')),
                    'estudiantes' => $estudiantes,
                ];
            })->values();

            return collect(['rol' => 'DOCENTE', 'clases' => $clasesData]);
        }

        $alumnos = $user->rol === 'ESTUDIANTE'
            ? $user->estudiantes()->with('matriculas')->get()
            : $user->representados()->with('matriculas')->get();

        $docentes = collect();

        foreach ($alumnos as $alumno) {
            $pares = $alumno->matriculas->map(fn ($m) => [$m->curso_id, $m->paralelo_id])->unique();

            foreach ($pares as [$cursoId, $paraleloId]) {
                $clases = ClaseDocente::with(['docente', 'materia'])
                    ->where('curso_id', $cursoId)
                    ->where('paralelo_id', $paraleloId)
                    ->get();

                foreach ($clases as $clase) {
                    $docentes->push([
                        'materia_id' => $clase->materia_id,
                        'materia_nombre' => $clase->materia?->nombre,
                        'docente_id' => $clase->user_id,
                        'docente_nombre' => $clase->docente
                            ? trim($clase->docente->name . ' ' . $clase->docente->apellidos)
                            : 'Docente',
                        'contexto_estudiante_id' => $alumno->id,
                    ]);
                }
            }
        }

        $materiasData = $docentes->groupBy('materia_id')->map(function ($items, $materiaId) {
            return [
                'id' => $materiaId,
                'nombre' => $items->first()['materia_nombre'] ?? 'Materia',
                'docentes' => $items->unique('docente_id')->map(fn ($d) => [
                    'id' => $d['docente_id'],
                    'nombre' => $d['docente_nombre'],
                    'contexto_estudiante_id' => $d['contexto_estudiante_id'],
                ])->values(),
            ];
        })->values();

        if ($user->rol === 'INSPECCION') {
            return collect(['rol' => 'INSPECCION', 'cursos' => $this->cursosParaDifusion()]);
        }

        return collect(['rol' => $user->rol, 'materias' => $materiasData]);
    }
}
