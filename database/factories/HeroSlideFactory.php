<?php

namespace Database\Factories;

use App\Models\HeroSlide;
use Illuminate\Database\Eloquent\Factories\Factory;

class HeroSlideFactory extends Factory
{
    protected $model = HeroSlide::class;

    public function definition(): array
    {
        return [
            'titulo' => fake()->sentence(3),
            'subtitulo' => fake()->sentence(6),
            'imagen_path' => 'hero-slides/placeholder.jpg',
            'orden' => fake()->numberBetween(0, 10),
            'activo' => true,
        ];
    }

    public function inactivo(): static
    {
        return $this->state(fn (array $attributes) => ['activo' => false]);
    }
}
