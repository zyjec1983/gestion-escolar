<?php

namespace Database\Seeders;

use App\Models\ClaseDocente;
use Illuminate\Database\Seeder;

class ClaseDocenteSeeder extends Seeder
{
    public function run(): void
    {
        $colores = ['#E63946', '#7209B7', '#4CC9F0', '#4AD66D'];
        $paralelos = [1, 2, 3, 4];

        foreach ($paralelos as $i => $paraleloId) {
            ClaseDocente::create([
                'user_id' => 1,
                'materia_id' => 1,
                'curso_id' => 1,
                'paralelo_id' => $paraleloId,
                'color_tarjeta' => $colores[$i],
            ]);
        }
    }
}
