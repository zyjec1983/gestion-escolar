<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agenda_alumnos', function (Blueprint $table) {
            $table->dateTime('entregada_en')->nullable()->after('alumno_id');
        });
    }

    public function down(): void
    {
        Schema::table('agenda_alumnos', function (Blueprint $table) {
            $table->dropColumn('entregada_en');
        });
    }
};
