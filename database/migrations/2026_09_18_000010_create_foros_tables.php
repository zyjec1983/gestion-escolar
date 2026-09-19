<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('foros', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clase_docente_id')->constrained('clases_docentes')->cascadeOnDelete();
            $table->string('titulo');
            $table->text('pregunta');
            $table->json('links')->nullable();
            $table->dateTime('fecha_inicio');
            $table->dateTime('fecha_cierre');
            $table->timestamps();
        });

        Schema::create('foro_intervenciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('foro_id')->constrained('foros')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('tipo', 20)->default('respuesta');
            $table->foreignId('padre_id')->nullable()->constrained('foro_intervenciones')->cascadeOnDelete();
            $table->text('cuerpo');
            $table->timestamps();

            $table->index(['foro_id', 'user_id', 'tipo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('foro_intervenciones');
        Schema::dropIfExists('foros');
    }
};