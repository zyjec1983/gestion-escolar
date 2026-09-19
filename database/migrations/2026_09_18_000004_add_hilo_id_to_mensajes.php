<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mensajes', function (Blueprint $table) {
            $table->foreignId('hilo_id')->nullable()->after('destinatario_id')
                ->references('id')->on('mensajes')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('mensajes', function (Blueprint $table) {
            $table->dropForeign(['hilo_id']);
            $table->dropColumn('hilo_id');
        });
    }
};