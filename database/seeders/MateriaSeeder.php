<?php

namespace Database\Seeders;

use App\Models\Materia;
use Illuminate\Database\Seeder;

class MateriaSeeder extends Seeder
{
    public function run(): void
    {
        Materia::create(['nombre' => 'ACADEMIC WRITING', 'codigo' => 'AW', 'color_tarjeta' => '#E63946']);
        Materia::create(['nombre' => 'MATEMATICAS', 'codigo' => 'MAT', 'color_tarjeta' => '#4CC9F0']);
    }
}
