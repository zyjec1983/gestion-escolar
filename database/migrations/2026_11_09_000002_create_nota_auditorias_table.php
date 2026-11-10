<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Bitacora append-only de las correcciones extraordinarias de notas.
     * Guarda el antes y el despues, el motivo y la trazabilidad de red para
     * que una correccion de Secretaria nunca pueda borrarse ni editarse.
     */
    public function up(): void
    {
        Schema::create('nota_auditorias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nota_id')->nullable()->constrained('notas')->nullOnDelete();
            $table->foreignId('matricula_id')->nullable()->constrained('matriculas')->nullOnDelete();
            $table->foreignId('alumno_id')->nullable()->constrained('alumnos')->nullOnDelete();
            $table->foreignId('clase_docente_id')->nullable()->constrained('clases_docentes')->nullOnDelete();

            $table->string('alumno_nombre');
            $table->string('materia', 150)->nullable();
            $table->string('curso', 150)->nullable();
            $table->string('paralelo', 50)->nullable();
            $table->unsignedTinyInteger('parcial')->nullable();
            $table->string('tipo_nota', 20)->nullable();

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

            $table->index(['nota_id', 'created_at']);
            $table->index(['alumno_id', 'created_at']);
            $table->index(['usuario_id', 'created_at']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nota_auditorias');
    }
};
