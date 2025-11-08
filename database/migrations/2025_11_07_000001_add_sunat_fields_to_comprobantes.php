<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::table('comprobantes', function (Blueprint $table) {

            $table->string('ruta_xml', 255)->nullable()->after('xml_firmado');

            $table->string('ruta_cdr', 255)->nullable()->after('cdr_respuesta');
            $table->datetime('fecha_envio_sunat')->nullable()->after('ruta_cdr');
            $table->integer('intentos_envio')->default(0)->after('fecha_envio_sunat');
            $table->text('error_envio')->nullable()->after('intentos_envio');
            // ticket_sunat: NO necesario para facturas/boletas individuales (solo Resúmenes/Bajas)
            $table->string('ticket_sunat', 50)->nullable()->after('error_envio')->comment('Solo para Resúmenes Diarios y Comunicaciones de Baja');
        });
    }

    public function down(): void
    {
        Schema::table('comprobantes', function (Blueprint $table) {
            $table->dropColumn([
                'ruta_xml',
                'ruta_cdr',
                'fecha_envio_sunat',
                'intentos_envio',
                'error_envio',
                'ticket_sunat'
            ]);
        });
    }
};
