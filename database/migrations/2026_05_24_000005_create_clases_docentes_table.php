<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clases_docentes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('materia_id')->constrained()->cascadeOnDelete();
            $table->foreignId('curso_id')->constrained()->cascadeOnDelete();
            $table->foreignId('paralelo_id')->constrained()->cascadeOnDelete();
            $table->string('color_tarjeta')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'materia_id', 'curso_id', 'paralelo_id'], 'clase_docente_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clases_docentes');
    }
};
