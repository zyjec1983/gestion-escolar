<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('insumos_evaluacion', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('codigo', 30)->nullable()->unique();
            $table->string('tipo')->nullable();
            $table->foreignId('clase_docente_id')->constrained('clases_docentes')->cascadeOnDelete();
            $table->decimal('peso_porcentual', 5, 2)->nullable();
            $table->timestamps();

            $table->index('clase_docente_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('insumos_evaluacion');
    }
};
