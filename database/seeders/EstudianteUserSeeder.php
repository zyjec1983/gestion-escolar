<?php

namespace Database\Seeders;

use App\Models\Alumno;
use App\Models\User;
use Illuminate\Database\Seeder;

class EstudianteUserSeeder extends Seeder
{
    public function run(): void
    {
        Alumno::whereNull('user_id')->each(function (Alumno $alumno) {
            $email = strtolower('est.' . $alumno->numero_cedula . '@fake.edu.ec');

            $user = User::firstOrCreate(
                ['email' => $email],
                [
                    'name' => $alumno->nombres,
                    'apellidos' => $alumno->apellidos,
                    'password' => bcrypt('password'),
                    'rol' => 'ESTUDIANTE',
                    'institucion_id' => 1,
                ]
            );

            $alumno->update(['user_id' => $user->id]);
        });
    }
}