<?php

namespace App\Http\Controllers\Secretaria;

use App\Http\Controllers\Concerns\ValidaDatosAlumno;
use App\Http\Controllers\Controller;
use App\Models\Alumno;
use App\Models\RepresentanteCambio;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Modulo de actualizacion de datos. La secretaria busca al alumno por cedula o
 * por apellidos y actualiza cualquier dato de la ficha, incluida la seccion del
 * representante legal, que se puede cambiar cuando el caso lo pida.
 */
class ActualizacionDatosController extends Controller
{
    use ValidaDatosAlumno;

    /** Campos editables de una cuenta de representante. */
    private const CAMPOS_REPRESENTANTE = [
        'name', 'apellidos', 'email', 'telefono', 'numero_cedula', 'fecha_nacimiento',
    ];

    /** Buscador: por cedula, por apellidos, o listado de fichas por regularizar. */
    public function index(Request $request)
    {
        $cedula = trim((string) $request->query('cedula', ''));
        $apellidos = trim((string) $request->query('apellidos', ''));
        $pendientes = $request->boolean('pendientes');
        $busco = $pendientes || $cedula !== '' || $apellidos !== '';

        $alumnos = collect();

        if ($busco) {
            $alumnos = $this->consultaAlumnos($cedula, $apellidos, $pendientes)
                ->with(['representante', 'matriculas.curso', 'matriculas.paralelo'])
                ->orderBy('apellidos')->orderBy('nombres')
                ->limit(50)
                ->get();
        }

        return view('secretaria.actualizacion.index', [
            'alumnos' => $alumnos,
            'busco' => $busco,
            'cedula' => $cedula,
            'apellidos' => $apellidos,
            'pendientes' => $pendientes,
            'totalPendientes' => $this->consultaPendientes()->count(),
        ]);
    }

    /**
     * Fichas cuyo representante todavia no cumple la regla de ser mayor de
     * edad: sin fecha de nacimiento, o con fecha que no llega a los 18 anos.
     * Sirve para que la secretaria no dependa de saber la cedula o el apellido
     * del alumno para regularizar la cuenta.
     */
    protected function consultaPendientes()
    {
        return Alumno::representantePorRegularizar();
    }

    protected function consultaAlumnos(string $cedula, string $apellidos, bool $pendientes)
    {
        if ($pendientes) {
            return $this->consultaPendientes();
        }

        return Alumno::when($cedula !== '', fn ($q) => $q->where(function ($q) use ($cedula) {
            $q->where('numero_cedula', $cedula)->orWhere('codigo', $cedula);
        }))->when($apellidos !== '', fn ($q) => $q->where('apellidos', 'like', '%'.$apellidos.'%'));
    }

    /** Ficha completa del alumno, editable. */
    public function edit(Request $request, Alumno $alumno)
    {
        $alumno->load(['matriculas.curso', 'matriculas.paralelo']);

        $representantes = $this->representantesDisponibles();

        // Si la secretaria elige otro representante en el desplegable, la ficha
        // se recarga para mostrar los datos de esa persona.
        $elegido = (int) $request->query('representante', 0);
        $representante = $representantes->firstWhere('id', $elegido)
            ?? $alumno->representante
            ?? $representantes->first();

        return view('secretaria.actualizacion.edit', [
            'alumno' => $alumno,
            'representantes' => $representantes,
            'representante' => $representante,
            'cambios' => $alumno->cambiosRepresentante()
                ->with(['anterior', 'nuevo', 'registradoPor'])->latest()->get(),
        ]);
    }

    public function update(Request $request, Alumno $alumno)
    {
        $crearNuevo = $request->boolean('crear_representante');

        $reglas = array_merge($this->reglasAlumno(), $this->reglasRepresentanteLegal());

        if ($crearNuevo) {
            $reglas['representante_nombres'] = 'required|string|max:255';
            $reglas['representante_email'] = 'required|email|max:255|unique:users,email';
            $reglas['representante_cedula'] = 'nullable|string|max:20|unique:users,numero_cedula';
            $reglas['representante_fecha_nacimiento'] = 'required|date';
        }

        $validated = $request->validate($reglas);

        if ($errores = $this->erroresRepresentante($validated)) {
            return back()->withInput()->withErrors($errores);
        }

        $anteriorId = $alumno->representante_user_id;

        $representanteId = $crearNuevo
            ? $this->crearRepresentante($validated)
            : ($validated['representante_user_id'] ?? null);

        // No se puede asignar como legal a alguien menor de edad o sin fecha de
        // nacimiento registrada. Si se deja al mismo de antes, se puede corregir
        // su fecha en este mismo formulario.
        if ($representanteId && $representanteId !== $anteriorId && ! User::find($representanteId)?->esAdulto()) {
            return back()->withInput()->withErrors([
                'representante_user_id' => 'El representante legal debe ser una persona mayor de edad, con fecha de nacimiento registrada.',
            ]);
        }

        DB::transaction(function () use ($request, $alumno, $validated, $anteriorId, $representanteId) {
            $alumno->update($this->datosAlumno($validated));

            if ($representanteId) {
                $this->actualizarRepresentante($representanteId, $validated);

                if ($representanteId !== $anteriorId) {
                    $alumno->update(['representante_user_id' => $representanteId]);

                    RepresentanteCambio::create([
                        'alumno_id' => $alumno->id,
                        'representante_anterior_id' => $anteriorId,
                        'representante_nuevo_id' => $representanteId,
                        'registrado_por' => $request->user()->id,
                    ]);
                }
            }
        });

        return redirect()
            ->route('actualizacion.edit', $alumno->id)
            ->with('success', 'Datos actualizados correctamente.');
    }

    /** Da de alta la cuenta del representante nuevo y devuelve su id. */
    protected function crearRepresentante(array $validated): int
    {
        return User::create([
            'name' => $validated['representante_nombres'],
            'apellidos' => $validated['representante_apellidos'] ?? null,
            'email' => $validated['representante_email'],
            'telefono' => $validated['representante_telefono'] ?? null,
            'numero_cedula' => $validated['representante_cedula'] ?? null,
            'fecha_nacimiento' => $validated['representante_fecha_nacimiento'] ?? null,
            'rol' => 'REPRESENTANTE',
            'password' => bcrypt('password'),
        ])->id;
    }

    /**
     * Los campos escritos corresponden al representante que quedo elegido, que
     * puede ser el mismo de antes o el nuevo. Telefono, cedula y fecha si se
     * pueden vaciar; el nombre nunca se borra.
     */
    protected function actualizarRepresentante(int $representanteId, array $validated): void
    {
        $representante = User::findOrFail($representanteId);

        $representante->fill([
            'name' => $validated['representante_nombres'] ?? $representante->name,
            'apellidos' => $validated['representante_apellidos'] ?? $representante->apellidos,
            'email' => $validated['representante_email'] ?? $representante->email,
            'telefono' => $validated['representante_telefono'] ?? null,
            'numero_cedula' => $validated['representante_cedula'] ?? null,
            'fecha_nacimiento' => $validated['representante_fecha_nacimiento'] ?? null,
        ])->save();
    }

    /** Cuentas de representante que la secretaria puede asignar. */
    protected function representantesDisponibles()
    {
        return User::where('rol', 'REPRESENTANTE')
            ->orderBy('apellidos')->orderBy('name')
            ->get(self::CAMPOS_REPRESENTANTE);
    }
}
