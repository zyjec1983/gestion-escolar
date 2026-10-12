<?php

namespace Database\Factories;

use App\Models\Alumno;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AlumnoFactory extends Factory
{
    protected $model = Alumno::class;

    public function definition(): array
    {
        return [
            'codigo' => now()->year . str_pad((string) fake()->unique()->numberBetween(1, 99999), 6, '0', STR_PAD_LEFT),
            'numero_cedula' => fake()->numerify('##########'),
            'nombres' => fake()->firstName(),
            'apellidos' => fake()->lastName(),
            'fecha_nacimiento' => fake()->date('Y-m-d', '2015-12-31'),
            'genero' => fake()->randomElement(['masculino', 'femenino']),
            // Por regla ningun alumno puede quedar sin representante, y la
            // columna es NOT NULL. Cada test que necesite otro representante lo
            // sobrescribe; esta cuenta solo sirve para cumplir la restriccion.
            'representante_user_id' => User::factory()->representante(),
        ];
    }
}
