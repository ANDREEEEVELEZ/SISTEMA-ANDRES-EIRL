<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Primero, eliminar duplicados manteniendo el más reciente de cada documento
        DB::statement('
            DELETE c1 FROM clientes c1
            INNER JOIN clientes c2
            WHERE c1.id < c2.id
            AND c1.num_doc = c2.num_doc
            AND c1.num_doc != ""
        ');

        // Agregar índice único
        Schema::table('clientes', function (Blueprint $table) {
            $table->unique('num_doc');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->dropUnique(['num_doc']);
        });
    }
};
