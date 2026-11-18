<?php

namespace App\Http\Controllers\Directivo;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Directivo\Concerns\UsaPanelesDirectivos;
use App\Models\Alumno;
use App\Models\Curso;
use App\Models\Institucion;
use App\Models\Paralelo;
use App\Models\PeriodoLectivo;
use App\Services\RendimientoAcademico;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Estudiantes en riesgo académico.
 *
 * Qué hace: lista a los estudiantes que cumplen alguna regla de riesgo
 * (promedio bajo, materias reprobadas, asistencia crítica o NEE sin
 * adaptación) y muestra su ficha académica sin datos clínicos del DECE.
 * Conecta con: RendimientoAcademico, DeceNee y Observacion.
 * Rutas: /rector/estudiantes-riesgo y /vicerrector/estudiantes-riesgo.
 */
class EstudiantesRiesgoController extends Controller
{
    use UsaPanelesDirectivos;

    public function index(Request $request)
    {
        return view('directivo.estudiantes-riesgo.index', $this->datos($request));
    }

    public function show(Request $request, Alumno $alumno)
    {
        $datos = $this->datos($request);
        $datos['ficha'] = $datos['periodo']
            ? app(RendimientoAcademico::class)->fichaEstudiante($alumno, $datos['periodo']->id, $datos['corte'])
            : null;

        return view('directivo.estudiantes-riesgo.show', $datos);
    }

    public function pdf(Request $request)
    {
        $datos = $this->datos($request);

        $pdf = Pdf::loadView('directivo.estudiantes-riesgo.pdf', $datos)
            ->setPaper('a4', 'landscape');

        return $pdf->download('Estudiantes_riesgo_' . $this->sufijoArchivo($datos) . '.pdf');
    }

    public function excel(Request $request)
    {
        $datos = $this->datos($request);
        $spreadsheet = $this->construirExcel($datos);

        ob_start();
        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        $contenido = ob_get_clean();

        return response($contenido, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="Estudiantes_riesgo_'
                . $this->sufijoArchivo($datos) . '.xlsx"',
        ]);
    }

    /** Datos comunes de pantalla y exportaciones. */
    private function datos(Request $request): array
    {
        $servicio = app(RendimientoAcademico::class);
        $periodo = $servicio->periodo($request->integer('periodo_id') ?: null);
        $corte = $this->corte($request->query('corte'));

        $filtros = [
            'curso_id' => $request->integer('curso_id') ?: null,
            'paralelo_id' => $request->integer('paralelo_id') ?: null,
        ];

        $estudiantes = $periodo
            ? $servicio->estudiantesEnRiesgo($periodo->id, $corte, $filtros)
            : collect();

        $nivel = $request->query('nivel');

        if (in_array($nivel, ['rojo', 'amarillo'], true)) {
            $estudiantes = $estudiantes->where('nivel', $nivel)->values();
        }

        return [
            'prefijo' => $this->prefijo($request),
            'periodo' => $periodo,
            'periodos' => PeriodoLectivo::orderByDesc('fecha_inicio')->get(),
            'corte' => $corte,
            'corte_etiqueta' => $this->etiquetaCorte($corte),
            'cortes' => [
                'anual' => 'Promedio anual',
                'q1' => 'Primer quimestre',
                'q2' => 'Segundo quimestre',
            ],
            'filtros' => $filtros,
            'nivel' => $nivel,
            'cursos' => Curso::orderBy('nombre')->get(),
            'paralelos' => Paralelo::orderBy('nombre')->get(),
            'estudiantes' => $estudiantes,
            'total_rojo' => $estudiantes->where('nivel', 'rojo')->count(),
            'total_amarillo' => $estudiantes->where('nivel', 'amarillo')->count(),
            'institucion' => Institucion::first(),
        ];
    }

    /** Nombre seguro para los archivos exportados. */
    private function sufijoArchivo(array $datos): string
    {
        $partes = [
            $datos['periodo']?->nombre ?? 'periodo',
            $datos['corte'],
        ];

        $sano = preg_replace('/[^A-Za-z0-9_-]+/', '_', implode('_', $partes));
        $sano = preg_replace('/_+/', '_', trim((string) $sano, '_'));

        return $sano !== '' ? $sano : 'general';
    }

    /** Construye el libro de Excel del listado de riesgo. */
    private function construirExcel(array $datos): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $hoja = $spreadsheet->getActiveSheet();
        $hoja->setTitle('Estudiantes en riesgo');

        $hoja->mergeCells('A1:I1');
        $hoja->setCellValue('A1', strtoupper($datos['institucion']?->nombre ?? 'Unidad Educativa'));
        $hoja->mergeCells('A2:I2');
        $hoja->setCellValue('A2', 'ESTUDIANTES EN RIESGO · ' . strtoupper($datos['corte_etiqueta']));
        $hoja->mergeCells('A3:I3');
        $hoja->setCellValue('A3', 'Periodo: ' . ($datos['periodo']?->nombre ?? 'Sin periodo')
            . ' · Generado: ' . now()->format('d/m/Y H:i'));

        foreach (['A1' => 14, 'A2' => 12] as $celda => $tam) {
            $hoja->getStyle($celda)->getFont()->setBold(true)->setSize($tam);
        }

        $hoja->getStyle('A1:A3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $fila = 5;
        $this->escribirFila($hoja, $fila, [
            'Estudiante', 'Cédula', 'Curso', 'Paralelo', 'Promedio',
            'Materias', 'Reprobadas', 'Asistencia %', 'Semáforo',
        ]);
        $hoja->getStyle('A' . $fila . ':I' . $fila)->getFont()->setBold(true);

        foreach ($datos['estudiantes'] as $indice => $estudiante) {
            $this->escribirFila($hoja, $fila + 1 + $indice, [
                trim($estudiante['alumno']->apellidos . ' ' . $estudiante['alumno']->nombres),
                $estudiante['alumno']->numero_cedula,
                $estudiante['curso'] ?? '',
                $estudiante['paralelo'] ?? '',
                $estudiante['promedio'] === null ? '—' : number_format($estudiante['promedio'], 2, ',', '.'),
                $estudiante['materias'],
                $estudiante['reprobadas'],
                $estudiante['asistencia'] === null ? '—' : number_format($estudiante['asistencia'], 1, ',', '.'),
                $estudiante['nivel'] === 'rojo' ? 'Rojo' : 'Amarillo',
            ]);
        }

        $hoja->getColumnDimension('A')->setWidth(38);
        $hoja->getColumnDimension('B')->setWidth(14);
        foreach (['C', 'D', 'E', 'F', 'G', 'H', 'I'] as $letra) {
            $hoja->getColumnDimension($letra)->setWidth(13);
        }

        $rango = 'A' . $fila . ':I' . $hoja->getHighestRow();
        $hoja->getStyle($rango)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        return $spreadsheet;
    }

    private function escribirFila($hoja, int $fila, array $valores): void
    {
        foreach ($valores as $indice => $valor) {
            $hoja->setCellValue([$indice + 1, $fila], $valor);
        }
    }
}
