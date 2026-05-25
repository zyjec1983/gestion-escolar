<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pre_registros', function (Blueprint $table) {
            $table->id();
            $table->string('rep_tipo_documento');
            $table->string('rep_numero_documento');
            $table->string('rep_nombres');
            $table->string('rep_apellidos');
            $table->string('rep_email');
            $table->string('rep_telefono')->nullable();
            $table->string('alumno_tipo_documento');
            $table->string('alumno_numero_documento');
            $table->string('alumno_nombres');
            $table->string('alumno_apellidos');
            $table->enum('alumno_genero', ['masculino', 'femenino'])->nullable();
            $table->date('alumno_fecha_nacimiento')->nullable();
            $table->foreignId('curso_id')->constrained();
            $table->foreignId('paralelo_id')->constrained();
            $table->foreignId('periodo_id')->constrained('periodos_lectivos');
            $table->enum('estado', ['pendiente', 'aprobado', 'rechazado'])->default('pendiente');
            $table->text('comentario_rechazo')->nullable();
            $table->string('pdf_path')->nullable();
            $table->string('codigo_solicitud', 20)->nullable()->unique();
            $table->timestamp('fecha_aprobacion')->nullable();
            $table->foreignId('aprobado_por')->nullable()->constrained('users');
            $table->timestamps();

            $table->index('estado');
            $table->index('rep_email');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pre_registros');
    }
};
