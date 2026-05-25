<?php

namespace Database\Factories;

use App\Models\Curso;
use App\Models\Paralelo;
use App\Models\PeriodoLectivo;
use App\Models\PreRegistro;
use Illuminate\Database\Eloquent\Factories\Factory;

class PreRegistroFactory extends Factory
{
    protected $model = PreRegistro::class;

    public function definition(): array
    {
        return [
            'rep_tipo_documento' => 'cedula',
            'rep_numero_documento' => fake()->numerify('##########'),
            'rep_nombres' => fake()->firstName(),
            'rep_apellidos' => fake()->lastName(),
            'rep_email' => fake()->safeEmail(),
            'rep_telefono' => fake()->phoneNumber(),
            'alumno_tipo_documento' => 'cedula',
            'alumno_numero_documento' => fake()->numerify('##########'),
            'alumno_nombres' => fake()->firstName(),
            'alumno_apellidos' => fake()->lastName(),
            'alumno_genero' => fake()->randomElement(['masculino', 'femenino']),
            'alumno_fecha_nacimiento' => fake()->date('Y-m-d', '2015-12-31'),
            'alumno_tipo_sangre' => fake()->randomElement(['A+', 'A-', 'B+', 'B-', 'O+', 'O-', 'AB+', 'AB-']),
            'curso_id' => Curso::factory(),
            'paralelo_id' => Paralelo::factory(),
            'periodo_id' => PeriodoLectivo::factory(),
            'estado' => 'pendiente',
            'codigo_solicitud' => 'SOL-' . strtoupper(fake()->regexify('[A-Z0-9]{8}')),
            'tipo_solicitud' => 'nuevo_ingreso',
        ];
    }

    public function aprobado(): static
    {
        return $this->state(fn (array $attributes) => [
            'estado' => 'aprobado',
            'fecha_aprobacion' => now(),
        ]);
    }

    public function rechazado(): static
    {
        return $this->state(fn (array $attributes) => [
            'estado' => 'rechazado',
            'comentario_rechazo' => fake()->sentence(),
        ]);
    }
}
