<?php

namespace App\Http\Controllers\Docente;

use App\Http\Controllers\Controller;
use App\Models\Alumno;
use App\Models\ClaseDocente;
use App\Models\Institucion;
use App\Models\Matricula;
use App\Models\Nota;
use App\Services\CalculoNotas;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class NotaController extends Controller
{
    /**
     * Campos de nota del grid. Admiten coma o punto al escribirse, pero
     * siempre se guardan con punto: la columna es decimal(5,2) y PHP, SQL,
     * JSON y Excel no interpretan una coma como separador decimal.
     */
    private const CAMPOS_NOTA = [
        'fii', 'ffi', 'fig', 'ffg',
        'proyecto_integrador', 'evaluacion_parcial',
        'nota_examen',
    ];

    public function index(Request $request)
    {
        $user = $request->user();
        $clases = ClaseDocente::with(['curso', 'paralelo', 'materia'])
            ->where('user_id', $user->id)
            ->get();

        $claseId = $request->query('clase_docente_id', $clases->first()?->id);
        $clase = $clases->firstWhere('id', $claseId) ?? $clases->first();

        $sistema = $this->sistemaEvaluacion();
        $opciones = $this->opcionesNotas($sistema);
        $grupos = collect($opciones)->groupBy('grupo');

        $parcial = (int) $request->query('parcial', $opciones[0]['parcial'] ?? 1);
        $tipo = $this->tipoDeParcial($parcial, $sistema);

        $alumnos = collect();
        $notasExistentes = collect();
        $resumen = [];

        if ($clase) {
            $alumnos = Alumno::whereHas('matriculas', function ($q) use ($clase) {
                $q->where('curso_id', $clase->curso_id)
                  ->where('paralelo_id', $clase->paralelo_id);
            })->orderBy('apellidos')->orderBy('nombres')->get();

            $matriculasIds = Matricula::where('curso_id', $clase->curso_id)
                ->where('paralelo_id', $clase->paralelo_id)
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

    /**
     * Normaliza la coma decimal a punto ANTES de validar, para que un 9,5
     * escrito a mano quede como 9.5 y no sea rechazado por la regla numeric.
     */
    private function normalizarDecimales(Request $request): void
    {
        $notas = $request->input('notas');

        if (!is_array($notas)) {
            return;
        }

        $formateadas = [];

        foreach ($notas as $alumnoId => $item) {
            if (is_array($item)) {
                foreach (self::CAMPOS_NOTA as $campo) {
                    if (isset($item[$campo]) && is_string($item[$campo])) {
                        $item[$campo] = str_replace(',', '.', trim($item[$campo]));
                    }
                }
            }

            $formateadas[$alumnoId] = $item;
        }

        $request->merge(['notas' => $formateadas]);
    }

    public function store(Request $request)
    {
        $this->normalizarDecimales($request);

        $sistema = $this->sistemaEvaluacion();
        $opciones = $this->opcionesNotas($sistema);
        $parcialesValidos = array_column($opciones, 'parcial');

        $parcial = (int) $request->input('parcial');
        $tipo = $this->tipoDeParcial($parcial, $sistema);

        $rules = [
            'clase_docente_id' => 'required|exists:clases_docentes,id',
            'parcial' => 'required|integer|in:' . implode(',', $parcialesValidos),
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

        if ($validated['tipo_guardado'] === 'final') {
            $vacios = [];
            foreach ($validated['notas'] as $alumnoId => $item) {
                if ($tipo === 'examen') {
                    $completo = isset($item['nota_examen']) && $item['nota_examen'] !== '' && $item['nota_examen'] !== null;
                } else {
                    $completo = true;
                    foreach (['fii', 'ffi', 'fig', 'ffg', 'proyecto_integrador', 'evaluacion_parcial'] as $campo) {
                        if (!isset($item[$campo]) || $item[$campo] === '' || $item[$campo] === null) {
                            $completo = false;
                            break;
                        }
                    }
                }

                if (!$completo) {
                    $alumno = Alumno::find($item['alumno_id']);
                    $vacios[] = $alumno?->apellidos . ' ' . $alumno?->nombres;
                }
            }

            if (count($vacios) > 0) {
                throw ValidationException::withMessages([
                    'notas' => 'Faltan notas para: ' . implode(', ', array_slice($vacios, 0, 5)),
                ]);
            }
        }

        foreach ($validated['notas'] as $alumnoId => $item) {
            $matricula = Matricula::where('alumno_id', $item['alumno_id'])
                ->where('curso_id', $clase->curso_id)
                ->where('paralelo_id', $clase->paralelo_id)
                ->first();

            if (!$matricula) continue;

            if ($tipo === 'examen') {
                $data = [
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
            } else {
                $data = [
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

            if ($validated['tipo_guardado'] === 'final') {
                $data['estado'] = 'final';
            }

            Nota::updateOrCreate(
                [
                    'matricula_id' => $matricula->id,
                    'clase_docente_id' => $validated['clase_docente_id'],
                    'parcial' => $validated['parcial'],
                ],
                $data
            );
        }

        $msg = $validated['tipo_guardado'] === 'final'
            ? 'Notas guardadas y bloqueadas correctamente.'
            : 'Guardado parcial realizado. Puedes seguir editando.';

        return redirect()->route('docente.notas.index', [
            'clase_docente_id' => $validated['clase_docente_id'],
            'parcial' => $validated['parcial'],
        ])->with('success', $msg);
    }

    private function sistemaEvaluacion(): string
    {
        return Institucion::first()?->sistema_evaluacion ?? 'quimestre';
    }

    private function opcionesNotas(string $sistema): array
    {
        if ($sistema === 'trimestre') {
            return [
                ['parcial' => 1, 'label' => 'Trimestre 1', 'grupo' => 'Trimestres'],
                ['parcial' => 2, 'label' => 'Trimestre 2', 'grupo' => 'Trimestres'],
                ['parcial' => 3, 'label' => 'Trimestre 3', 'grupo' => 'Trimestres'],
            ];
        }

        return [
            ['parcial' => 1, 'label' => 'Parcial 1', 'grupo' => '1er Quimestre'],
            ['parcial' => 2, 'label' => 'Parcial 2', 'grupo' => '1er Quimestre'],
            ['parcial' => 3, 'label' => 'Examen Quimestral', 'grupo' => '1er Quimestre'],
            ['parcial' => 4, 'label' => 'Parcial 1', 'grupo' => '2do Quimestre'],
            ['parcial' => 5, 'label' => 'Parcial 2', 'grupo' => '2do Quimestre'],
            ['parcial' => 6, 'label' => 'Examen Quimestral', 'grupo' => '2do Quimestre'],
        ];
    }

    private function tipoDeParcial(int $parcial, string $sistema): string
    {
        if ($sistema === 'quimestre' && in_array($parcial, [3, 6])) {
            return 'examen';
        }
        return 'parcial';
    }

    private function notaParcialDe(?Nota $n): ?float
    {
        return app(CalculoNotas::class)->notaParcialDe($n);
    }

    private function resumenNotas($alumnos, $notasClase, string $sistema): array
    {
        return app(CalculoNotas::class)->resumen($alumnos, $notasClase, $sistema);
    }

    private function calcularPromedio($alumnos, $notasExistentes, string $tipo): ?array
    {
        if ($alumnos->isEmpty()) return null;

        $sumForm = 0; $sumEval = 0; $sumTotal = 0; $count = 0;

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

        if ($count === 0) return null;

        return [
            'form70' => $sumForm / $count,
            'eval30' => $sumEval / $count,
            'total' => $sumTotal / $count,
        ];
    }
}