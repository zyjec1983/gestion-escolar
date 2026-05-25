<?php

namespace Database\Seeders;

use App\Models\Matricula;
use Illuminate\Database\Seeder;

class MatriculaSeeder extends Seeder
{
    public function run(): void
    {
        Matricula::create([
            'codigo_matricula' => 'ALV-016',
            'alumno_id' => 1,
            'curso_id' => 1,
            'paralelo_id' => 1,
            'periodo_id' => 1,
        ]);

        Matricula::create([
            'codigo_matricula' => 'AND-291',
            'alumno_id' => 2,
            'curso_id' => 1,
            'paralelo_id' => 1,
            'periodo_id' => 1,
        ]);
    }
}
