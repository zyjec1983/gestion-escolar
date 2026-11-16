<?php

namespace Database\Factories;

use App\Models\Curso;
use App\Models\CursoMateria;
use App\Models\Materia;
use Illuminate\Database\Eloquent\Factories\Factory;

class CursoMateriaFactory extends Factory
{
    protected $model = CursoMateria::class;

    public function definition(): array
    {
        return [
            'curso_id' => Curso::factory(),
            'materia_id' => Materia::factory(),
            'periodos_semanales' => fake()->numberBetween(1, 8),
            'orden' => 0,
        ];
    }
}
