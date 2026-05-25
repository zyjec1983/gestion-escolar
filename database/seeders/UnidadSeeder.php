<?php

namespace Database\Seeders;

use App\Models\Unidad;
use Illuminate\Database\Seeder;

class UnidadSeeder extends Seeder
{
    public function run(): void
    {
        Unidad::create([
            'materia_id' => 1,
            'titulo' => 'True stories: How to communicate facts and real stories effectively',
            'fecha_inicio' => '2026-04-20',
            'fecha_fin' => '2026-06-26',
        ]);
    }
}
