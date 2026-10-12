<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Queda constancia de cada cambio de representante legal. No se pide el
     * motivo en las fichas: las causas son multiples y las decide la
     * secretaria segun el caso. El registro sirve para saber quien cambio y
     * cuando, sin obligar a inventar un motivo.
     */
    public function up(): void
    {
        Schema::create('representante_cambios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('alumno_id')->constrained()->cascadeOnDelete();
            $table->foreignId('representante_anterior_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('representante_nuevo_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('registrado_por')->constrained('users');
            $table->timestamps();

            $table->index(['alumno_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('representante_cambios');
    }
};