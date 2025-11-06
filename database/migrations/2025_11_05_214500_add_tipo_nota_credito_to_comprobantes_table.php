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
            $table->char('codigo_tipo_nota', 2)
                ->nullable()
                ->after('tipo')
                ->comment('Catálogo 09 SUNAT (si tipo=nota de credito) o Catálogo 10 (si tipo=nota de debito). Ej NC: 01=Anulación, 07=Devolución. NULL para boletas/facturas/tickets');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('comprobantes', function (Blueprint $table) {
            $table->dropColumn('codigo_tipo_nota');
        });
    }
};
