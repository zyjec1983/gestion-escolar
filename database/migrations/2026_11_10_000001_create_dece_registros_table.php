<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tablas principales del modulo DECE. El orden respeta las dependencias:
 * primero el catalogo de diagnosticos, luego las citas y despues los registros
 * que las referencian.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dece_diagnosticos', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 30);
            $table->string('nombre', 255);
            $table->string('categoria', 120);
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->unique('codigo');
            $table->index(['activo', 'categoria']);
        });

        Schema::create('dece_citas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('alumno_id')->constrained('alumnos')->cascadeOnDelete();
            $table->foreignId('representante_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('profesional_id')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('fecha_hora');
            $table->unsignedSmallInteger('duracion_minutos')->default(30);
            $table->string('motivo', 150);
            $table->string('estado', 20)->default('programada');
            $table->text('observaciones')->nullable();
            $table->dateTime('notificado_en')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['profesional_id', 'fecha_hora']);
            $table->index(['alumno_id', 'fecha_hora']);
            $table->index('estado');
        });

        Schema::create('dece_atenciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('alumno_id')->constrained('alumnos')->cascadeOnDelete();
            $table->foreignId('profesional_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('cita_id')->nullable()->constrained('dece_citas')->nullOnDelete();
            $table->dateTime('fecha_atencion');
            $table->string('motivo', 120);
            $table->text('historia_narrada');
            $table->text('acuerdos')->nullable();
            $table->string('estado', 20)->default('abierta');
            $table->boolean('confidencial')->default(true);
            $table->timestamps();

            $table->index(['alumno_id', 'fecha_atencion']);
            $table->index(['motivo', 'fecha_atencion']);
            $table->index('estado');
        });

        Schema::create('dece_acuerdos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('alumno_id')->constrained('alumnos')->cascadeOnDelete();
            $table->foreignId('atencion_id')->nullable()->constrained('dece_atenciones')->nullOnDelete();
            $table->foreignId('profesional_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('tipo', 30);
            $table->text('descripcion');
            $table->text('compromiso_estudiante')->nullable();
            $table->text('compromiso_representante')->nullable();
            $table->date('fecha_inicio');
            $table->date('fecha_compromiso');
            $table->string('estado', 20)->default('activo');
            $table->dateTime('firmado_estudiante_en')->nullable();
            $table->dateTime('firmado_representante_en')->nullable();
            $table->dateTime('firmado_profesional_en')->nullable();
            $table->timestamps();

            $table->index(['alumno_id', 'estado']);
            $table->index(['fecha_compromiso', 'estado']);
        });

        Schema::create('dece_nee', function (Blueprint $table) {
            $table->id();
            $table->foreignId('alumno_id')->nullable()->constrained('alumnos')->nullOnDelete();
            $table->string('nombre_externo', 255)->nullable();
            $table->string('curso_externo', 120)->nullable();
            $table->string('procedencia', 20)->default('interno');
            $table->foreignId('diagnostico_id')->nullable()->constrained('dece_diagnosticos')->nullOnDelete();
            $table->string('diagnostico_detalle', 255)->nullable();
            $table->string('profesional_evaluador', 255)->nullable();
            $table->date('fecha_evaluacion')->nullable();
            $table->unsignedTinyInteger('grado_adaptacion')->nullable();
            $table->boolean('requiere_informe')->default(false);
            $table->text('observaciones')->nullable();
            $table->boolean('activo')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['alumno_id', 'activo']);
            $table->index(['procedencia', 'activo']);
            $table->index('diagnostico_id');
        });

        Schema::create('dece_derivaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('alumno_id')->constrained('alumnos')->cascadeOnDelete();
            $table->string('tipo', 20);
            $table->string('motivo', 150);
            $table->text('descripcion');
            $table->foreignId('derivado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->string('entidad_externa', 255)->nullable();
            $table->string('oficio', 100)->nullable();
            $table->date('fecha_derivacion');
            $table->date('fecha_limite')->nullable();
            $table->string('estado', 20)->default('pendiente');
            $table->text('respuesta')->nullable();
            $table->timestamps();

            $table->index(['alumno_id', 'estado']);
            $table->index(['tipo', 'estado']);
            $table->index('fecha_limite');
        });

        Schema::create('dece_talleres', function (Blueprint $table) {
            $table->id();
            $table->string('titulo', 255);
            $table->string('tipo', 60);
            $table->text('descripcion')->nullable();
            $table->date('fecha');
            $table->string('dirigido_a', 40);
            $table->foreignId('curso_id')->nullable()->constrained('cursos')->nullOnDelete();
            $table->string('facilitador', 255)->nullable();
            $table->string('lugar', 255)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['fecha', 'tipo']);
            $table->index('dirigido_a');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dece_talleres');
        Schema::dropIfExists('dece_derivaciones');
        Schema::dropIfExists('dece_nee');
        Schema::dropIfExists('dece_acuerdos');
        Schema::dropIfExists('dece_atenciones');
        Schema::dropIfExists('dece_citas');
        Schema::dropIfExists('dece_diagnosticos');
    }
};
