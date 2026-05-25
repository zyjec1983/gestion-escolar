<?php

namespace Database\Seeders;

use App\Models\Curso;
use Illuminate\Database\Seeder;

class CursoSeeder extends Seeder
{
    public function run(): void
    {
        Curso::create(['nombre' => 'NOVENO GRADO', 'codigo' => 'NOV']);
        Curso::create(['nombre' => 'DECIMO GRADO', 'codigo' => 'DEC']);
    }
}
