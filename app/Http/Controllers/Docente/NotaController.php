<?php

namespace App\Http\Controllers\Docente;

use App\Http\Controllers\Controller;
use App\Models\Alumno;
use App\Models\ClaseDocente;
use App\Models\Matricula;
use App\Models\Nota;
use App\Models\PeriodoLectivo;
use App\Services\CalculoNotas;
use App\Services\ConfiguracionNotas;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class NotaController extends Controller
{
    public function __construct(private readonly ConfiguracionNotas $configuracion) {}

    public function index(Request $request)
    {
        $user = $request->user();
        $clases = ClaseDocente::with(['curso', 'paralelo', 'materia'])
            ->where('user_id', $user->id)
            ->get();

        $claseId = $request->query('clase_docente_id', $clases->first()?->id);
        $clase = $clases->firstWhere('id', $claseId) ?? $clases->first();

        $sistema = $this->configuracion->sistema();
        $opciones = $this->configuracion->opciones($sistema);
        $grupos = collect($opciones)->groupBy('grupo');

        $parcial = (int) $request->query('parcial', $opciones[0]['parcial'] ?? 1);
        $tipo = $this->configuracion->tipoDeParcial($parcial, $sistema);
        $periodo = PeriodoLectivo::where('activo', true)->first();

        $alumnos = collect();
        $notasExistentes = collect();
        $resumen = [];

        if ($clase) {
            $alumnos = Alumno::whereHas('matriculas', function ($query) use ($clase, $periodo) {
                $query->where('curso_id', $clase->curso_id)
                    ->where('paralelo_id', $clase->paralelo_id)
                    ->when($periodo, fn ($sub) => $sub->where('periodo_id', $periodo->id));
            })->orderBy('apellidos')->orderBy('nombres')->get();

            $matriculasIds = Matricula::where('curso_id', $clase->curso_id)
                ->where('paralelo_id', $clase->paralelo_id)
                ->when($periodo, fn ($query) => $query->where('periodo_id', $periodo->id))
                ->pluck('id');

            $notasExistentes = Nota::whereIn('matricula_id', $matriculasIds)
                ->where('clase_docente_id', $clase->id)
                ->where('parcial', $parcial)
                ->get()
                ->keyBy('alumno_id');

            $notasClase = Nota::whereIn('matricula_id', $matriculasIds)
                ->where('clase_docente_id', $clase->id)
                ->get()
                ->groupBy('alumno_id');

            $resumen = $this->resumenNotas($alumnos, $notasClase, $sistema);
        }

        $promedio = $this->calcularPromedio($alumnos, $notasExistentes, $tipo);

        return view('docente.notas.index', compact(
            'clases', 'clase', 'parcial', 'tipo', 'sistema', 'opciones',
            'grupos', 'alumnos', 'notasExistentes', 'promedio', 'resumen'
        ));
    }

    public function store(Request $request)
    {
        $this->configuracion->normalizarDecimales($request);

        $sistema = $this->configuracion->sistema();
        $parcialesValidos = $this->configuracion->parcialesValidos($sistema);

        $parcial = (int) $request->input('parcial');
        $tipo = $this->configuracion->tipoDeParcial($parcial, $sistema);

        $rules = [
            'clase_docente_id' => 'required|exists:clases_docentes,id',
            'parcial' => 'required|integer|in:'.implode(',', $parcialesValidos),
            'tipo_guardado' => 'required|in:parcial,final',
            'notas' => 'required|array',
            'notas.*.alumno_id' => 'required|exists:alumnos,id',
        ];

        // decimal:0,2 mirrors la columna decimal(5,2): sin ella el servidor
        // aceptaba 9.555 y lo guardaba crudo en SQLite, que no trunca.
        $nota = 'nullable|numeric|decimal:0,2|min:0.01|max:10';

        if ($tipo === 'examen') {
            $rules['notas.*.nota_examen'] = $nota;
        } else {
            $rules['notas.*.fii'] = $nota;
            $rules['notas.*.ffi'] = $nota;
            $rules['notas.*.fig'] = $nota;
            $rules['notas.*.ffg'] = $nota;
            $rules['notas.*.proyecto_integrador'] = $nota;
            $rules['notas.*.evaluacion_parcial'] = $nota;
        }

        $validated = $request->validate($rules);

        $clase = ClaseDocente::findOrFail($validated['clase_docente_id']);

        // Un docente solo puede escribir en sus propias clases. La ruta ya
        // exige rol DOCENTE, pero la pertenencia se valida en el servidor.
        if ((int) $clase->user_id !== (int) $request->user()->id) {
            abort(403, 'No puedes registrar notas en una clase que no te pertenece.');
        }

        if ($validated['tipo_guardado'] === 'final') {
            $vacios = [];
            foreach ($validated['notas'] as $item) {
                if ($tipo === 'examen') {
                    $completo = isset($item['nota_examen']) && $item['nota_examen'] !== '' && $item['nota_examen'] !== null;
                } else {
                    $completo = true;
                    foreach (ConfiguracionNotas::CAMPOS_PARCIAL as $campo) {
                        if (! isset($item[$campo]) || $item[$campo] === '' || $item[$campo] === null) {
                            $completo = false;
                            break;
                        }
                    }
                }

                if (! $completo) {
                    $alumno = Alumno::find($item['alumno_id']);
                    $vacios[] = $alumno?->nombreCompleto() ?? 'Estudiante';
                }
            }

            if (count($vacios) > 0) {
                throw ValidationException::withMessages([
                    'notas' => 'Faltan notas para: '.implode(', ', array_slice($vacios, 0, 5)),
                ]);
            }
        }

        $periodo = PeriodoLectivo::where('activo', true)->first();

        $filas = [];
        $bloqueadas = [];

        foreach ($validated['notas'] as $item) {
            $matricula = Matricula::where('alumno_id', $item['alumno_id'])
                ->where('curso_id', $clase->curso_id)
                ->where('paralelo_id', $clase->paralelo_id)
                ->when($periodo, fn ($query) => $query->where('periodo_id', $periodo->id))
                ->first();

            if (! $matricula) {
                continue;
            }

            $datos = $this->datosNota($item, $tipo);

            if ($validated['tipo_guardado'] === 'final') {
                $datos['estado'] = 'final';
            }

            $existente = Nota::where('matricula_id', $matricula->id)
                ->where('clase_docente_id', $clase->id)
                ->where('parcial', $validated['parcial'])
                ->first();

            if ($existente && $existente->estado === 'final') {
                // La nota final es inmutable para el docente. Solo Secretaria
                // puede corregirla mediante el flujo extraordinario auditado.
                if ($this->cambioNota($existente, $datos, $tipo)) {
                    $bloqueadas[] = $matricula->alumno?->nombreCompleto()
                        ?? Alumno::find($item['alumno_id'])?->nombreCompleto()
                        ?? 'Estudiante';
                }

                continue;
            }

            $filas[] = [
                'matricula_id' => $matricula->id,
                'clase_docente_id' => $clase->id,
                'parcial' => $validated['parcial'],
                'datos' => $datos,
            ];
        }

        if ($bloqueadas !== []) {
            throw ValidationException::withMessages([
                'notas' => 'Las siguientes notas ya están finalizadas y no pueden modificarse: '
                    .implode(', ', array_slice(array_unique($bloqueadas), 0, 5))
                    .'. Solicite una corrección extraordinaria a Secretaría.',
            ]);
        }

        DB::transaction(function () use ($filas) {
            foreach ($filas as $fila) {
                Nota::updateOrCreate(
                    [
                        'matricula_id' => $fila['matricula_id'],
                        'clase_docente_id' => $fila['clase_docente_id'],
                        'parcial' => $fila['parcial'],
                    ],
                    array_merge($fila['datos'], [
                        'alumno_id' => $fila['datos']['alumno_id'],
                    ])
                );
            }
        });

        $msg = $validated['tipo_guardado'] === 'final'
            ? 'Notas guardadas y bloqueadas correctamente.'
            : 'Guardado parcial realizado. Puedes seguir editando.';

        return redirect()->route('docente.notas.index', [
            'clase_docente_id' => $validated['clase_docente_id'],
            'parcial' => $validated['parcial'],
        ])->with('success', $msg);
    }

    /** @return array<string, mixed> */
    private function datosNota(array $item, string $tipo): array
    {
        if ($tipo === 'examen') {
            return [
                'tipo_nota' => 'examen',
                'alumno_id' => $item['alumno_id'],
                'fii' => 0,
                'ffi' => 0,
                'fig' => 0,
                'ffg' => 0,
                'proyecto_integrador' => 0,
                'evaluacion_parcial' => 0,
                'nota_examen' => $item['nota_examen'] ?? null,
            ];
        }

        return [
            'tipo_nota' => 'parcial',
            'fii' => $item['fii'] ?? 0,
            'ffi' => $item['ffi'] ?? 0,
            'fig' => $item['fig'] ?? 0,
            'ffg' => $item['ffg'] ?? 0,
            'proyecto_integrador' => $item['proyecto_integrador'] ?? 0,
            'evaluacion_parcial' => $item['evaluacion_parcial'] ?? 0,
            'nota_examen' => null,
            'alumno_id' => $item['alumno_id'],
        ];
    }

    private function cambioNota(Nota $existente, array $datos, string $tipo): bool
    {
        $campos = $tipo === 'examen'
            ? ['nota_examen']
            : ['fii', 'ffi', 'fig', 'ffg', 'proyecto_integrador', 'evaluacion_parcial'];

        foreach ($campos as $campo) {
            $anterior = $existente->{$campo} === null ? null : round((float) $existente->{$campo}, 4);
            $nuevo = ($datos[$campo] ?? null) === null ? null : round((float) $datos[$campo], 4);

            if ($anterior !== $nuevo) {
                return true;
            }
        }

        return false;
    }

    private function resumenNotas($alumnos, $notasClase, string $sistema): array
    {
        return app(CalculoNotas::class)->resumen($alumnos, $notasClase, $sistema);
    }

    private function calcularPromedio($alumnos, $notasExistentes, string $tipo): ?array
    {
        if ($alumnos->isEmpty()) {
            return null;
        }

        $sumForm = 0;
        $sumEval = 0;
        $sumTotal = 0;
        $count = 0;

        foreach ($alumnos as $alumno) {
            $n = $notasExistentes->get($alumno->id);

            if ($tipo === 'examen') {
                $sumTotal += $n?->nota_examen !== null ? (float) $n->nota_examen : 0;
            } else {
                $fii = (float) ($n?->fii ?? 0);
                $ffi = (float) ($n?->ffi ?? 0);
                $fig = (float) ($n?->fig ?? 0);
                $ffg = (float) ($n?->ffg ?? 0);
                $pi = (float) ($n?->proyecto_integrador ?? 0);
                $ep = (float) ($n?->evaluacion_parcial ?? 0);
                $sf = $fii + $ffi + $fig + $ffg;
                $se = $pi + $ep;
                $sumForm += $sf / 40 * 7;
                $sumEval += $se / 20 * 3;
                $sumTotal += $sf / 40 * 7 + $se / 20 * 3;
            }
            $count++;
        }

        if ($count === 0) {
            return null;
        }

        return [
            'form70' => $sumForm / $count,
            'eval30' => $sumEval / $count,
            'total' => $sumTotal / $count,
        ];
    }
}
