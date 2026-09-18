<?php

namespace App\Http\Controllers;

use App\Models\Alumno;
use App\Models\ArchivoAdjunto;
use App\Models\ClaseDocente;
use App\Models\Mensaje;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class MensajeController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $carpeta = $request->query('carpeta', 'recibidos');

        $recibidos = Mensaje::with(['emisor', 'contextoEstudiante'])
            ->where('destinatario_id', $user->id)
            ->latest('fecha_envio')
            ->get();

        $enviados = Mensaje::with(['destinatario', 'contextoEstudiante'])
            ->where('emisor_id', $user->id)
            ->where('emisor_type', User::class)
            ->latest('fecha_envio')
            ->get();

        $hilos = ($carpeta === 'enviados' ? $enviados : $recibidos)
            ->groupBy(fn (Mensaje $m) => $carpeta === 'enviados'
                ? $m->destinatario_id
                : $m->emisor()->getResults()?->id . '-' . ($m->contexto_estudiante_id ?? ''))
            ->map(function ($grupo, $clave) use ($carpeta, $user) {
                $ultimo = $grupo->first();
                $interlocutor = $carpeta === 'enviados'
                    ? $ultimo->destinatario
                    : $ultimo->emisor()->getResults();

                return [
                    'id' => $ultimo->id,
                    'interlocutor' => $interlocutor ? "{$interlocutor->name} {$interlocutor->apellidos}" : 'Usuario',
                    'interlocutor_id' => $carpeta === 'enviados' ? $ultimo->destinatario_id : $ultimo->emisor()->getResults()?->id,
                    'ultimo_asunto' => $ultimo->asunto,
                    'ultimo_cuerpo' => $ultimo->cuerpo_contenido,
                    'fecha' => $ultimo->fecha_envio?->format('d/m/Y H:i'),
                    'no_leidos' => $carpeta === 'enviados' ? 0 : $grupo->where('leido', false)->count(),
                    'contexto_nombre' => $ultimo->contextoEstudiante
                        ? "{$ultimo->contextoEstudiante->nombres} {$ultimo->contextoEstudiante->apellidos}"
                        : null,
                ];
            })
            ->values();

        return view('mensajes.index', [
            'hilos' => $hilos,
            'carpeta' => $carpeta,
            'contactos' => $this->contactosDisponibles($user),
            'no_leidos_total' => $recibidos->where('leido', false)->count(),
        ]);
    }

    public function show($id, Request $request)
    {
        $user = $request->user();
        $mensaje = Mensaje::with(['emisor', 'destinatario', 'contextoEstudiante'])->findOrFail($id);

        if ($user->id !== $mensaje->destinatario_id && !$this->esEmisor($user, $mensaje)) {
            abort(403, 'No tienes acceso a este mensaje.');
        }

        $interlocutorId = $user->id === $mensaje->destinatario_id
            ? $mensaje->emisor()->getResults()?->id
            : $mensaje->destinatario_id;

        $hilo = Mensaje::with(['emisor', 'destinatario', 'contextoEstudiante', 'archivosAdjuntos'])
            ->where(function ($q) use ($user, $interlocutorId) {
                $q->where(fn ($sub) => $sub->where('emisor_id', $user->id)->where('destinatario_id', $interlocutorId))
                    ->orWhere(fn ($sub) => $sub->where('emisor_id', $interlocutorId)->where('destinatario_id', $user->id));
            })
            ->orderBy('fecha_envio')
            ->get();

        foreach ($hilo->where('destinatario_id', $user->id)->where('leido', false) as $noLeido) {
            $noLeido->update(['leido' => true, 'carpeta' => 'leidos']);
        }

        return view('mensajes.show', [
            'hilo' => $hilo,
            'interlocutor_id' => $interlocutorId,
            'contexto_estudiante_id' => $mensaje->contexto_estudiante_id,
            'alumno_contexto' => $mensaje->contextoEstudiante,
            'contactos' => $this->contactosDisponibles($user),
            'no_leidos_total' => Mensaje::where('destinatario_id', $user->id)->where('leido', false)->count(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'asunto' => 'required|string|max:255',
            'cuerpo_contenido' => 'nullable|string',
            'destinatario_id' => 'required|exists:users,id',
            'contexto_estudiante_id' => 'nullable|exists:alumnos,id',
            'adjunto' => 'nullable|file|max:10240',
        ]);

        $mensaje = Mensaje::create([
            'asunto' => $validated['asunto'],
            'cuerpo_contenido' => $validated['cuerpo_contenido'] ?? null,
            'carpeta' => 'enviados',
            'emisor_id' => $request->user()->id,
            'emisor_type' => User::class,
            'destinatario_id' => $validated['destinatario_id'],
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

    public function contactosDisponibles(User $user): Collection
    {
        if ($user->rol === 'DOCENTE') {
            $clases = ClaseDocente::with(['curso', 'paralelo'])->where('user_id', $user->id)->get();
            $contactos = collect();

            foreach ($clases as $clase) {
                $matriculas = $clase->curso->matriculas()
                    ->where('paralelo_id', $clase->paralelo_id)
                    ->with(['alumno.usuarioEstudiante', 'alumno.representante'])
                    ->get();

                foreach ($matriculas as $matricula) {
                    $alumno = $matricula->alumno;

                    if ($alumno->usuarioEstudiante) {
                        $contactos->push([
                            'destinatario_id' => $alumno->usuarioEstudiante->id,
                            'contexto_estudiante_id' => $alumno->id,
                            'label' => "{$alumno->apellidos} {$alumno->nombres} (Estudiante)",
                        ]);
                    }
                    if ($alumno->representante) {
                        $contactos->push([
                            'destinatario_id' => $alumno->representante->id,
                            'contexto_estudiante_id' => $alumno->id,
                            'label' => "Representante de {$alumno->apellidos} {$alumno->nombres}",
                        ]);
                    }
                }
            }

            return $contactos->unique(fn ($c) => $c['destinatario_id'] . '-' . $c['contexto_estudiante_id'])->values();
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
                        'destinatario_id' => $clase->user_id,
                        'contexto_estudiante_id' => $alumno->id,
                        'label' => "{$clase->docente->name} {$clase->docente->apellidos} ({$clase->materia->nombre})",
                    ]);
                }
            }
        }

        return $docentes->unique(fn ($c) => $c['destinatario_id'] . '-' . $c['contexto_estudiante_id'])->values();
    }
}