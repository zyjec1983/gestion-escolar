<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tablas de soporte del modulo DECE: archivos polimorficos, bitacora
 * inalterable, seguimientos de acuerdos y asistencia a talleres.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dece_archivos', function (Blueprint $table) {
            $table->id();
            $table->string('archivoable_type');
            $table->unsignedBigInteger('archivoable_id');
            $table->string('nombre_original', 255);
            $table->string('ruta', 255);
            $table->string('mime', 120);
            $table->unsignedBigInteger('tamano')->default(0);
            $table->foreignId('subido_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['archivoable_type', 'archivoable_id'], 'dece_archivos_morph_index');
        });

        Schema::create('dece_auditorias', function (Blueprint $table) {
            $table->id();
            $table->string('auditable_type');
            $table->unsignedBigInteger('auditable_id');
            $table->foreignId('alumno_id')->nullable()->constrained('alumnos')->nullOnDelete();
            $table->foreignId('usuario_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('usuario_nombre', 255);
            $table->string('usuario_rol', 30);
            $table->string('accion', 60);
            $table->text('detalle')->nullable();
            $table->json('datos_anteriores')->nullable();
            $table->json('datos_nuevos')->nullable();
            $table->string('ip', 45)->nullable();
            $table->string('user_agent', 1000)->nullable();
            $table->timestamps();

            $table->index(['auditable_type', 'auditable_id'], 'dece_auditorias_morph_index');
            $table->index(['alumno_id', 'created_at']);
            $table->index('accion');
        });

        Schema::create('dece_acuerdo_seguimientos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('acuerdo_id')->constrained('dece_acuerdos')->cascadeOnDelete();
            $table->date('fecha');
            $table->string('estado', 20);
            $table->text('observacion')->nullable();
            $table->foreignId('registrado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['acuerdo_id', 'fecha']);
        });

        Schema::create('dece_taller_asistentes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('taller_id')->constrained('dece_talleres')->cascadeOnDelete();
            $table->foreignId('alumno_id')->nullable()->constrained('alumnos')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('nombre', 255)->nullable();
            $table->boolean('asistio')->default(true);
            $table->timestamps();

            $table->index(['taller_id', 'asistio']);
            $table->index('alumno_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dece_taller_asistentes');
        Schema::dropIfExists('dece_acuerdo_seguimientos');
        Schema::dropIfExists('dece_auditorias');
        Schema::dropIfExists('dece_archivos');
    }
};
