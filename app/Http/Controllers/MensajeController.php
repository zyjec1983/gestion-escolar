<?php

namespace App\Http\Controllers;

use App\Models\Alumno;
use App\Models\ArchivoAdjunto;
use App\Models\ClaseDocente;
use App\Models\Mensaje;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

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
            ->values()
            ->reverse()
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
        $validated = $request->validate([
            'asunto' => 'required|string|max:255',
            'cuerpo_contenido' => 'nullable|string',
            'destinatario_id' => 'required|exists:users,id',
            'contexto_estudiante_id' => 'nullable|exists:alumnos,id',
            'hilo_id' => 'nullable|exists:mensajes,id',
            'adjunto' => 'nullable|file|max:5120',
        ]);

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

        return collect(['rol' => $user->rol, 'materias' => $materiasData]);
    }
}