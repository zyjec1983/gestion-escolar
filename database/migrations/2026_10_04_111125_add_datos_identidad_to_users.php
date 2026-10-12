<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Los representantes son personas identificables: sin cedula no se puede
     * buscar ni confirmar quien es, y sin fecha de nacimiento no se puede
     * comprobar que sea mayor de edad.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('numero_cedula', 20)->nullable()->after('telefono')->index();
            $table->date('fecha_nacimiento')->nullable()->after('numero_cedula');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['numero_cedula']);
            $table->dropColumn(['numero_cedula', 'fecha_nacimiento']);
        });
    }
};