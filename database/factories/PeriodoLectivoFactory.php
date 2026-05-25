<?php

namespace Database\Factories;

use App\Models\PeriodoLectivo;
use Illuminate\Database\Eloquent\Factories\Factory;

class PeriodoLectivoFactory extends Factory
{
    protected $model = PeriodoLectivo::class;

    public function definition(): array
    {
        return [
            'nombre' => '2026 - 2027',
            'fecha_inicio' => '2026-09-01',
            'fecha_fin' => '2027-06-30',
            'activo' => true,
        ];
    }
}
