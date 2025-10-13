<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('serie_comprobantes', function (Blueprint $table) {
            $table->enum('aplica_a', ['factura', 'boleta', 'ninguno'])
                ->default('ninguno')
                ->after('codigo_tipo_comprobante');

        });
    }

    public function down(): void
    {
        Schema::table('serie_comprobantes', function (Blueprint $table) {
            $table->dropColumn('aplica_a');
        });
    }
};
