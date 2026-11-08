<?php

namespace App\Http\Controllers\Secretaria;

use App\Http\Controllers\Concerns\ValidaDatosAlumno;
use App\Http\Controllers\Controller;
use App\Models\Alumno;
use App\Models\RepresentanteCambio;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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
        'id', 'name', 'apellidos', 'email', 'telefono', 'numero_cedula', 'fecha_nacimiento',
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

        // Si la secretaria elige otro representante en el buscador, la ficha se
        // recarga con ese id para mostrar los datos de esa persona.
        $elegido = (int) $request->query('representante', 0);
        $seleccionado = $representantes->firstWhere('id', $elegido);
        $representante = $seleccionado
            ?? $alumno->representante
            ?? $representantes->first();

        $lista = $representantes->map(fn (User $r) => [
            'id' => $r->id,
            'etiqueta' => $this->etiquetaRepresentante($r),
        ]);

        // old() puede traer un arreglo si la peticion fue manipulada; se ignora.
        $busquedaPrevia = old('representante_busqueda');
        $idPrevio = old('representante_user_id');

        $busqueda = is_string($busquedaPrevia)
            ? $busquedaPrevia
            : ($representante ? $this->etiquetaRepresentante($representante) : '');

        $idMostrado = is_scalar($idPrevio) && $idPrevio !== ''
            ? $idPrevio
            : ($seleccionado?->id ?? $alumno->representante_user_id);

        return view('secretaria.actualizacion.edit', [
            'alumno' => $alumno,
            'representante' => $representante,
            'representanteActualId' => $idMostrado,
            'representanteBusqueda' => $busqueda,
            'representantesLista' => $lista,
            'cambios' => $alumno->cambiosRepresentante()
                ->with(['anterior', 'nuevo', 'registradoPor'])->latest()->get(),
        ]);
    }

    public function update(Request $request, Alumno $alumno)
    {
        // Los textos se guardan en mayusculas y con sus tildes originales.
        $this->normalizarMayusculasRequest($request);

        $crearNuevo = $request->boolean('crear_representante');
        $busqueda = $request->input('representante_busqueda', '');
        $busqueda = is_string($busqueda) ? trim($busqueda) : '';

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
        $solicitado = isset($validated['representante_user_id'])
            ? (int) $validated['representante_user_id']
            : null;

        if ($crearNuevo) {
            $representanteId = $this->crearRepresentante($validated);
        } elseif ($busqueda !== '') {
            // El buscador es la fuente principal: lo escrito tiene que existir
            // en la lista y coincidir con la persona que quedo seleccionada.
            $encontrado = $this->resolverRepresentante($busqueda);

            if (! $encontrado) {
                return back()->withInput()->withErrors([
                    'representante_busqueda' => 'Escriba un representante que aparezca en la lista.',
                ]);
            }

            if ($solicitado && $solicitado !== $encontrado->id) {
                return back()->withInput()->withErrors([
                    'representante_busqueda' => 'El representante escrito no coincide con la persona seleccionada. Elijalo de la lista.',
                ]);
            }

            // Si se trata de otra persona, primero debe recargarse la ficha
            // para que los campos correspondan a ella y no se sobrescriban.
            if ($encontrado->id !== $anteriorId && $solicitado !== $encontrado->id) {
                return back()->withInput()->withErrors([
                    'representante_busqueda' => 'Seleccione al representante de la lista para cargar sus datos antes de guardar.',
                ]);
            }

            $representanteId = $encontrado->id;
        } else {
            $representanteId = $solicitado;
        }

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

    /** Etiqueta visible del buscador: nombre completo y cedula si la tiene. */
    protected function etiquetaRepresentante(User $representante): string
    {
        $etiqueta = Str::upper($representante->nombreCompleto());

        if ($representante->numero_cedula) {
            $etiqueta .= ' — '.$representante->numero_cedula;
        }

        return $etiqueta;
    }

    /**
     * Compara sin distinguir mayusculas ni tildes, para que la busqueda sea
     * tolerante a como escriba la secretaria.
     */
    protected function normalizarBusqueda(string $texto): string
    {
        $texto = Str::upper(trim($texto));

        $texto = str_replace(
            ['Á', 'É', 'Í', 'Ó', 'Ú', 'Ü', 'Ñ', 'À', 'È', 'Ì', 'Ò', 'Ù'],
            ['A', 'E', 'I', 'O', 'U', 'U', 'N', 'A', 'E', 'I', 'O', 'U'],
            $texto
        );

        return preg_replace('/\s+/', ' ', $texto) ?? '';
    }

    /**
     * Encuentra a la persona escrita en el buscador. Solo devuelve un resultado
     * cuando es inequivoco: coincidencia exacta con nombre, etiqueta o cedula,
     * o una unica coincidencia parcial.
     */
    protected function resolverRepresentante(string $busqueda): ?User
    {
        $buscado = $this->normalizarBusqueda($busqueda);

        if ($buscado === '') {
            return null;
        }

        $representantes = $this->representantesDisponibles();

        $exactos = $representantes->filter(function (User $r) use ($buscado) {
            $valores = array_filter([
                $this->normalizarBusqueda($this->etiquetaRepresentante($r)),
                $this->normalizarBusqueda($r->nombreCompleto()),
                $r->numero_cedula ? $this->normalizarBusqueda($r->numero_cedula) : null,
            ]);

            return in_array($buscado, $valores, true);
        });

        if ($exactos->count() === 1) {
            return $exactos->first();
        }

        if ($exactos->count() > 1) {
            return null;
        }

        $parciales = $representantes->filter(
            fn (User $r) => str_contains(
                $this->normalizarBusqueda($this->etiquetaRepresentante($r)),
                $buscado
            )
        );

        return $parciales->count() === 1 ? $parciales->first() : null;
    }
}
