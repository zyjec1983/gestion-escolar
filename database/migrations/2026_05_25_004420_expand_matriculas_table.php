<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('matriculas', function (Blueprint $table) {
            $table->string('especializacion', 100)->nullable()->after('paralelo_id');
            $table->boolean('firma_rectora_completada')->default(false)->after('periodo_id');
            $table->boolean('firma_secretaria_completada')->default(false)->after('firma_rectora_completada');
            $table->boolean('firma_representante_completada')->default(false)->after('firma_secretaria_completada');
        });
    }

    public function down(): void
    {
        Schema::table('matriculas', function (Blueprint $table) {
            $table->dropColumn(['especializacion', 'firma_rectora_completada', 'firma_secretaria_completada', 'firma_representante_completada']);
        });
    }
};
