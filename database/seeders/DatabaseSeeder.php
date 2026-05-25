<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use \Illuminate\Database\Console\Seeds\WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            InstitucionSeeder::class,
            PeriodoLectivoSeeder::class,
            CursoSeeder::class,
            ParaleloSeeder::class,
            MateriaSeeder::class,
            UserSeeder::class,
            AlumnoSeeder::class,
            ClaseDocenteSeeder::class,
            MatriculaSeeder::class,
            CircularSeeder::class,
            TipoObservacionSeeder::class,
            ObservacionSeeder::class,
            TipoActividadSeeder::class,
            MensajeSeeder::class,
            UnidadSeeder::class,
            AdminSeeder::class,
        ]);
    }
}
