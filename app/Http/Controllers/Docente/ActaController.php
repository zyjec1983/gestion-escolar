<?php

namespace App\Http\Controllers\Docente;

use App\Http\Controllers\Controller;
use App\Models\Alumno;
use App\Models\ClaseDocente;
use App\Models\Institucion;
use App\Models\Matricula;
use App\Models\Nota;
use App\Services\CalculoNotas;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ActaController extends Controller
{
    public function index(Request $request)
    {
        $datos = $this->datosActa($request);

        return view('docente.actas.index', $datos);
    }

    public function exportarPdf(Request $request)
    {
        $datos = $this->datosActa($request);
        $datos['orientacion'] = $datos['tipo'] === 'parcial' ? 'portrait' : 'landscape';

        $pdf = Pdf::loadView('docente.actas.pdf', $datos)
            ->setPaper('a4', $datos['orientacion']);

        $nombre = $this->nombreArchivo($datos) . '.pdf';

        return $pdf->download($nombre);
    }

    public function exportarExcel(Request $request)
    {
        $datos = $this->datosActa($request);
        $spreadsheet = $this->construirExcel($datos);

        ob_start();
        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        $contenido = ob_get_clean();

        return response($contenido, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $this->nombreArchivo($datos) . '.xlsx"',
        ]);
    }

    private function datosActa(Request $request): array
    {
        $user = $request->user();
        $clases = ClaseDocente::with(['curso', 'paralelo', 'materia', 'docente'])
            ->where('user_id', $user->id)
            ->get();

        $claseId = $request->query('clase_docente_id', $clases->first()?->id);
        $clase = $clases->firstWhere('id', $claseId) ?? $clases->first();

        $sistema = Institucion::first()?->sistema_evaluacion ?? 'quimestre';
        $opciones = $this->opcionesNotas($sistema);
        $tipo = $request->query('tipo', 'quimestral') === 'parcial' ? 'parcial' : 'quimestral';
        $parcial = (int) $request->query('parcial', ($tipo === 'parcial' ? $opciones[0]['parcial'] ?? 1 : 1));

        $institucion = Institucion::first();
        $filas = [];

        if ($clase) {
            $alumnos = Alumno::whereHas('matriculas', function ($q) use ($clase) {
                $q->where('curso_id', $clase->curso_id)
                  ->where('paralelo_id', $clase->paralelo_id);
            })->orderBy('apellidos')->orderBy('nombres')->get();

            $matriculasIds = Matricula::where('curso_id', $clase->curso_id)
                ->where('paralelo_id', $clase->paralelo_id)
                ->pluck('id');

            $notasClase = Nota::whereIn('matricula_id', $matriculasIds)
                ->where('clase_docente_id', $clase->id)
                ->get()
                ->groupBy('alumno_id');

            $calculo = app(CalculoNotas::class);

            if ($tipo === 'parcial') {
                foreach ($alumnos as $alumno) {
                    $n = ($notasClase[$alumno->id] ?? collect())->firstWhere('parcial', $parcial);
                    $esExamen = $this->esExamen($parcial, $sistema);
                    $nota = $esExamen
                        ? ($n?->nota_examen !== null ? (float) $n->nota_examen : null)
                        : $calculo->notaParcialDe($n);

                    $filas[] = [
                        'alumno' => $alumno,
                        'propios' => $n,
                        'nota' => $nota,
                        'esExamen' => $esExamen,
                        'estado' => $n?->estado,
                    ];
                }
            } else {
                $filas = $calculo->resumen($alumnos, $notasClase, $sistema);
            }
        }

        $opActual = collect($opciones)->firstWhere('parcial', $parcial);

        return compact('clases', 'clase', 'sistema', 'opciones', 'tipo', 'parcial', 'institucion', 'filas', 'opActual');
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

    private function esExamen(int $parcial, string $sistema): bool
    {
        return $sistema === 'quimestre' && in_array($parcial, [3, 6]);
    }

    private function nombreArchivo(array $datos): string
    {
        $clase = $datos['clase'];
        $parte = implode('_', [
            $clase?->curso->nombre ?? 'curso',
            $clase?->paralelo->nombre ?? 'paralelo',
            $clase?->materia->nombre ?? 'materia',
        ]);

        $tipo = $datos['tipo'] === 'parcial' ? 'P' . $datos['parcial'] : ($datos['sistema'] === 'quimestre' ? 'Quimestral' : 'Anual');

        $sano = preg_replace('/[^A-Za-z0-9_-]+/', '_', $parte);
        $sano = preg_replace('/_+/', '_', trim($sano, '_'));

        return "Acta_{$tipo}_" . ($sano !== '' ? $sano : 'general');
    }

    private function construirExcel(array $datos): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Acta');

        $clase = $datos['clase'];
        $sistema = $datos['sistema'];
        $tipo = $datos['tipo'];
        $filas = $datos['filas'];
        $institucion = $datos['institucion'];

        $titulo = $tipo === 'parcial'
            ? 'ACTA PARCIAL - ' . strtoupper(collect($datos['opciones'])->firstWhere('parcial', $datos['parcial'])['label'] ?? '')
            : ($sistema === 'quimestre' ? 'ACTA QUIMESTRAL' : 'ACTA TRIMESTRAL - ANUAL');

        if ($tipo === 'parcial') {
            $esExamen = $filas[0]['esExamen'] ?? false;
            $totalColumnas = $esExamen ? 6 : 11;
        } elseif ($sistema === 'quimestre') {
            $totalColumnas = 18;
        } else {
            $totalColumnas = 7;
        }

        $ultimaLetra = Coordinate::stringFromColumnIndex($totalColumnas);

        $sheet->mergeCells('A1:' . $ultimaLetra . '1');
        $sheet->setCellValue('A1', strtoupper($institucion?->nombre ?? 'Unidad Educativa'));
        $sheet->mergeCells('A2:' . $ultimaLetra . '2');
        $sheet->setCellValue('A2', strtoupper($titulo));
        $sheet->mergeCells('A3:' . $ultimaLetra . '3');
        $sheet->setCellValue('A3', 'Asignatura: ' . ($clase?->materia->nombre ?? '') . ' - Curso: ' . ($clase?->curso->nombre ?? '') . ' - Paralelo: ' . ($clase?->paralelo->nombre ?? ''));
        $sheet->mergeCells('A4:' . $ultimaLetra . '4');
        $sheet->setCellValue('A4', 'Docente: ' . ($clase?->docente->apellidos ?? '') . ' ' . ($clase?->docente->name ?? ''));
        $sheet->mergeCells('A5:' . $ultimaLetra . '5');
        $sheet->setCellValue('A5', 'Fecha: ' . now()->format('d/m/Y'));

        foreach (['A1' => 14, 'A2' => 12, 'A4' => 11] as $celda => $tam) {
            $sheet->getStyle($celda)->getFont()->setBold(true)->setSize($tam);
        }
        $sheet->getStyle('A1:A5')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $filaEncabezado = 7;

        if ($tipo === 'parcial') {
            $columnas = array_merge(
                ['N.º', 'Cédula', 'Apellidos y Nombres'],
                $esExamen ? ['Examen Quimestral'] : ['FII', 'FFI', 'FIG', 'FFG', 'Pr. Integrador', 'Eval. Parcial'],
                ['Nota', 'Estado']
            );
            $this->escribirFila($sheet, $filaEncabezado, $columnas);

            foreach ($filas as $i => $f) {
                $fila = $filaEncabezado + 1 + $i;
                $valores = [$i + 1, $f['alumno']->numero_cedula, $f['alumno']->apellidos . ' ' . $f['alumno']->nombres];
                if ($esExamen) {
                    $valores[] = formatearNota($f['nota']);
                } else {
                    $p = $f['propios'];
                    foreach (['fii', 'ffi', 'fig', 'ffg', 'proyecto_integrador', 'evaluacion_parcial'] as $campo) {
                        $valores[] = (float) ($p?->{$campo} ?? 0) > 0 ? formatearNota((float) $p->{$campo}) : '—';
                    }
                }
                $valores[] = formatearNota($f['nota']);
                $valores[] = $f['estado'] === 'final' ? 'Final' : ($f['estado'] === 'parcial' ? 'Parcial' : 'Pendiente');
                $this->escribirFila($sheet, $fila, $valores);
            }
        } elseif ($sistema === 'quimestre') {
            $columnas = ['N.º', 'Cédula', 'Apellidos y Nombres', 'P1', 'P2', 'Prom', '70%', 'T.Exam', '30%', 'Q1', 'P3', 'P4', 'Prom2', '70%', 'T.Exam2', '30%', 'Q2', 'AÑO'];
            $this->escribirFila($sheet, $filaEncabezado, $columnas);

            foreach ($filas as $i => $r) {
                $prom1 = ($r['p1'] !== null && $r['p2'] !== null) ? ($r['p1'] + $r['p2']) / 2 : null;
                $v701 = $prom1 !== null ? $prom1 * 0.7 : null;
                $v301 = $r['te1'] !== null ? $r['te1'] * 0.3 : null;
                $prom2 = ($r['p3'] !== null && $r['p4'] !== null) ? ($r['p3'] + $r['p4']) / 2 : null;
                $v702 = $prom2 !== null ? $prom2 * 0.7 : null;
                $v302 = $r['te2'] !== null ? $r['te2'] * 0.3 : null;

                $this->escribirFila($sheet, $filaEncabezado + 1 + $i, [
                    $i + 1, $r['alumno']->numero_cedula, $r['alumno']->apellidos . ' ' . $r['alumno']->nombres,
                    formatearNota($r['p1']), formatearNota($r['p2']), formatearNota($prom1), formatearNota($v701),
                    formatearNota($r['te1']), formatearNota($v301), formatearNota($r['q1']),
                    formatearNota($r['p3']), formatearNota($r['p4']), formatearNota($prom2), formatearNota($v702),
                    formatearNota($r['te2']), formatearNota($v302), formatearNota($r['q2']), formatearNota($r['anio']),
                ]);
            }
        } else {
            $columnas = ['N.º', 'Cédula', 'Apellidos y Nombres', 'Trimestre 1', 'Trimestre 2', 'Trimestre 3', 'AÑO'];
            $this->escribirFila($sheet, $filaEncabezado, $columnas);

            foreach ($filas as $i => $r) {
                $this->escribirFila($sheet, $filaEncabezado + 1 + $i, [
                    $i + 1, $r['alumno']->numero_cedula, $r['alumno']->apellidos . ' ' . $r['alumno']->nombres,
                    formatearNota($r['t1']), formatearNota($r['t2']), formatearNota($r['t3']), formatearNota($r['anio']),
                ]);
            }
        }

        $rango = 'A' . $filaEncabezado . ':' . $ultimaLetra . $sheet->getHighestRow();
        $sheet->getStyle($rango)->getFont()->setSize(10);
        $sheet->getStyle($rango)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $sheet->getStyle($filaEncabezado . ':' . $filaEncabezado)->getFont()->setBold(true);
        $sheet->getStyle($filaEncabezado . ':' . $filaEncabezado)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->getColumnDimension('A')->setWidth(5);
        $sheet->getColumnDimension('B')->setWidth(12);
        $sheet->getColumnDimension('C')->setWidth(34);
        $letra = 'D';
        while ($letra !== $ultimaLetra) {
            $sheet->getColumnDimension($letra)->setWidth(9);
            $letra++;
        }
        $sheet->getColumnDimension($ultimaLetra)->setWidth(9);

        return $spreadsheet;
    }

    private function escribirFila($sheet, int $fila, array $valores): void
    {
        foreach ($valores as $indice => $valor) {
            $sheet->setCellValue([$indice + 1, $fila], $valor);
        }
    }
}