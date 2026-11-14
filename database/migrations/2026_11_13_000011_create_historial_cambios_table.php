<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Historial de cambios del sistema.
 *
 * Qué hace: registra quién hizo qué y cuándo en los módulos administrables.
 * Se consulta en Administración → Historial de cambios.
 * Conecta con: App\Models\HistorialCambio y Admin\HistorialController.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('historial_cambios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('modulo', 60);
            $table->string('accion', 30);
            $table->text('descripcion');
            $table->json('datos')->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamps();

            $table->index(['modulo', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('historial_cambios');
    }
};
