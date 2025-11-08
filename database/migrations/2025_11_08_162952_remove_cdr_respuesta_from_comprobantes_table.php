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
        Schema::table('comprobantes', function (Blueprint $table) {
            // Eliminar columna cdr_respuesta (ya no se usa, solo ruta_cdr)
            $table->dropColumn('cdr_respuesta');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('comprobantes', function (Blueprint $table) {
            // Restaurar columna por si se necesita hacer rollback
            $table->text('cdr_respuesta')->nullable()->after('ruta_xml');
        });
    }
};
