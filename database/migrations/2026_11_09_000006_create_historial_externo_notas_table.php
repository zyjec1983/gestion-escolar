<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('historial_externo_notas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expediente_id')->constrained('expedientes_traslado')->cascadeOnDelete();
            $table->string('asignatura', 150);
            $table->string('periodo', 80);
            $table->decimal('nota', 7, 2);
            $table->decimal('ponderacion', 7, 2)->nullable();
            $table->string('escala', 50)->default('0-10');
            $table->decimal('asistencia_porcentaje', 5, 2)->nullable();
            $table->timestamps();

            $table->index('expediente_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('historial_externo_notas');
    }
};
