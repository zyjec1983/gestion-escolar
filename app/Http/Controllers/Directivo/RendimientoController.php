<?php

namespace App\Http\Controllers\Directivo;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Directivo\Concerns\UsaPanelesDirectivos;
use App\Models\Curso;
use App\Models\Institucion;
use App\Models\Materia;
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
 * Rendimiento académico consolidado para Rectorado y Vicerrectorado.
 *
 * Qué hace: muestra promedios por nivel, curso, paralelo y asignatura, la
 * distribución de notas y el resumen por estudiante. Permite exportar a PDF
 * y Excel reutilizando las notas de las actas.
 * Conecta con: RendimientoAcademico, CalculoNotas, Nota y Matricula.
 * Rutas: /rector/rendimiento y /vicerrector/rendimiento.
 */
class RendimientoController extends Controller
{
    use UsaPanelesDirectivos;

    public function index(Request $request)
    {
        return view('directivo.rendimiento.index', $this->datos($request));
    }

    public function pdf(Request $request)
    {
        $datos = $this->datos($request);

        $pdf = Pdf::loadView('directivo.rendimiento.pdf', $datos)
            ->setPaper('a4', 'landscape');

        return $pdf->download('Rendimiento_' . $this->sufijoArchivo($datos) . '.pdf');
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
            'Content-Disposition' => 'attachment; filename="Rendimiento_'
                . $this->sufijoArchivo($datos) . '.xlsx"',
        ]);
    }

    /**
     * Datos comunes de pantalla y exportaciones.
     */
    private function datos(Request $request): array
    {
        $servicio = app(RendimientoAcademico::class);
        $periodo = $servicio->periodo($request->integer('periodo_id') ?: null);
        $corte = $this->corte($request->query('corte'));

        $filtros = [
            'curso_id' => $request->integer('curso_id') ?: null,
            'paralelo_id' => $request->integer('paralelo_id') ?: null,
            'materia_id' => $request->integer('materia_id') ?: null,
        ];

        $filas = $periodo
            ? $servicio->notasPorClase($periodo->id, $corte, $filtros)
            : collect();

        $notas = $filas->pluck('nota')->filter(fn ($n) => $n !== null);

        return [
            'prefijo' => $this->prefijo($request),
            'periodo' => $periodo,
            'periodos' => PeriodoLectivo::orderByDesc('fecha_inicio')->get(),
            'corte' => $corte,
            'corte_etiqueta' => $this->etiquetaCorte($corte),
            'cortes' => $this->cortes(),
            'filtros' => $filtros,
            'cursos' => Curso::orderBy('nombre')->get(),
            'paralelos' => Paralelo::orderBy('nombre')->get(),
            'materias' => Materia::orderBy('nombre')->get(),
            'filas' => $filas,
            'promedio' => $servicio->promedioGeneral($filas),
            'evaluados' => $notas->count(),
            'aprobados' => $notas->filter(fn ($n) => $n >= RendimientoAcademico::NOTA_MINIMA)->count(),
            'reprobados' => $notas->filter(fn ($n) => $n < RendimientoAcademico::NOTA_MINIMA)->count(),
            'resumen_niveles' => $servicio->resumenAgrupado($filas, 'nivel'),
            'resumen_cursos' => $servicio->resumenAgrupado($filas, 'curso'),
            'resumen_paralelos' => $servicio->resumenAgrupado($filas, 'paralelo'),
            'resumen_materias' => $servicio->resumenAgrupado($filas, 'materia'),
            'distribucion' => $servicio->distribucion($filas),
            'alumnos' => $servicio->resumenAlumnos($filas),
            'institucion' => Institucion::first(),
        ];
    }

    /** Opciones de corte para el formulario. */
    private function cortes(): array
    {
        return [
            'anual' => 'Promedio anual',
            'q1' => 'Primer quimestre',
            'q2' => 'Segundo quimestre',
            'p1' => 'Primer parcial',
            'p2' => 'Segundo parcial',
            'p3' => 'Examen del primer quimestre',
            'p4' => 'Tercer parcial',
            'p5' => 'Cuarto parcial',
            'p6' => 'Examen del segundo quimestre',
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

    /** Construye el libro de Excel con resúmenes y estudiantes. */
    private function construirExcel(array $datos): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $hoja = $spreadsheet->getActiveSheet();
        $hoja->setTitle('Rendimiento');

        $titulo = 'RENDIMIENTO ACADÉMICO · ' . strtoupper($datos['corte_etiqueta']);

        $hoja->mergeCells('A1:H1');
        $hoja->setCellValue('A1', strtoupper($datos['institucion']?->nombre ?? 'Unidad Educativa'));
        $hoja->mergeCells('A2:H2');
        $hoja->setCellValue('A2', $titulo);
        $hoja->mergeCells('A3:H3');
        $hoja->setCellValue('A3', 'Periodo: ' . ($datos['periodo']?->nombre ?? 'Sin periodo')
            . ' · Generado: ' . now()->format('d/m/Y H:i'));

        foreach (['A1' => 14, 'A2' => 12] as $celda => $tam) {
            $hoja->getStyle($celda)->getFont()->setBold(true)->setSize($tam);
        }

        $hoja->getStyle('A1:A3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $fila = 5;
        $encabezados = ['Nivel', 'Evaluados', 'Promedio', 'Aprobados', 'Reprobados'];
        $this->escribirFila($hoja, $fila, $encabezados);
        $hoja->getStyle('A' . $fila . ':E' . $fila)->getFont()->setBold(true);

        foreach ($datos['resumen_niveles'] as $indice => $resumen) {
            $this->escribirFila($hoja, $fila + 1 + $indice, [
                $resumen['etiqueta'],
                $resumen['evaluados'],
                $resumen['promedio'] === null ? '—' : number_format($resumen['promedio'], 2, ',', '.'),
                $resumen['aprobados'],
                $resumen['reprobados'],
            ]);
        }

        $hoja->getColumnDimension('A')->setWidth(28);
        foreach (['B', 'C', 'D', 'E'] as $letra) {
            $hoja->getColumnDimension($letra)->setWidth(14);
        }

        $rango = 'A' . $fila . ':E' . $hoja->getHighestRow();
        $hoja->getStyle($rango)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        $estudiantes = $spreadsheet->createSheet();
        $estudiantes->setTitle('Estudiantes');
        $estudiantes->mergeCells('A1:F1');
        $estudiantes->setCellValue('A1', 'RESUMEN POR ESTUDIANTE');
        $estudiantes->getStyle('A1')->getFont()->setBold(true)->setSize(12);

        $filaEstudiantes = 3;
        $this->escribirFila($estudiantes, $filaEstudiantes, [
            'Estudiante', 'Curso', 'Paralelo', 'Promedio', 'Materias', 'Reprobadas',
        ]);
        $estudiantes->getStyle('A' . $filaEstudiantes . ':F' . $filaEstudiantes)->getFont()->setBold(true);

        foreach ($datos['alumnos'] as $indice => $alumno) {
            $this->escribirFila($estudiantes, $filaEstudiantes + 1 + $indice, [
                trim($alumno['alumno']->apellidos . ' ' . $alumno['alumno']->nombres),
                $alumno['curso'] ?? '',
                $alumno['paralelo'] ?? '',
                $alumno['promedio'] === null ? '—' : number_format($alumno['promedio'], 2, ',', '.'),
                $alumno['materias'],
                $alumno['reprobadas'],
            ]);
        }

        $estudiantes->getColumnDimension('A')->setWidth(38);
        foreach (['B', 'C', 'D', 'E', 'F'] as $letra) {
            $estudiantes->getColumnDimension($letra)->setWidth(12);
        }

        $rangoEstudiantes = 'A' . $filaEstudiantes . ':F' . $estudiantes->getHighestRow();
        $estudiantes->getStyle($rangoEstudiantes)
            ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        return $spreadsheet;
    }

    private function escribirFila($hoja, int $fila, array $valores): void
    {
        foreach ($valores as $indice => $valor) {
            $hoja->setCellValue([$indice + 1, $fila], $valor);
        }
    }
}
