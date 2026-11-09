<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Bitacora append-only de las intervenciones administrativas sobre
     * planificaciones. Guarda una copia del estado anterior y posterior para
     * que el registro siga siendo util aunque la unidad se elimine despues.
     */
    public function up(): void
    {
        Schema::create('planificacion_auditorias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unidad_id')->nullable()->constrained('unidades')->nullOnDelete();
            $table->string('grupo_id', 50)->nullable();
            $table->string('unidad_titulo');
            $table->foreignId('usuario_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('usuario_nombre');
            $table->string('usuario_rol', 30);
            $table->string('accion', 50);
            $table->text('motivo');
            $table->json('datos_anteriores')->nullable();
            $table->json('datos_nuevos')->nullable();
            $table->string('ip', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            $table->index(['unidad_id', 'created_at']);
            $table->index(['grupo_id', 'created_at']);
            $table->index(['usuario_id', 'created_at']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('planificacion_auditorias');
    }
};
