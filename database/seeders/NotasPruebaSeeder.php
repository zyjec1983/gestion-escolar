<?php

namespace Database\Seeders;

use App\Models\ClaseDocente;
use App\Models\Curso;
use App\Models\Materia;
use App\Models\Matricula;
use App\Models\Nota;
use App\Models\PeriodoLectivo;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Llena las notas de prueba de todos los estudiantes del periodo activo.
 *
 * Qué hace: garantiza una clase por curso/paralelo/materia, asigna a cada
 * estudiante un perfil de rendimiento estable (de 10 excelente a insuficiente)
 * y carga los seis parciales del sistema de quimestre con notas variadas.
 * Conecta con: Curso, Materia, ClaseDocente, Matricula y Nota.
 * Ejecutar con: php artisan db:seed --class=NotasPruebaSeeder
 */
class NotasPruebaSeeder extends Seeder
{
    /**
     * Perfiles base de rendimiento. Se repiten en orden para que cada curso
     * tenga estudiantes excelentes, buenos, regulares, bajos e insuficientes.
     */
    private const PERFILES = [
        10.0, 9.8, 9.6, 9.4, 9.1, 8.9, 8.7, 8.5, 8.2, 8.0,
        7.8, 7.6, 7.4, 7.2, 7.0, 6.6, 6.1, 5.5, 4.6, 3.2,
    ];

    /** Parciales del sistema de quimestre: 3 y 6 son exámenes. */
    private const PARCIALES = [1, 2, 3, 4, 5, 6];

    public function run(): void
    {
        $periodo = PeriodoLectivo::where('activo', true)->first();
        $cursos = Curso::orderBy('nombre')->get();
        $materias = Materia::orderBy('nombre')->get();
        $docentes = User::where('rol', 'DOCENTE')->orderBy('id')->pluck('id')->values();

        if ($cursos->isEmpty() || $materias->isEmpty() || $docentes->isEmpty()) {
            $this->command?->warn('Faltan cursos, materias o docentes; no se generaron notas.');

            return;
        }

        $matriculas = Matricula::query()
            ->when($periodo, fn ($query) => $query->where('periodo_id', $periodo->id))
            ->orderBy('alumno_id')
            ->get();

        if ($matriculas->isEmpty()) {
            $this->command?->warn('No hay matrículas en el periodo activo.');

            return;
        }

        // Índice global del alumno para asignar siempre el mismo perfil.
        $indiceAlumno = [];
        $indice = 0;

        foreach ($matriculas->pluck('alumno_id')->unique()->sort()->values() as $alumnoId) {
            $indiceAlumno[$alumnoId] = $indice++;
        }

        $clasesCreadas = 0;
        $notasGuardadas = 0;

        DB::transaction(function () use ($matriculas, $materias, $docentes, $indiceAlumno, &$clasesCreadas, &$notasGuardadas) {
            $clasesPorCombo = [];

            // Una clase por materia para cada curso/paralelo con matrículas.
            foreach ($matriculas->groupBy(fn (Matricula $m) => $m->curso_id.'-'.$m->paralelo_id) as $grupo) {
                $primera = $grupo->first();
                $combo = $primera->curso_id.'-'.$primera->paralelo_id;
                $clasesPorCombo[$combo] = [];

                foreach ($materias as $indiceMateria => $materia) {
                    $clase = ClaseDocente::where('curso_id', $primera->curso_id)
                        ->where('paralelo_id', $primera->paralelo_id)
                        ->where('materia_id', $materia->id)
                        ->orderBy('id')
                        ->first();

                    if (! $clase) {
                        $docenteId = ClaseDocente::where('materia_id', $materia->id)->value('user_id')
                            ?? $docentes[($primera->curso_id + $materia->id) % $docentes->count()];

                        $clase = ClaseDocente::create([
                            'user_id' => $docenteId,
                            'materia_id' => $materia->id,
                            'curso_id' => $primera->curso_id,
                            'paralelo_id' => $primera->paralelo_id,
                            'color_tarjeta' => $materia->color_tarjeta,
                        ]);

                        $clasesCreadas++;
                    }

                    $clasesPorCombo[$combo][] = [
                        'clase' => $clase,
                        'indice_materia' => $indiceMateria,
                    ];
                }
            }

            $filas = [];

            foreach ($matriculas as $matricula) {
                $combo = $matricula->curso_id.'-'.$matricula->paralelo_id;
                $indiceEstudiante = $indiceAlumno[$matricula->alumno_id] ?? 0;
                $perfil = self::PERFILES[$indiceEstudiante % count(self::PERFILES)];

                foreach ($clasesPorCombo[$combo] ?? [] as $item) {
                    foreach (self::PARCIALES as $parcial) {
                        $esExamen = in_array($parcial, [3, 6], true);
                        $valor = $this->valorNota($perfil, $indiceEstudiante, $item['indice_materia'], $parcial);

                        $filas[] = [
                            'matricula_id' => $matricula->id,
                            'alumno_id' => $matricula->alumno_id,
                            'clase_docente_id' => $item['clase']->id,
                            'parcial' => $parcial,
                            'tipo_nota' => $esExamen ? 'examen' : 'parcial',
                            'fii' => $esExamen ? 0 : $valor,
                            'ffi' => $esExamen ? 0 : $valor,
                            'fig' => $esExamen ? 0 : $valor,
                            'ffg' => $esExamen ? 0 : $valor,
                            'proyecto_integrador' => $esExamen ? 0 : $valor,
                            'evaluacion_parcial' => $esExamen ? 0 : $valor,
                            'nota_examen' => $esExamen ? $valor : null,
                            'estado' => 'final',
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];

                        if (count($filas) >= 500) {
                            $this->guardarFilas($filas);
                            $notasGuardadas += count($filas);
                            $filas = [];
                        }
                    }
                }
            }

            if ($filas !== []) {
                $this->guardarFilas($filas);
                $notasGuardadas += count($filas);
            }
        });

        $this->command?->info("Clases creadas: {$clasesCreadas}");
        $this->command?->info("Notas generadas o actualizadas: {$notasGuardadas}");
    }

    /** Nota estable por estudiante, materia y parcial, con una leve variación. */
    private function valorNota(float $perfil, int $indiceEstudiante, int $indiceMateria, int $parcial): float
    {
        // Los estudiantes excelentes conservan 10 exacto en todas sus materias.
        if ($perfil >= 10.0) {
            return 10.0;
        }

        $variacion = ((($indiceEstudiante * 7 + $indiceMateria * 3 + $parcial * 5) % 7) - 3) * 0.1;

        return round(max(1.0, min(10.0, $perfil + $variacion)), 2);
    }

    /** @param array<int, array<string, mixed>> $filas */
    private function guardarFilas(array $filas): void
    {
        Nota::upsert(
            $filas,
            ['matricula_id', 'clase_docente_id', 'parcial'],
            [
                'alumno_id', 'tipo_nota', 'fii', 'ffi', 'fig', 'ffg',
                'proyecto_integrador', 'evaluacion_parcial', 'nota_examen',
                'estado', 'updated_at',
            ]
        );
    }
}
