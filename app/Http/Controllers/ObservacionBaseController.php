<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\NotificaObservacionAlRepresentante;
use App\Models\Alumno;
use App\Models\Observacion;
use App\Models\TipoObservacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

abstract class ObservacionBaseController extends Controller
{
    use NotificaObservacionAlRepresentante;

    /** Nombre de ruta del listado de este rol, por ejemplo 'inspeccion.observaciones.index'. */
    abstract protected function rutaIndex(): string;

    /** Prefijo de rutas para los enlaces del formulario, por ejemplo 'inspeccion.observaciones'. */
    abstract protected function prefijoRuta(): string;

    /** 'clase' para el docente (sus clases), 'curso' para inspeccion (todos los cursos). */
    abstract protected function selector(): string;

    /** Cuando es true el aviso al representante es obligatorio y no se ofrece la casilla. */
    protected function notificarEsObligatorio(): bool
    {
        return false;
    }

    protected function vistaIndex(): string
    {
        return 'observaciones.index';
    }

    protected function tiposObservacion()
    {
        return TipoObservacion::orderBy('nombre')->get();
    }

    /**
     * Historial de un alumno. Lo usan tanto el listado como la impression.
     */
    protected function historial(int $alumnoId)
    {
        return Observacion::with(['tipoObservacion', 'profesor'])
            ->where('alumno_id', $alumnoId)
            ->latest()
            ->get();
    }

    /**
     * Permite ajustar los datos antes de guardar. Quien lo reescriba (el
     * docente) puede rechazar la operacion si el alumno esta fuera de su
     * alcance. Por defecto no se restringe: inspeccion es global.
     */
    protected function validarAlcance(Request $request, Alumno $alumno): void
    {
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'alumno_id' => 'required|exists:alumnos,id',
            'tipo_observacion_id' => 'required|exists:tipos_observacion,id',
            'descripcion_texto' => 'required|string|max:1000',
            'notificar_representante' => 'boolean',
        ]);

        $alumno = Alumno::with('representante')->findOrFail($validated['alumno_id']);
        $tipo = TipoObservacion::findOrFail($validated['tipo_observacion_id']);
        $notificar = $this->notificarEsObligatorio() || $request->boolean('notificar_representante');

        $this->validarAlcance($request, $alumno);

        $aviso = DB::transaction(function () use ($request, $alumno, $tipo, $notificar) {
            $observacion = Observacion::create([
                'alumno_id' => $alumno->id,
                'tipo_observacion_id' => $tipo->id,
                'user_id' => $request->user()->id,
                'descripcion_texto' => $request->input('descripcion_texto'),
                'notificar_representante' => $notificar,
            ]);

            return $notificar ? $this->avisarAlRepresentante($request->user(), $alumno, $tipo, $observacion) : null;
        });

        $redirect = redirect()
            ->route($this->rutaIndex(), $this->parametrosTrasGuardar($request, $alumno));

        if ($notificar && !$aviso) {
            return $redirect
                ->with('success', 'Observación registrada correctamente.')
                ->with('warning', 'El alumno no tiene representante asociado, por eso no se pudo enviar el aviso.');
        }

        return $redirect->with('success', $aviso
            ? 'Observación registrada correctamente. Se notificó al representante.'
            : 'Observación registrada correctamente.');
    }

    public function print($alumnoId)
    {
        $alumno = Alumno::findOrFail($alumnoId);

        return view('observaciones.print', [
            'alumno' => $alumno,
            'observaciones' => $this->historial($alumno->id),
        ]);
    }

    /**
     * Parametros que se devuelven al listado tras guardar, para no perder el
     * filtro que el usuario habia elegido.
     */
    abstract protected function parametrosTrasGuardar(Request $request, Alumno $alumno): array;

    /** Datos que consume la vista para construir el selector y los enlaces. */
    protected function datosVista(array $extra = []): array
    {
        return array_merge([
            'tiposObservacion' => $this->tiposObservacion(),
            'selector' => $this->selector(),
            'rutaBase' => $this->prefijoRuta(),
            'notificarObligatorio' => $this->notificarEsObligatorio(),
        ], $extra);
    }
}