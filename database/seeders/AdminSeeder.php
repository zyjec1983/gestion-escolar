<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => 'ADMIN SECRETARIA',
            'email' => 'secretaria@fake.edu.ec',
            'password' => bcrypt('admin123'),
            'rol' => 'SECRETARIA',
            'institucion_id' => 1,
        ]);

        User::create([
            'name' => 'RECTOR UNIDAD EDUCATIVA',
            'email' => 'rector@fake.edu.ec',
            'password' => bcrypt('admin123'),
            'rol' => 'ADMIN',
            'institucion_id' => 1,
        ]);
    }
}
