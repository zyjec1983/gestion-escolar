<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ventanas de subida de notas.
 *
 * Qué hace: habilita o deshabilita la carga de notas por parcial. Si no hay
 * ninguna ventana registrada para un parcial, el sistema mantiene el
 * comportamiento anterior (carga permitida). Cuando existe una ventana, solo
 * se puede guardar dentro de sus fechas y mientras esté activa.
 * Conecta con: App\Models\VentanaNota, ConfiguracionNotas, Admin\VentanaNotaController
 * y Docente\NotaController.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ventanas_notas', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('parcial')->nullable();
            $table->dateTime('fecha_inicio');
            $table->dateTime('fecha_fin');
            $table->boolean('activo')->default(true);
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('descripcion')->nullable();
            $table->timestamps();

            $table->index('parcial');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ventanas_notas');
    }
};
