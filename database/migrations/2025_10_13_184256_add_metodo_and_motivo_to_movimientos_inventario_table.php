<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('movimientos_inventario', function (Blueprint $table) {
            // Método de ajuste: solo aplica cuando tipo = 'ajuste'
            $table->enum('metodo_ajuste', ['absoluto', 'relativo'])->nullable()->after('tipo');
            
            // Motivo específico del ajuste: solo aplica cuando tipo = 'ajuste'
            $table->enum('motivo_ajuste', ['conteo_fisico', 'vencido', 'danado', 'robo', 'otro'])->nullable()->after('metodo_ajuste');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('movimientos_inventario', function (Blueprint $table) {
            $table->dropColumn(['metodo_ajuste', 'motivo_ajuste']);
        });
    }
};
