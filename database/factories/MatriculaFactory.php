<?php

namespace Database\Factories;

use App\Models\Alumno;
use App\Models\Curso;
use App\Models\Matricula;
use App\Models\Paralelo;
use App\Models\PeriodoLectivo;
use Illuminate\Database\Eloquent\Factories\Factory;

class MatriculaFactory extends Factory
{
    protected $model = Matricula::class;

    public function definition(): array
    {
        return [
            'codigo_matricula' => 'MAT-' . fake()->unique()->numerify('#####'),
            'alumno_id' => Alumno::factory(),
            'curso_id' => Curso::factory(),
            'paralelo_id' => Paralelo::factory(),
            'periodo_id' => PeriodoLectivo::factory(),
        ];
    }
}
