<?php

namespace App\Http\Controllers\Representante;

use App\Http\Controllers\Concerns\ValidaDatosAlumno;
use App\Http\Controllers\Controller;
use App\Models\Alumno;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Formulario de datos para representantes ya matriculados.
 *
 * Qué hace:
 * - Lista los estudiantes vinculados a la cuenta (como representante o como
 *   estudiante, porque la cuenta familiar usa la cédula del alumno).
 * - Permite completar y corregir la ficha: datos del alumno, salud, domicilio,
 *   padre, madre, representante alterno y facturador.
 * - Deja que el representante actualice sus propios datos de contacto.
 *
 * Conecta con: App\Models\Alumno, App\Models\User, el trait
 * ValidaDatosAlumno y la vista compartida secretaria.actualizacion.edit.
 *
 * Rutas:
 * - GET  /mis-datos              (representante.datos.index)
 * - GET  /mis-datos/{alumno}     (representante.datos.edit)
 * - PUT  /mis-datos/{alumno}     (representante.datos.update)
 */
class DatosController extends Controller
{
    use ValidaDatosAlumno;

    /** Listado de estudiantes que la cuenta puede actualizar. */
    public function index(Request $request)
    {
        $user = $request->user();

        $alumnos = Alumno::query()
            ->where(function ($query) use ($user) {
                $query->where('representante_user_id', $user->id)
                    ->orWhere('user_id', $user->id);
            })
            ->with(['matriculas.curso', 'matriculas.paralelo'])
            ->orderBy('apellidos')
            ->orderBy('nombres')
            ->get();

        return view('representante.datos.index', [
            'alumnos' => $alumnos,
            'representante' => $user,
        ]);
    }

    /** Ficha editable de un estudiante propio. */
    public function edit(Request $request, Alumno $alumno)
    {
        $this->autorizar($request, $alumno);

        $alumno->load(['matriculas.curso', 'matriculas.paralelo']);

        return view('secretaria.actualizacion.edit', [
            'alumno' => $alumno,
            'representante' => $request->user(),
            'representanteActualId' => $request->user()->id,
            'representanteBusqueda' => '',
            'representantesLista' => collect(),
            'cambios' => collect(),
            'modoRepresentante' => true,
            'accionFormulario' => route('representante.datos.update', $alumno->id),
            'volverUrl' => route('representante.datos.index'),
        ]);
    }

    /** Guarda la ficha del alumno y los datos de contacto del representante. */
    public function update(Request $request, Alumno $alumno)
    {
        $this->autorizar($request, $alumno);

        $this->normalizarMayusculasRequest($request);

        $user = $request->user();

        $validated = $request->validate(array_merge($this->reglasAlumno(), [
            'representante_nombres' => 'required|string|max:255',
            'representante_apellidos' => 'nullable|string|max:255',
            'representante_telefono' => 'nullable|string|max:20',
            'representante_email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
        ]), [
            'representante_nombres.required' => 'Escriba su nombre como representante legal.',
            'representante_email.required' => 'Escriba un correo de contacto.',
            'representante_email.unique' => 'Ese correo ya está registrado en otra cuenta.',
        ]);

        DB::transaction(function () use ($alumno, $user, $validated) {
            $alumno->update($this->datosAlumno($validated));

            $user->update([
                'name' => $validated['representante_nombres'],
                'apellidos' => $validated['representante_apellidos'] ?? $user->apellidos,
                'telefono' => $validated['representante_telefono'] ?? $user->telefono,
                'email' => $validated['representante_email'],
            ]);
        });

        return redirect()
            ->route('representante.datos.edit', $alumno->id)
            ->with('success', 'Datos actualizados. Gracias por mantener la ficha al día.');
    }

    /** Solo el representante o el estudiante dueño de la ficha pueden editarla. */
    protected function autorizar(Request $request, Alumno $alumno): void
    {
        $user = $request->user();

        abort_unless(
            (int) $alumno->representante_user_id === (int) $user->id
                || (int) $alumno->user_id === (int) $user->id,
            403,
            'Esta ficha no está vinculada a su cuenta.'
        );
    }
}
