<?php

namespace Database\Factories;

use App\Models\Institucion;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    protected static ?string $password;

    protected $model = User::class;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'apellidos' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ?? Hash::make('password'),
            'remember_token' => Str::random(10),
            'rol' => 'DOCENTE',
            'institucion_id' => Institucion::factory(),
            'telefono' => fake()->phoneNumber(),
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'rol' => 'ADMIN',
        ]);
    }

    public function docente(): static
    {
        return $this->state(fn (array $attributes) => [
            'rol' => 'DOCENTE',
        ]);
    }

    public function representante(): static
    {
        return $this->state(fn (array $attributes) => [
            'rol' => 'REPRESENTANTE',
        ]);
    }
}
