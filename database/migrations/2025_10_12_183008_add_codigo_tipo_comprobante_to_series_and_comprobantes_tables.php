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
        // Agregar el campo a la tabla serie_comprobantes
        Schema::table('serie_comprobantes', function (Blueprint $table) {
            if (!Schema::hasColumn('serie_comprobantes', 'codigo_tipo_comprobante')) {
                $table->char('codigo_tipo_comprobante', 2)
                    ->nullable()
                    ->after('tipo')
                    ->comment('01: Factura, 03: Boleta, 07: Nota Crédito, 08: Nota Débito');
            }
        });

        // Agregar el campo a la tabla comprobantes
        Schema::table('comprobantes', function (Blueprint $table) {
            if (!Schema::hasColumn('comprobantes', 'codigo_tipo_comprobante')) {
                $table->char('codigo_tipo_comprobante', 2)
                    ->nullable()
                    ->after('tipo')
                    ->comment('01: Factura, 03: Boleta, 07: Nota Crédito, 08: Nota Débito');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('serie_comprobantes', function (Blueprint $table) {
            if (Schema::hasColumn('serie_comprobantes', 'codigo_tipo_comprobante')) {
                $table->dropColumn('codigo_tipo_comprobante');
            }
        });

        Schema::table('comprobantes', function (Blueprint $table) {
            if (Schema::hasColumn('comprobantes', 'codigo_tipo_comprobante')) {
                $table->dropColumn('codigo_tipo_comprobante');
            }
        });
    }
};
