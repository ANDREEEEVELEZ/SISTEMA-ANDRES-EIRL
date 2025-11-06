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
        // Eliminar campo codigo_tipo_comprobante de comprobantes
        // Este campo debe estar SOLO en serie_comprobantes
        // Se accede vía relación: comprobante->serieComprobante->codigo_tipo_comprobante
        Schema::table('comprobantes', function (Blueprint $table) {
            $table->dropColumn('codigo_tipo_comprobante');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Restaurar el campo si se hace rollback
        Schema::table('comprobantes', function (Blueprint $table) {
            $table->char('codigo_tipo_comprobante', 2)
                ->nullable()
                ->after('tipo')
                ->comment('01: Factura, 03: Boleta, 07: Nota Crédito, 08: Nota Débito');
        });
    }
};
