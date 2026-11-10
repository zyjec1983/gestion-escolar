<?php

namespace App\Http\Controllers\Secretaria;

use App\Http\Controllers\Controller;
use App\Models\Alumno;
use App\Models\Asistencia;
use App\Models\Curso;
use App\Models\ExpedienteAuditoria;
use App\Models\ExpedienteDocumento;
use App\Models\ExpedienteTraslado;
use App\Models\HistorialExternoNota;
use App\Models\HistorialExternoPromocion;
use App\Models\Institucion;
use App\Models\Matricula;
use App\Models\Nota;
use App\Models\Paralelo;
use App\Models\PeriodoLectivo;
use App\Models\User;
use App\Services\CalculoNotas;
use App\Services\ChecklistTraslado;
use App\Services\ConfiguracionNotas;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Modulo de traslados e historial academico. Registra ingresos y salidas,
 * controla el check-list documental, homologa notas externas, administra el
 * paz y salvo y emite los PDF institucionales.
 */
class TrasladoController extends Controller
{
    public function __construct(private readonly ChecklistTraslado $checklist) {}

    public function index(Request $request)
    {
        $filtros = $request->validate([
            'tipo' => ['nullable', Rule::in([ExpedienteTraslado::TIPO_INGRESO, ExpedienteTraslado::TIPO_SALIDA])],
            'estado' => ['nullable', Rule::in([
                ExpedienteTraslado::ESTADO_POR_REGULARIZAR,
                ExpedienteTraslado::ESTADO_DEFINITIVA,
                ExpedienteTraslado::ESTADO_CERRADO,
            ])],
            'buscar' => ['nullable', 'string', 'max:100'],
        ]);

        $expedientes = ExpedienteTraslado::with(['alumno', 'curso', 'paralelo'])
            ->when($filtros['tipo'] ?? null, fn ($query, string $tipo) => $query->where('tipo', $tipo))
            ->when($filtros['estado'] ?? null, fn ($query, string $estado) => $query->where('estado', $estado))
            ->when($filtros['buscar'] ?? null, function ($query, string $buscar) {
                $query->where(function ($sub) use ($buscar) {
                    $sub->where('institucion', 'like', "%{$buscar}%")
                        ->orWhereHas('alumno', function ($alumno) use ($buscar) {
                            $alumno->where('nombres', 'like', "%{$buscar}%")
                                ->orWhere('apellidos', 'like', "%{$buscar}%")
                                ->orWhere('numero_cedula', 'like', "%{$buscar}%")
                                ->orWhere('codigo', 'like', "%{$buscar}%");
                        });
                });
            })
            ->latest('fecha_traslado')
            ->latest('id')
            ->paginate(12)
            ->withQueryString();

        $kpis = [
            'ingresos' => ExpedienteTraslado::ingresos()
                ->whereYear('fecha_traslado', now()->year)
                ->count(),
            'por_regularizar' => ExpedienteTraslado::porRegularizar()->count(),
            'definitivas' => ExpedienteTraslado::where('estado', ExpedienteTraslado::ESTADO_DEFINITIVA)->count(),
            'salidas' => ExpedienteTraslado::salidas()->count(),
        ];

        return view('secretaria.traslados.index', compact('expedientes', 'kpis', 'filtros'));
    }

    public function create()
    {
        $cursos = Curso::enOrdenEscolar()->get();
        $paralelos = Paralelo::orderBy('nombre')->get();
        $periodo = PeriodoLectivo::where('activo', true)->first();
        $periodoVigente = $this->checklist->periodoVigente();

        $alumnos = Alumno::select('id', 'nombres', 'apellidos', 'numero_cedula', 'codigo', 'representante_user_id')
            ->with([
                'representante:id,name,apellidos,numero_cedula',
                'matriculas' => function ($query) use ($periodo) {
                    $query->when($periodo, fn ($sub) => $sub->where('periodo_id', $periodo->id))
                        ->with(['curso:id,nombre', 'paralelo:id,nombre'])
                        ->latest('id');
                },
            ])
            ->orderBy('apellidos')
            ->orderBy('nombres')
            ->get();

        $representantes = User::where('rol', 'REPRESENTANTE')
            ->orderBy('apellidos')
            ->orderBy('name')
            ->get(['id', 'name', 'apellidos', 'numero_cedula']);

        $cursoOctavo = new Curso(['nombre' => 'OCTAVO']);

        $checklists = [
            'inicio' => $this->checklist->requisitos(
                ExpedienteTraslado::TIPO_INGRESO,
                ExpedienteTraslado::MOMENTO_INICIO,
                null,
                $periodoVigente
            ),
            'inicio_octavo' => $this->checklist->requisitos(
                ExpedienteTraslado::TIPO_INGRESO,
                ExpedienteTraslado::MOMENTO_INICIO,
                $cursoOctavo,
                $periodoVigente
            ),
            'transcurso' => $this->checklist->requisitos(
                ExpedienteTraslado::TIPO_INGRESO,
                ExpedienteTraslado::MOMENTO_TRANSICURSO,
                null,
                $periodoVigente
            ),
            'transcurso_octavo' => $this->checklist->requisitos(
                ExpedienteTraslado::TIPO_INGRESO,
                ExpedienteTraslado::MOMENTO_TRANSICURSO,
                $cursoOctavo,
                $periodoVigente
            ),
            'salida' => $this->checklist->requisitos(ExpedienteTraslado::TIPO_SALIDA, null, null, $periodoVigente),
        ];

        $cursosOctavo = $cursos->filter(fn (Curso $curso) => $curso->grado() === 'OCTAVO')->pluck('id')->values();

        return view('secretaria.traslados.create', compact(
            'cursos', 'paralelos', 'periodo', 'periodoVigente', 'alumnos',
            'representantes', 'checklists', 'cursosOctavo'
        ));
    }

    public function store(Request $request)
    {
        $datos = $this->normalizarMayusculas($request->all());
        $request->merge($datos);

        $validated = $request->validate([
            'tipo' => ['required', Rule::in([ExpedienteTraslado::TIPO_INGRESO, ExpedienteTraslado::TIPO_SALIDA])],
            'momento' => [
                'nullable',
                Rule::requiredIf(fn () => $request->input('tipo') === ExpedienteTraslado::TIPO_INGRESO),
                Rule::in([ExpedienteTraslado::MOMENTO_INICIO, ExpedienteTraslado::MOMENTO_TRANSICURSO]),
            ],
            'modo_alumno' => ['required', Rule::in(['existente', 'nuevo'])],
            'alumno_id' => ['nullable', 'required_if:modo_alumno,existente', 'exists:alumnos,id'],
            'nombres' => ['nullable', 'required_if:modo_alumno,nuevo', 'string', 'max:255'],
            'apellidos' => ['nullable', 'required_if:modo_alumno,nuevo', 'string', 'max:255'],
            'numero_cedula' => ['nullable', 'required_if:modo_alumno,nuevo', 'string', 'max:20'],
            'fecha_nacimiento' => ['nullable', 'date', 'before:today'],
            'genero' => ['nullable', Rule::in(['masculino', 'femenino'])],
            'representante_user_id' => [
                'nullable',
                'required_if:modo_alumno,nuevo',
                Rule::exists('users', 'id')->where(fn ($query) => $query->where('rol', 'REPRESENTANTE')),
            ],
            'fecha_traslado' => ['required', 'date'],
            'institucion' => ['required', 'string', 'max:255'],
            'anio_lectivo' => ['required', 'string', 'max:20'],
            'curso_id' => ['nullable', 'required_with:paralelo_id', 'exists:cursos,id'],
            'paralelo_id' => ['nullable', 'required_with:curso_id', 'exists:paralelos,id'],
            'matricula_id' => ['nullable', 'exists:matriculas,id'],
            'observaciones' => ['nullable', 'string', 'max:2000'],
        ]);

        $periodo = PeriodoLectivo::where('activo', true)->first();

        $expediente = DB::transaction(function () use ($request, $validated, $periodo) {
            $alumno = $this->resolverAlumno($validated);
            $matricula = $this->resolverMatricula($alumno, $validated, $periodo);

            $expediente = ExpedienteTraslado::create([
                'alumno_id' => $alumno->id,
                'matricula_id' => $matricula?->id,
                'curso_id' => $matricula?->curso_id ?? ($validated['curso_id'] ?? null),
                'paralelo_id' => $matricula?->paralelo_id ?? ($validated['paralelo_id'] ?? null),
                'tipo' => $validated['tipo'],
                'momento' => $validated['tipo'] === ExpedienteTraslado::TIPO_INGRESO ? $validated['momento'] : null,
                'fecha_traslado' => $validated['fecha_traslado'],
                'institucion' => $validated['institucion'],
                'anio_lectivo' => $validated['anio_lectivo'],
                'observaciones' => $validated['observaciones'] ?? null,
                'estado' => ExpedienteTraslado::ESTADO_POR_REGULARIZAR,
                'documentos_completos' => false,
                'creado_por' => $request->user()->id,
            ]);

            $this->auditar(
                $request,
                $expediente,
                ExpedienteAuditoria::ACCION_CREACION,
                'Expediente de '.$expediente->tipoLabel().' creado para '.$alumno->nombreCompleto().'.',
                null,
                ['tipo' => $expediente->tipo, 'institucion' => $expediente->institucion]
            );

            $this->recalcularEstado($expediente);

            return $expediente;
        });

        return redirect()
            ->route('traslados.show', $expediente)
            ->with('success', 'Expediente creado. Complete la documentación para pasar a matrícula definitiva.');
    }

    public function show(ExpedienteTraslado $expediente)
    {
        $expediente->load([
            'alumno.representante',
            'matricula',
            'curso',
            'paralelo',
            'documentos.subidor',
            'promociones',
            'notasParciales',
            'auditorias.usuario',
            'creador',
            'validador',
        ]);

        $requisitos = $this->checklist->requisitos(
            $expediente->tipo,
            $expediente->momento,
            $expediente->curso,
            $this->checklist->periodoVigente()
        );
        $subidos = $this->checklist->clavesSubidas($expediente);
        $faltantes = $this->checklist->faltantes($expediente);
        $periodoVigente = $this->checklist->periodoVigente();
        $institucion = Institucion::first();

        $notasActuales = collect();
        if ($expediente->matricula_id) {
            $notasActuales = Nota::with(['claseDocente.materia', 'claseDocente.docente'])
                ->where('matricula_id', $expediente->matricula_id)
                ->get()
                ->groupBy(fn (Nota $nota) => $nota->claseDocente?->materia?->nombre ?? 'Sin materia');
        }

        return view('secretaria.traslados.show', compact(
            'expediente', 'requisitos', 'subidos', 'faltantes', 'periodoVigente',
            'institucion', 'notasActuales'
        ));
    }

    public function update(Request $request, ExpedienteTraslado $expediente)
    {
        $this->autorizarEdicion($expediente);

        $datos = $this->normalizarMayusculas($request->all());
        $request->merge($datos);

        $validated = $request->validate([
            'momento' => [
                'nullable',
                Rule::requiredIf(fn () => $expediente->tipo === ExpedienteTraslado::TIPO_INGRESO),
                Rule::in([ExpedienteTraslado::MOMENTO_INICIO, ExpedienteTraslado::MOMENTO_TRANSICURSO]),
            ],
            'fecha_traslado' => ['required', 'date'],
            'institucion' => ['required', 'string', 'max:255'],
            'anio_lectivo' => ['required', 'string', 'max:20'],
            'curso_id' => ['nullable', 'exists:cursos,id'],
            'paralelo_id' => ['nullable', 'exists:paralelos,id'],
            'observaciones' => ['nullable', 'string', 'max:2000'],
        ]);

        $anteriores = $expediente->only([
            'momento', 'fecha_traslado', 'institucion', 'anio_lectivo',
            'curso_id', 'paralelo_id', 'observaciones',
        ]);

        $expediente->update([
            'momento' => $expediente->tipo === ExpedienteTraslado::TIPO_INGRESO ? $validated['momento'] : null,
            'fecha_traslado' => $validated['fecha_traslado'],
            'institucion' => $validated['institucion'],
            'anio_lectivo' => $validated['anio_lectivo'],
            'curso_id' => $validated['curso_id'] ?? null,
            'paralelo_id' => $validated['paralelo_id'] ?? null,
            'observaciones' => $validated['observaciones'] ?? null,
        ]);

        $this->recalcularEstado($expediente->fresh(['curso', 'documentos', 'matricula']));

        $this->auditar(
            $request,
            $expediente,
            ExpedienteAuditoria::ACCION_ACTUALIZACION,
            'Datos generales del expediente actualizados.',
            $anteriores,
            $expediente->only(array_keys($anteriores))
        );

        return back()->with('success', 'Datos del expediente actualizados.');
    }

    public function subirDocumento(Request $request, ExpedienteTraslado $expediente)
    {
        $this->autorizarEdicion($expediente);

        $requisitos = $this->checklist->requisitos(
            $expediente->tipo,
            $expediente->momento,
            $expediente->curso,
            $this->checklist->periodoVigente()
        );
        $claves = array_column($requisitos, 'clave');

        $validated = $request->validate([
            'tipo_documento' => ['required', Rule::in($claves)],
            'archivo' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ]);

        $archivo = $request->file('archivo');
        $base = Str::slug(pathinfo($archivo->getClientOriginalName(), PATHINFO_FILENAME));
        $base = $base !== '' ? $base : 'documento';
        $extension = strtolower($archivo->getClientOriginalExtension());
        $nombre = $base.'-'.Str::random(8).'.'.$extension;
        $directorio = 'expedientes/'.$expediente->alumno_id;

        $ruta = null;

        try {
            $ruta = $archivo->storeAs($directorio, $nombre, 'public');

            if (! $ruta) {
                throw new \RuntimeException('No se pudo almacenar el archivo.');
            }

            $documento = ExpedienteDocumento::create([
                'expediente_id' => $expediente->id,
                'tipo_documento' => $validated['tipo_documento'],
                'nombre_original' => mb_substr($archivo->getClientOriginalName(), 0, 255, 'UTF-8'),
                'ruta' => $ruta,
                'mime' => $archivo->getClientMimeType() ?: 'application/octet-stream',
                'tamano' => $archivo->getSize() ?: 0,
                'subido_por' => $request->user()->id,
            ]);
        } catch (Throwable $e) {
            if ($ruta) {
                Storage::disk('public')->delete($ruta);
            }

            throw $e;
        }

        $this->recalcularEstado($expediente->fresh(['curso', 'documentos', 'matricula']));

        $this->auditar(
            $request,
            $expediente,
            ExpedienteAuditoria::ACCION_DOCUMENTO_SUBIDO,
            'Documento adjuntado: '.$this->checklist->etiqueta($documento->tipo_documento).'.',
            null,
            ['tipo_documento' => $documento->tipo_documento, 'archivo' => $documento->nombre_original]
        );

        return back()->with('success', 'Documento adjuntado al expediente.');
    }

    public function eliminarDocumento(Request $request, ExpedienteTraslado $expediente, ExpedienteDocumento $documento)
    {
        $this->autorizarEdicion($expediente);
        abort_unless((int) $documento->expediente_id === (int) $expediente->id, 404);

        $etiqueta = $this->checklist->etiqueta($documento->tipo_documento);
        $nombre = $documento->nombre_original;
        $ruta = $documento->ruta;

        $documento->delete();
        Storage::disk('public')->delete($ruta);

        $this->recalcularEstado($expediente->fresh(['curso', 'documentos', 'matricula']));

        $this->auditar(
            $request,
            $expediente,
            ExpedienteAuditoria::ACCION_DOCUMENTO_ELIMINADO,
            'Documento eliminado: '.$etiqueta.'.',
            ['tipo_documento' => $documento->tipo_documento, 'archivo' => $nombre],
            null
        );

        return back()->with('success', 'Documento eliminado y estado del expediente recalculado.');
    }

    public function storePromocion(Request $request, ExpedienteTraslado $expediente)
    {
        $this->autorizarEdicion($expediente);

        $request->merge(['promedio' => $this->decimal($request->input('promedio'))]);

        $validated = $request->validate([
            'anio_lectivo' => ['required', 'string', 'max:20'],
            'nivel' => ['required', 'string', 'max:100'],
            'promedio' => ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:100'],
            'escala' => ['required', 'string', 'max:50'],
            'observacion' => ['nullable', 'string', 'max:1000'],
        ]);

        $promocion = HistorialExternoPromocion::create([
            'expediente_id' => $expediente->id,
            'anio_lectivo' => $validated['anio_lectivo'],
            'nivel' => $validated['nivel'],
            'promedio' => $validated['promedio'],
            'escala' => $validated['escala'],
            'observacion' => $validated['observacion'] ?? null,
        ]);

        $this->auditar(
            $request,
            $expediente,
            ExpedienteAuditoria::ACCION_PROMOCION_AGREGADA,
            'Promoción externa homologada: '.$promocion->nivel.' ('.$promocion->anio_lectivo.').',
            null,
            $promocion->only(['anio_lectivo', 'nivel', 'promedio', 'escala'])
        );

        return back()->with('success', 'Promoción homologada al historial externo.');
    }

    public function destroyPromocion(Request $request, ExpedienteTraslado $expediente, HistorialExternoPromocion $promocion)
    {
        $this->autorizarEdicion($expediente);
        abort_unless((int) $promocion->expediente_id === (int) $expediente->id, 404);

        $datos = $promocion->only(['anio_lectivo', 'nivel', 'promedio', 'escala']);
        $promocion->delete();

        $this->auditar(
            $request,
            $expediente,
            ExpedienteAuditoria::ACCION_PROMOCION_ELIMINADA,
            'Promoción eliminada: '.$datos['nivel'].' ('.$datos['anio_lectivo'].').',
            $datos,
            null
        );

        return back()->with('success', 'Promoción eliminada del historial externo.');
    }

    public function storeNota(Request $request, ExpedienteTraslado $expediente)
    {
        $this->autorizarEdicion($expediente);

        $request->merge([
            'nota' => $this->decimal($request->input('nota')),
            'ponderacion' => $this->decimal($request->input('ponderacion')),
            'asistencia_porcentaje' => $this->decimal($request->input('asistencia_porcentaje')),
        ]);

        $validated = $request->validate([
            'asignatura' => ['required', 'string', 'max:150'],
            'periodo' => ['required', 'string', 'max:80'],
            'nota' => ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:100'],
            'ponderacion' => ['nullable', 'numeric', 'decimal:0,2', 'min:0', 'max:100'],
            'escala' => ['required', 'string', 'max:50'],
            'asistencia_porcentaje' => ['nullable', 'numeric', 'decimal:0,2', 'min:0', 'max:100'],
        ]);

        $nota = HistorialExternoNota::create([
            'expediente_id' => $expediente->id,
            'asignatura' => $validated['asignatura'],
            'periodo' => $validated['periodo'],
            'nota' => $validated['nota'],
            'ponderacion' => $validated['ponderacion'] ?? null,
            'escala' => $validated['escala'],
            'asistencia_porcentaje' => $validated['asistencia_porcentaje'] ?? null,
        ]);

        $this->auditar(
            $request,
            $expediente,
            ExpedienteAuditoria::ACCION_NOTA_AGREGADA,
            'Nota parcial externa homologada: '.$nota->asignatura.' / '.$nota->periodo.'.',
            null,
            $nota->only(['asignatura', 'periodo', 'nota', 'ponderacion', 'escala', 'asistencia_porcentaje'])
        );

        return back()->with('success', 'Nota parcial homologada. Se conservó la escala y ponderación de origen.');
    }

    public function destroyNota(Request $request, ExpedienteTraslado $expediente, HistorialExternoNota $nota)
    {
        $this->autorizarEdicion($expediente);
        abort_unless((int) $nota->expediente_id === (int) $expediente->id, 404);

        $datos = $nota->only(['asignatura', 'periodo', 'nota', 'ponderacion', 'escala', 'asistencia_porcentaje']);
        $nota->delete();

        $this->auditar(
            $request,
            $expediente,
            ExpedienteAuditoria::ACCION_NOTA_ELIMINADA,
            'Nota parcial eliminada: '.$datos['asignatura'].' / '.$datos['periodo'].'.',
            $datos,
            null
        );

        return back()->with('success', 'Nota parcial eliminada del historial externo.');
    }

    public function actualizarPazYSalvo(Request $request, ExpedienteTraslado $expediente)
    {
        $this->autorizarEdicion($expediente);

        $validated = $request->validate([
            'paz_y_salvo_financiero' => ['nullable', 'boolean'],
            'paz_y_salvo_biblioteca' => ['nullable', 'boolean'],
            'paz_y_salvo_observacion' => ['nullable', 'string', 'max:1000'],
        ]);

        $anteriores = $expediente->only([
            'paz_y_salvo_financiero', 'paz_y_salvo_biblioteca', 'paz_y_salvo_observacion',
        ]);

        $expediente->update([
            'paz_y_salvo_financiero' => $request->boolean('paz_y_salvo_financiero'),
            'paz_y_salvo_biblioteca' => $request->boolean('paz_y_salvo_biblioteca'),
            'paz_y_salvo_observacion' => $validated['paz_y_salvo_observacion'] ?? null,
        ]);

        $this->auditar(
            $request,
            $expediente,
            ExpedienteAuditoria::ACCION_PAZ_Y_SALVO,
            'Paz y salvo actualizado manualmente.',
            $anteriores,
            $expediente->only(array_keys($anteriores))
        );

        return back()->with('success', 'Estado de paz y salvo actualizado.');
    }

    public function cerrar(Request $request, ExpedienteTraslado $expediente)
    {
        $this->autorizarEdicion($expediente);

        $this->recalcularEstado($expediente->fresh(['curso', 'documentos', 'matricula']));
        $expediente->refresh();

        if (! $expediente->documentos_completos) {
            throw ValidationException::withMessages([
                'expediente' => 'No se puede cerrar el expediente: aún faltan documentos obligatorios. '
                    .'La matrícula definitiva exige el 100% de la documentación (RN-04).',
            ]);
        }

        $expediente->update([
            'estado' => ExpedienteTraslado::ESTADO_CERRADO,
            'validado_por' => $request->user()->id,
            'validado_at' => now(),
            'cerrado_at' => now(),
        ]);

        if ($expediente->tipo === ExpedienteTraslado::TIPO_INGRESO && $expediente->matricula) {
            $expediente->matricula->update(['estado' => Matricula::ESTADO_DEFINITIVA]);
        }

        $this->auditar(
            $request,
            $expediente,
            ExpedienteAuditoria::ACCION_CIERRE,
            'Expediente validado con el 100% de la documentación y cerrado por Secretaría.',
            ['estado' => ExpedienteTraslado::ESTADO_DEFINITIVA],
            ['estado' => ExpedienteTraslado::ESTADO_CERRADO]
        );

        return back()->with('success', 'Expediente validado y cerrado. El historial externo quedó inmutable.');
    }

    public function historialPdf(ExpedienteTraslado $expediente)
    {
        $datos = $this->datosAcademicos($expediente);

        $pdf = Pdf::loadView('secretaria.traslados.pdf.historial', $datos)
            ->setPaper('a4', 'portrait');

        return $pdf->download('historial-academico-'.$this->nombreArchivo($expediente).'.pdf');
    }

    public function certificadoSalidaPdf(ExpedienteTraslado $expediente)
    {
        abort_unless($expediente->tipo === ExpedienteTraslado::TIPO_SALIDA, 404);

        if (! $expediente->puedeEmitirCertificadoSalida()) {
            abort(403, 'No se puede emitir el certificado de salida: existen deudas o documentos pendientes (RN-02).');
        }

        $datos = $this->datosAcademicos($expediente);

        $pdf = Pdf::loadView('secretaria.traslados.pdf.certificado-salida', $datos)
            ->setPaper('a4', 'portrait');

        return $pdf->download('certificado-salida-'.$this->nombreArchivo($expediente).'.pdf');
    }

    private function resolverAlumno(array $validated): Alumno
    {
        if ($validated['modo_alumno'] === 'existente') {
            return Alumno::findOrFail($validated['alumno_id']);
        }

        $ultimo = Alumno::max('id') ?? 0;

        return Alumno::create([
            'codigo' => now()->year.str_pad((string) ($ultimo + 1), 6, '0', STR_PAD_LEFT),
            'nombres' => $validated['nombres'],
            'apellidos' => $validated['apellidos'],
            'numero_cedula' => $validated['numero_cedula'],
            'fecha_nacimiento' => $validated['fecha_nacimiento'] ?? null,
            'genero' => $validated['genero'] ?? null,
            'representante_user_id' => $validated['representante_user_id'],
            'institucion_procedencia' => $validated['institucion'],
        ]);
    }

    private function resolverMatricula(Alumno $alumno, array $validated, ?PeriodoLectivo $periodo): ?Matricula
    {
        if (! empty($validated['matricula_id'])) {
            $matricula = Matricula::findOrFail($validated['matricula_id']);

            if ((int) $matricula->alumno_id !== (int) $alumno->id) {
                throw ValidationException::withMessages([
                    'matricula_id' => 'La matrícula seleccionada no pertenece al estudiante.',
                ]);
            }

            return $matricula;
        }

        if (empty($validated['curso_id']) || empty($validated['paralelo_id']) || ! $periodo) {
            return null;
        }

        return Matricula::firstOrCreate(
            ['alumno_id' => $alumno->id, 'periodo_id' => $periodo->id],
            [
                'codigo_matricula' => 'MAT-'.$alumno->id,
                'curso_id' => $validated['curso_id'],
                'paralelo_id' => $validated['paralelo_id'],
                'estado' => Matricula::ESTADO_PROVISIONAL,
            ]
        );
    }

    private function recalcularEstado(ExpedienteTraslado $expediente): void
    {
        $expediente->load(['curso', 'documentos']);
        $completo = $this->checklist->estaCompleto($expediente);

        $expediente->documentos_completos = $completo;

        if (! $expediente->estaCerrado()) {
            $expediente->estado = $completo
                ? ExpedienteTraslado::ESTADO_DEFINITIVA
                : ExpedienteTraslado::ESTADO_POR_REGULARIZAR;
        }

        $expediente->save();

        if ($expediente->tipo === ExpedienteTraslado::TIPO_INGRESO && $expediente->matricula) {
            $expediente->matricula->update([
                'estado' => $completo ? Matricula::ESTADO_DEFINITIVA : Matricula::ESTADO_PROVISIONAL,
            ]);
        }
    }

    private function autorizarEdicion(ExpedienteTraslado $expediente): void
    {
        if ($expediente->estaCerrado()) {
            abort(403, 'El expediente está cerrado y su historial es inmutable (RN-03).');
        }
    }

    private function auditar(
        Request $request,
        ExpedienteTraslado $expediente,
        string $accion,
        ?string $detalle = null,
        ?array $anteriores = null,
        ?array $nuevos = null
    ): void {
        $usuario = $request->user();

        ExpedienteAuditoria::create([
            'expediente_id' => $expediente->id,
            'alumno_nombre' => $expediente->nombreAlumno(),
            'usuario_id' => $usuario?->id,
            'usuario_nombre' => $usuario?->nombreCompleto() ?? 'Sistema',
            'usuario_rol' => $usuario?->rol ?? 'SISTEMA',
            'accion' => $accion,
            'detalle' => $detalle,
            'datos_anteriores' => $anteriores,
            'datos_nuevos' => $nuevos,
            'ip' => $request->ip(),
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 1000, 'UTF-8'),
        ]);
    }

    private function datosAcademicos(ExpedienteTraslado $expediente): array
    {
        $expediente->load([
            'alumno.representante',
            'matricula.periodo',
            'curso',
            'paralelo',
            'promociones',
            'notasParciales',
        ]);

        $institucion = Institucion::first();
        $sistema = app(ConfiguracionNotas::class)->sistema();
        $calculo = app(CalculoNotas::class);

        $resumenActual = [];
        $asistencia = ['total' => 0, 'asistidas' => 0, 'porcentaje' => null];

        if ($expediente->matricula) {
            $notas = Nota::with(['claseDocente.materia', 'claseDocente.docente'])
                ->where('matricula_id', $expediente->matricula_id)
                ->get()
                ->groupBy('clase_docente_id');

            foreach ($notas as $grupo) {
                $materia = $grupo->first()->claseDocente?->materia?->nombre ?? 'Sin materia';
                $docente = $grupo->first()->claseDocente?->docente;

                $resumen = $calculo->resumen(
                    collect([$expediente->alumno]),
                    collect([$expediente->alumno->id => $grupo]),
                    $sistema
                );

                $resumenActual[] = [
                    'materia' => $materia,
                    'docente' => $docente,
                    'datos' => $resumen[0] ?? [],
                ];
            }

            $asistencias = Asistencia::where('alumno_id', $expediente->alumno_id)
                ->when($expediente->curso_id, fn ($query) => $query->where('curso_id', $expediente->curso_id))
                ->when($expediente->paralelo_id, fn ($query) => $query->where('paralelo_id', $expediente->paralelo_id))
                ->get();

            $total = $asistencias->count();
            $asistidas = $asistencias->whereIn('estado', ['presente', 'justificada', 'atraso'])->count();

            $asistencia = [
                'total' => $total,
                'asistidas' => $asistidas,
                'porcentaje' => $total > 0 ? round($asistidas / $total * 100, 2) : null,
            ];
        }

        return [
            'expediente' => $expediente,
            'alumno' => $expediente->alumno,
            'institucion' => $institucion,
            'sistema' => $sistema,
            'resumenActual' => $resumenActual,
            'asistencia' => $asistencia,
            'fechaEmision' => now(),
        ];
    }

    private function nombreArchivo(ExpedienteTraslado $expediente): string
    {
        $codigo = Str::slug($expediente->alumno?->codigo ?? (string) $expediente->alumno_id);

        return $codigo !== '' ? $codigo : 'expediente-'.$expediente->id;
    }

    private function decimal(mixed $valor): mixed
    {
        return is_string($valor) ? str_replace(',', '.', trim($valor)) : $valor;
    }

    private function normalizarMayusculas(array $datos): array
    {
        foreach (['nombres', 'apellidos', 'numero_cedula', 'institucion', 'nivel', 'asignatura', 'periodo', 'escala'] as $campo) {
            if (isset($datos[$campo]) && is_string($datos[$campo])) {
                $datos[$campo] = trim(mb_strtoupper($datos[$campo], 'UTF-8'));
            }
        }

        return $datos;
    }
}
