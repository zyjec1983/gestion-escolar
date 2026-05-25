<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('matriculas', function (Blueprint $table) {
            $table->id();
            $table->string('codigo_matricula', 20)->nullable();
            $table->foreignId('alumno_id')->constrained()->cascadeOnDelete();
            $table->foreignId('curso_id')->constrained()->cascadeOnDelete();
            $table->foreignId('paralelo_id')->constrained()->cascadeOnDelete();
            $table->foreignId('periodo_id')->constrained('periodos_lectivos')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['alumno_id', 'periodo_id'], 'matricula_periodo_unique');
            $table->index('codigo_matricula');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('matriculas');
    }
};
