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
        Schema::create('comprobantes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venta_id')->constrained('ventas')->onDelete('cascade');
            $table->foreignId('serie_comprobante_id')->constrained('serie_comprobantes')->onDelete('cascade');
            $table->enum('tipo', [
                'boleta',
                'factura',
                'ticket',
                'nota de credito',
                'nota de debito',
            ]);
            $table->string('serie', 10);
            $table->integer('correlativo');
            $table->dateTime('fecha_emision');
            $table->decimal('sub_total', 12, 2);
            $table->decimal('igv', 12, 2);
            $table->decimal('total', 12, 2);
            $table->enum('estado', ['emitido','anulado','rechazado']);
            $table->string('motivo_anulacion', 100)->nullable();
            $table->string('hash_sunat', 100)->nullable();
            $table->string('codigo_sunat', 20)->nullable();
            $table->text('xml_firmado')->nullable();
            $table->text('cdr_respuesta')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comprobantes');
    }
};
