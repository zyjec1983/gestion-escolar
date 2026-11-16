<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Malla curricular: materias que se dictan en cada año de educación.
 *
 * `periodos_semanales` son períodos pedagógicos de 40 minutos. El unique evita
 * repetir la misma materia en el mismo año.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('curso_materias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('curso_id')->constrained()->cascadeOnDelete();
            $table->foreignId('materia_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('periodos_semanales')->default(1);
            $table->unsignedSmallInteger('orden')->default(0);
            $table->timestamps();

            $table->unique(['curso_id', 'materia_id'], 'curso_materia_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('curso_materias');
    }
};
