<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Estado de la matricula. Las matricula existentes ya estaban completas,
     * asi que el valor por defecto es "definitiva" y no se rompe ningun dato.
     * Una matricula de traslado con documentacion pendiente pasa a
     * "provisional" hasta que Secretaria valide el expediente completo.
     */
    public function up(): void
    {
        Schema::table('matriculas', function (Blueprint $table) {
            $table->string('estado', 20)->default('definitiva')->after('especializacion');
            $table->index('estado');
        });
    }

    public function down(): void
    {
        Schema::table('matriculas', function (Blueprint $table) {
            $table->dropIndex(['estado']);
            $table->dropColumn('estado');
        });
    }
};
