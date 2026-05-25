<?php

namespace Database\Seeders;

use App\Models\Paralelo;
use Illuminate\Database\Seeder;

class ParaleloSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['A', 'B', 'C', 'D'] as $letra) {
            Paralelo::create(['nombre' => $letra]);
        }
    }
}
