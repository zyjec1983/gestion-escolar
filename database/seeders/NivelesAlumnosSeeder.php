<?php

namespace Database\Seeders;

use App\Models\Alumno;
use App\Models\Curso;
use App\Models\Matricula;
use App\Models\Paralelo;
use App\Models\PeriodoLectivo;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class NivelesAlumnosSeeder extends Seeder
{
    private const ALUMNOS_POR_PARALELO = 10;

    /** curso => [nombre, codigo, paralelos] */
    private const NIVELES = [
        ['OCTAVO GRADO', 'OCT', ['A', 'B', 'C', 'D']],
        ['DECIMO GRADO', 'DEC', ['A', 'B', 'C', 'D']],
        ['PRIMERO DE BACHILLERATO', 'P1B', ['A', 'B', 'C', 'D']],
        ['SEGUNDO DE BACHILLERATO - CIENCIAS', '2BC', ['A', 'B']],
        ['SEGUNDO DE BACHILLERATO - COMERCIO', '2BM', ['A', 'B']],
        ['SEGUNDO DE BACHILLERATO - CONTABILIDAD', '2BCT', ['A', 'B']],
        ['SEGUNDO DE BACHILLERATO - INFORMATICA', '2BI', ['A', 'B']],
        ['SEGUNDO DE BACHILLERATO - FISICO MATEMATICA', '2BF', ['A', 'B']],
    ];

    private const NOMBRES_M = [
        'JOSÉ LUIS', 'CARLOS ANDRÉS', 'MIGUEL ÁNGEL', 'PEDRO PABLO', 'DIEGO FERNANDO',
        'ANDRÉS FELIPE', 'RICARDO MANUEL', 'JAVIER ALEJANDRO', 'FELIPE SANTIAGO', 'SEBASTIÁN EMILIO',
        'VICENTE RAMÓN', 'GUSTAVO ADOLFO', 'EDUARDO JAVIER', 'MARCO VINICIO', 'DANIEL ARTURO',
        'FERNANDO JOSUÉ', 'ROBERTO ISRAEL', 'JORGE LUIS', 'HUGO FERNANDO', 'RAMIRO GABRIEL',
        'ISAAC DAVID', 'AARÓN BENJAMÍN', 'BYRON STEVEN', 'FRANKLIN ISAAC', 'ALEXIS RODRIGO',
        'JONATHAN ALEXIS', 'SANTIAGO NICOLÁS', 'VÍCTOR HUGO', 'MAURICIO XAVIER', 'WILSON GERMÁN',
    ];

    private const NOMBRES_F = [
        'MARÍA FERNANDA', 'SOFÍA ALEJANDRA', 'LAURA CAROLINA', 'VALENTINA ISABEL', 'CAMILA ESTEFANÍA',
        'GABRIELA MARIANA', 'NICOLE ALEXANDRA', 'DANIELA ESTER', 'PAULA ANDREA', 'KAREN LISETTE',
        'DIANA MARCELA', 'EVELYN TATIANA', 'LILIANA BEATRIZ', 'JENNIFER KATHERINE', 'ANDREA MICHELLE',
        'ALISSON DAYANA', 'YADIRA MONSERRATE', 'MELISSA NATHALY', 'KATHERINE ESTEFANÍA', 'LISSETH CAROLINA',
        'CINDY MICHELLE', 'LADY DIANA', 'JOSSELYN MARIBEL', 'ESTEFANÍA MARÍA', 'FÁTIMA CARMITA',
        'YOMIRA MICHELLE', 'ARIANA BELÉN', 'SAMANTHA ELIZABETH', 'NAYELI ESTHER', 'GÉNESIS NICOLE',
    ];

    private const APELLIDOS = [
        'ZAMBRANO MENDOZA', 'TORRES VILLEGAS', 'GÓMEZ PALACIOS', 'RUIZ CÁRDENAS', 'MOLINA CEDEÑO',
        'VEGA LOOR', 'HERRERA PINCAY', 'PAZ SANTANA', 'DELGADO ROMERO', 'MACÍAS GIL',
        'PLAZA ALCÍVAR', 'SALAZAR CEDEÑO', 'BRAVO MENDOZA', 'VILLACÍS MEJÍA', 'RODRÍGUEZ LOOR',
        'MACENCINI PINTO', 'INTRIAGO ALCÍVAR', 'MERCHÁN SANTOS', 'ZAMORA BRIONES', 'BAQUE RIVADENEIRA',
        'CABRERA VALDIVIESO', 'AGUIRRE CHÁVEZ', 'SARANGO MORA', 'YAGUAL BURGOS', 'BARRETO REYES',
        'CHOEZ VILLAMAR', 'SUÁREZ CARRIÓN', 'GUTIÉRREZ PINEDA', 'PANCHI ULLAURI', 'MOREIRA ARTEAGA',
    ];

    public function run(): void
    {
        $periodoId = PeriodoLectivo::where('activo', true)->value('id')
            ?? PeriodoLectivo::max('id');

        // La cédula es "09" + 8 dígitos de secuencia, así que la secuencia se
        // lee quitando el prefijo. Un (int) sobre la cédula completa daría
        // 900000100 y desborde el formato.
        $siguiente = DB::table('alumnos')
            ->whereNotNull('numero_cedula')
            ->pluck('numero_cedula')
            ->map(fn ($c) => (int) (str_starts_with($c, '09') ? substr($c, 2) : $c))
            ->max() + 1;

        $usados = Alumno::get()
            ->map(fn (Alumno $a) => mb_strtoupper($a->nombres . ' ' . $a->apellidos))
            ->flip()
            ->all();

        $pool = $this->poolNombresSinRepetir($usados);
        $cursor = 0;

        $creados = 0;
        $lineas = [];

        foreach (self::NIVELES as [$nombre, $codigo, $letras]) {
            $curso = Curso::firstOrCreate(['nombre' => $nombre], ['codigo' => $codigo]);

            foreach ($letras as $letra) {
                $paralelo = Paralelo::firstOrCreate(['nombre' => $letra]);

                // Si el paralelo ya tiene matrículas, no se vuelve a generar.
                if (Matricula::where('curso_id', $curso->id)->where('paralelo_id', $paralelo->id)->exists()) {
                    $this->command?->warn("  {$nombre} {$letra}: ya tiene alumnos, se omite.");

                    continue;
                }

                [$lote, $cursor] = $this->loteSinNombresRepetidos($pool, $cursor);

                foreach ($lote as $persona) {
                    $cedula = sprintf('09%08d', $siguiente + $creados);

                    $representante = User::create([
                        'name' => "REP. {$persona['apellidos']} DE {$persona['nombres']}",
                        'email' => "rep.{$cedula}@fake.edu.ec",
                        'password' => bcrypt('password'),
                        'rol' => 'REPRESENTANTE',
                        'institucion_id' => 1,
                    ]);

                    $alumno = Alumno::create([
                        'codigo' => $cedula,
                        'numero_cedula' => $cedula,
                        'nombres' => $persona['nombres'],
                        'apellidos' => $persona['apellidos'],
                        'genero' => $persona['genero'],
                        'representante_user_id' => $representante->id,
                    ]);

                    $usuario = User::create([
                        'name' => $persona['nombres'],
                        'apellidos' => $persona['apellidos'],
                        'email' => "est.{$cedula}@fake.edu.ec",
                        'password' => bcrypt('password'),
                        'rol' => 'ESTUDIANTE',
                        'institucion_id' => 1,
                    ]);

                    $alumno->update(['user_id' => $usuario->id]);

                    Matricula::create([
                        'codigo_matricula' => 'MAT-' . $cedula,
                        'alumno_id' => $alumno->id,
                        'curso_id' => $curso->id,
                        'paralelo_id' => $paralelo->id,
                        'periodo_id' => $periodoId,
                    ]);

                    $lineas[] = "est.{$cedula}@fake.edu.ec              password      ESTUDIANTE";
                    $lineas[] = "rep.{$cedula}@fake.edu.ec               password      REPRESENTANTE";
                    $creados++;
                }

                $this->command?->info("  {$nombre} \"{$letra}\": 10 alumnos.");
            }
        }

        if ($lineas) {
            $ruta = database_path('../usuarios.txt');
            file_put_contents(
                $ruta,
                PHP_EOL . '--- Alumnos generados por NivelesAlumnosSeeder ---' . PHP_EOL
                . implode(PHP_EOL, $lineas) . PHP_EOL,
                FILE_APPEND
            );
        }

        $this->command?->info("Total alumnos creados: {$creados}");
    }

    /**
     * Toma el siguiente lote de alumnos garantizando que no se repita un
     * nombre dentro del mismo paralelo. Devuelve el lote y la posición del
     * pool donde debe seguir el siguiente lote, que no es simplemente
     * + ALUMNOS_POR_PARALELO porque se pueden saltar entradas repetidas.
     *
     * @param  array<int, array{nombres: string, apellidos: string, genero: string}>  $pool
     * @return array{0: array<int, array{nombres: string, apellidos: string, genero: string}>, 1: int}
     */
    private function loteSinNombresRepetidos(array $pool, int $desde): array
    {
        $lote = [];
        $tomados = [];
        $pos = $desde;

        for (; $pos < count($pool) && count($lote) < self::ALUMNOS_POR_PARALELO; $pos++) {
            if (isset($tomados[$pool[$pos]['nombres']])) {
                continue;
            }

            $tomados[$pool[$pos]['nombres']] = true;
            $lote[] = $pool[$pos];
        }

        return [$lote, $pos];
    }

    /**
     * Todas las combinaciones nombre + apellido, alternando sexo y sin repetir
     * ninguna que ya exista en la base.
     *
     * @param  array<int, int>  $usados
     * @return array<int, array{nombres: string, apellidos: string, genero: string}>
     */
    private function poolNombresSinRepetir(array $usados): array
    {
        $pool = [];
        $generos = ['masculino', 'femenino'];

        for ($i = 0; $i < count(self::NOMBRES_M) + count(self::NOMBRES_F); $i++) {
            $genero = $generos[$i % 2];
            $nombres = $genero === 'masculino'
                ? self::NOMBRES_M[(int) floor($i / 2)]
                : self::NOMBRES_F[(int) floor($i / 2)];

            foreach (self::APELLIDOS as $apellidos) {
                $clave = mb_strtoupper($nombres . ' ' . $apellidos);

                if (isset($usados[$clave])) {
                    continue;
                }

                $usados[$clave] = true;
                $pool[] = compact('nombres', 'apellidos', 'genero');
            }
        }

        // Mezcla determinista para que un mismo paralelo no salga con un solo nombre.
        mt_srand(20260921);
        shuffle($pool);

        return $pool;
    }
}
