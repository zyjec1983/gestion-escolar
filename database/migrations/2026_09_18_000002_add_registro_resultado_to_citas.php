<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('citas', function (Blueprint $table) {
            $table->string('resultado')->nullable()->after('motivo');
            $table->boolean('dudas_aclaradas')->nullable()->after('resultado');
            $table->text('evidencia')->nullable()->after('dudas_aclaradas');
            $table->unsignedBigInteger('registrada_por')->nullable()->after('evidencia');
            $table->timestamp('registrada_en')->nullable()->after('registrada_por');

            $table->index('resultado');
        });
    }

    public function down(): void
    {
        Schema::table('citas', function (Blueprint $table) {
            $table->dropColumn(['resultado', 'dudas_aclaradas', 'evidencia', 'registrada_por', 'registrada_en']);
        });
    }
};