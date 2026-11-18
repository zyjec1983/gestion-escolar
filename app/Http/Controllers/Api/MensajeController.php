<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\MensajeCollection;
use App\Models\Alumno;
use App\Models\Mensaje;
use Illuminate\Http\Request;

class MensajeController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $carpeta = $request->query('carpeta', 'no_leidos');

        $mensajes = Mensaje::with(['emisor', 'contextoEstudiante'])
            ->where('destinatario_id', $user->id)
            ->when($carpeta !== 'todos', function ($q) use ($carpeta) {
                if ($carpeta === 'no_leidos') {
                    $q->where('leido', false);
                } else {
                    $q->where('carpeta', $carpeta);
                }
            })
            ->latest('fecha_envio')
            ->paginate(20);

        return new MensajeCollection($mensajes);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $esDirectivo = in_array($user->rol, ['RECTORA', 'VICERRECTORA'], true);

        $validated = $request->validate([
            'asunto' => 'required|string|max:255',
            'cuerpo_contenido' => 'nullable|string',
            'destinatario_id' => $esDirectivo ? 'nullable|exists:users,id' : 'required|exists:users,id',
            'alumno_id' => 'nullable|exists:alumnos,id',
            'contexto_estudiante_id' => 'nullable|exists:alumnos,id',
        ]);

        if ($esDirectivo) {
            // El destinatario se deriva del estudiante; nunca se acepta una
            // cuenta de estudiante como destino de un mensaje directivo.
            $alumno = Alumno::with('representante')->find(
                $validated['alumno_id'] ?? $validated['contexto_estudiante_id'] ?? null
            );

            $destinatario = $alumno?->representante;

            if (! $destinatario || $destinatario->rol === 'ESTUDIANTE') {
                return response()->json([
                    'message' => 'Solo puede enviar mensajes al representante legal de un estudiante.',
                ], 422);
            }

            $validated['destinatario_id'] = $destinatario->id;
            $validated['contexto_estudiante_id'] = $alumno->id;
        }

        $mensaje = Mensaje::create([
            'asunto' => $validated['asunto'],
            'cuerpo_contenido' => $validated['cuerpo_contenido'],
            'carpeta' => 'enviados',
            'emisor_id' => $user->id,
            'emisor_type' => get_class($user),
            'destinatario_id' => $validated['destinatario_id'],
            'contexto_estudiante_id' => $validated['contexto_estudiante_id'] ?? null,
            'fecha_envio' => now(),
        ]);

        return response()->json($mensaje, 201);
    }

    public function show($id)
    {
        $mensaje = Mensaje::with(['emisor', 'contextoEstudiante'])->findOrFail($id);
        $mensaje->update(['leido' => true, 'carpeta' => 'leidos']);

        return response()->json($mensaje);
    }
}
