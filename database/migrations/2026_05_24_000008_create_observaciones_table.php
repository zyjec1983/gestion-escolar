<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('observaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('alumno_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tipo_observacion_id')->constrained('tipos_observacion')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('descripcion_texto');
            $table->boolean('notificar_representante')->default(false);
            $table->timestamps();

            $table->index(['alumno_id', 'created_at']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('observaciones');
    }
};
