<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Bitacora append-only del expediente de traslado. Registra cada accion
     * sensible (documentos, homologaciones, paz y salvo, cierre) con usuario,
     * IP y agente de usuario.
     */
    public function up(): void
    {
        Schema::create('expediente_auditorias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expediente_id')->nullable()->constrained('expedientes_traslado')->nullOnDelete();
            $table->string('alumno_nombre');
            $table->foreignId('usuario_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('usuario_nombre');
            $table->string('usuario_rol', 30);
            $table->string('accion', 60);
            $table->text('detalle')->nullable();
            $table->json('datos_anteriores')->nullable();
            $table->json('datos_nuevos')->nullable();
            $table->string('ip', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            $table->index(['expediente_id', 'created_at']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expediente_auditorias');
    }
};
