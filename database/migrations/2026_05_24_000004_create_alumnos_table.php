<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alumnos', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 20)->unique();
            $table->string('nombres');
            $table->string('apellidos');
            $table->enum('genero', ['masculino', 'femenino'])->nullable();
            $table->foreignId('representante_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('codigo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alumnos');
    }
};
