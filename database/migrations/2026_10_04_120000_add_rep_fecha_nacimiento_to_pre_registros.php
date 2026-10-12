<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * El representante legal tiene que ser mayor de edad, asi que el formulario
     * de inscripcion tambien pide su fecha de nacimiento. Queda nullable para
     * que las solicitudes pendientes de antes no se rompan.
     */
    public function up(): void
    {
        Schema::table('pre_registros', function (Blueprint $table) {
            $table->date('rep_fecha_nacimiento')->nullable()->after('rep_apellidos');
        });
    }

    public function down(): void
    {
        Schema::table('pre_registros', function (Blueprint $table) {
            $table->dropColumn('rep_fecha_nacimiento');
        });
    }
};
