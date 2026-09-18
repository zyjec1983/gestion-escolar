<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('matricula_id')->constrained()->cascadeOnDelete();
            $table->foreignId('alumno_id')->constrained()->cascadeOnDelete();
            $table->foreignId('clase_docente_id')->constrained('clases_docentes')->cascadeOnDelete();
            $table->tinyInteger('parcial');
            $table->decimal('fii', 5, 2)->default(0);
            $table->decimal('ffi', 5, 2)->default(0);
            $table->decimal('fig', 5, 2)->default(0);
            $table->decimal('ffg', 5, 2)->default(0);
            $table->decimal('proyecto_integrador', 5, 2)->default(0);
            $table->decimal('evaluacion_parcial', 5, 2)->default(0);
            $table->timestamps();

            $table->unique(['matricula_id', 'clase_docente_id', 'parcial'], 'notas_unique');
            $table->index(['clase_docente_id', 'parcial']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notas');
    }
};
