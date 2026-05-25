<?php

namespace Database\Seeders;

use App\Models\TipoActividad;
use Illuminate\Database\Seeder;

class TipoActividadSeeder extends Seeder
{
    public function run(): void
    {
        TipoActividad::create(['codigo' => 'TAREA', 'nombre' => 'Tarea']);
        TipoActividad::create(['codigo' => 'EXAMEN', 'nombre' => 'Examen']);
        TipoActividad::create(['codigo' => 'TRABAJO', 'nombre' => 'Trabajo Grupal']);
        TipoActividad::create(['codigo' => 'LECTURA', 'nombre' => 'Lectura']);
    }
}
