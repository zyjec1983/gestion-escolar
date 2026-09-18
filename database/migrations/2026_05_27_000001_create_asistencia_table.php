<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asistencia', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clase_docente_id')->constrained('clases_docentes')->cascadeOnDelete();
            $table->foreignId('alumno_id')->constrained()->cascadeOnDelete();
            $table->date('fecha');
            $table->string('estado')->default('presente');
            $table->text('observacion')->nullable();
            $table->timestamps();

            $table->unique(['clase_docente_id', 'alumno_id', 'fecha'], 'asistencia_unique');
            $table->index(['clase_docente_id', 'fecha']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asistencia');
    }
};
