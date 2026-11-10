<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('historial_externo_promociones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expediente_id')->constrained('expedientes_traslado')->cascadeOnDelete();
            $table->string('anio_lectivo', 20);
            $table->string('nivel', 100);
            $table->decimal('promedio', 7, 2);
            $table->string('escala', 50)->default('0-10');
            $table->text('observacion')->nullable();
            $table->timestamps();

            $table->index('expediente_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('historial_externo_promociones');
    }
};
