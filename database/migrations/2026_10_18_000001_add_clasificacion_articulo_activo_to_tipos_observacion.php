<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tipos_observacion', function (Blueprint $table) {
            // Clasificacion / gravedad del tipo de observacion.
            $table->string('clasificacion', 20)->default('leve');
            // Sustento legal o articulo de ley que respalda la observacion.
            $table->text('articulo_ley')->nullable();
            // Un tipo desactivado no aparece en los selects, pero se
            // conserva en el historial de observaciones ya registradas.
            $table->boolean('activo')->default(true);
        });
    }

    public function down(): void
    {
        Schema::table('tipos_observacion', function (Blueprint $table) {
            $table->dropColumn(['clasificacion', 'articulo_ley', 'activo']);
        });
    }
};
