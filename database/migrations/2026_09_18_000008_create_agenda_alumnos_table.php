<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agenda_alumnos', function (Blueprint $table) {
            $table->foreignId('agenda_id')->constrained('agendas')->cascadeOnDelete();
            $table->foreignId('alumno_id')->constrained('alumnos')->cascadeOnDelete();
            $table->primary(['agenda_id', 'alumno_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agenda_alumnos');
    }
};