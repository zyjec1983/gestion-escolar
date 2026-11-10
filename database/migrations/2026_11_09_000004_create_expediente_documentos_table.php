<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expediente_documentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expediente_id')->constrained('expedientes_traslado')->cascadeOnDelete();
            $table->string('tipo_documento', 60);
            $table->string('nombre_original');
            $table->string('ruta');
            $table->string('mime', 100);
            $table->unsignedBigInteger('tamano');
            $table->foreignId('subido_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['expediente_id', 'tipo_documento']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expediente_documentos');
    }
};
