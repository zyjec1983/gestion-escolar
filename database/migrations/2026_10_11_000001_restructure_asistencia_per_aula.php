<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // La asistencia se registra una vez al dia por aula (curso + paralelo),
        // no por materia. Se reestructura la tabla (vacia en este momento).
        Schema::dropIfExists('asistencia');

        Schema::create('asistencia', function (Blueprint $table) {
            $table->id();
            $table->foreignId('curso_id')->constrained('cursos')->cascadeOnDelete();
            $table->foreignId('paralelo_id')->constrained('paralelos')->cascadeOnDelete();
            $table->foreignId('alumno_id')->constrained('alumnos')->cascadeOnDelete();
            $table->date('fecha');
            $table->string('estado')->default('presente');
            $table->text('observacion')->nullable();
            $table->foreignId('clase_docente_id')->nullable()->constrained('clases_docentes')->nullOnDelete();
            $table->foreignId('registrado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('editado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('editado_en')->nullable();
            $table->timestamps();

            $table->unique(['curso_id', 'paralelo_id', 'alumno_id', 'fecha'], 'asistencia_unique');
            $table->index(['curso_id', 'paralelo_id', 'fecha']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asistencia');

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
};