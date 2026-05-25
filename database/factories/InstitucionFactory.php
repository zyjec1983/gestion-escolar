<?php

namespace Database\Factories;

use App\Models\Institucion;
use Illuminate\Database\Eloquent\Factories\Factory;

class InstitucionFactory extends Factory
{
    protected $model = Institucion::class;

    public function definition(): array
    {
        return [
            'nombre' => 'Unidad Educativa Test',
            'subtitulo' => 'Test',
            'plataforma_proveedor' => 'test',
            'pre_registro_activo' => true,
        ];
    }

    public function inactivo(): static
    {
        return $this->state(fn (array $attributes) => [
            'pre_registro_activo' => false,
        ]);
    }
}
