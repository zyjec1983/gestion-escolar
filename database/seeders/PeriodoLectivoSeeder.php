<?php

namespace Database\Seeders;

use App\Models\PeriodoLectivo;
use Illuminate\Database\Seeder;

class PeriodoLectivoSeeder extends Seeder
{
    public function run(): void
    {
        PeriodoLectivo::create([
            'nombre' => '2026 - 2027',
            'fecha_inicio' => '2026-04-01',
            'fecha_fin' => '2027-03-31',
            'activo' => true,
        ]);
    }
}
