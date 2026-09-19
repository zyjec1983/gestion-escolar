<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asignaciones_grupos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clase_docente_id')->constrained('clases_docentes')->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('asignacion_grupo_alumnos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asignacion_id')->constrained('asignaciones_grupos')->cascadeOnDelete();
            $table->foreignId('alumno_id')->constrained('alumnos')->cascadeOnDelete();
            $table->unsignedSmallInteger('grupo');
            $table->unique(['asignacion_id', 'alumno_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asignacion_grupo_alumnos');
        Schema::dropIfExists('asignaciones_grupos');
    }
};