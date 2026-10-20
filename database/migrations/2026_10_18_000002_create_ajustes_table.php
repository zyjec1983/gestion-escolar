<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ajustes', function (Blueprint $table) {
            $table->id();
            // Clave unica del ajuste, por ejemplo 'tipos_obs_edicion'.
            $table->string('clave', 60)->unique();
            // Valor del ajuste, almacenado como texto para ser generico.
            $table->string('valor')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ajustes');
    }
};
