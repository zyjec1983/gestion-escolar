<?php

namespace Database\Factories;

use App\Models\Curso;
use Illuminate\Database\Eloquent\Factories\Factory;

class CursoFactory extends Factory
{
    protected $model = Curso::class;

    public function definition(): array
    {
        return [
            'nombre' => fake()->randomElement(['NOVENO GRADO', 'DECIMO GRADO']),
            'codigo' => fake()->unique()->regexify('[A-Z0-9]{5}'),
        ];
    }
}
