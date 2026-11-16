<?php

namespace Database\Factories;

use App\Models\Materia;
use Illuminate\Database\Eloquent\Factories\Factory;

class MateriaFactory extends Factory
{
    protected $model = Materia::class;

    public function definition(): array
    {
        return [
            'nombre' => ucfirst(fake()->unique()->words(2, true)),
            'area' => fake()->randomElement([
                'Ciencias Naturales',
                'Lengua y Literatura',
                'Ciencias Sociales',
                'Matemáticas',
                'Lenguas Extranjeras',
            ]),
            'codigo' => fake()->unique()->regexify('[A-Z]{3}[0-9]{3}'),
            'activo' => true,
        ];
    }

    public function inactiva(): static
    {
        return $this->state(fn (array $attributes) => [
            'activo' => false,
        ]);
    }
}
