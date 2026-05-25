<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $docente = User::create([
            'name' => 'CHRISTIAN WILFRIDO RODRIGUEZ',
            'email' => 'crodrigue@fake.edu.ec',
            'password' => bcrypt('password'),
            'rol' => 'DOCENTE',
            'institucion_id' => 1,
        ]);

        $representante = User::create([
            'name' => 'MENDOZA ANDRADE CARLOS LUIS',
            'email' => 'cmendoza@email.com',
            'password' => bcrypt('password'),
            'rol' => 'REPRESENTANTE',
            'institucion_id' => 1,
        ]);

        $admin = User::create([
            'name' => 'EVA DEL ROCIO ALVAREZ ROBALINO',
            'email' => 'ealvarez@fake.edu.ec',
            'password' => bcrypt('password'),
            'rol' => 'DOCENTE',
            'institucion_id' => 1,
        ]);
    }
}
