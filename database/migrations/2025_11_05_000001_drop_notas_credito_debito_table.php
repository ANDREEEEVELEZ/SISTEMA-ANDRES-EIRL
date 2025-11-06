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
        // Eliminar tabla notas_credito_debito ya que es completamente redundante
        // Toda la funcionalidad está cubierta por:
        // - comprobantes (contiene las notas de crédito/débito)
        // - comprobante_relacion (relaciona comprobante origen con la nota)
        Schema::dropIfExists('notas_credito_debito');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Recrear la tabla si se hace rollback
        Schema::create('notas_credito_debito', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venta_id')->constrained('ventas')->onDelete('cascade');
            $table->foreignId('comprobante_origen_id')->constrained('comprobantes')->onDelete('cascade');
            $table->foreignId('comprobante_nota_id')->nullable()->constrained('comprobantes')->onDelete('set null');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->enum('tipo_nota', ['credito', 'debito']);
            $table->string('serie_nota', 10);
            $table->integer('numero_nota');
            $table->decimal('subtotal', 12, 2);
            $table->decimal('igv', 12, 2);
            $table->decimal('total', 12, 2);
            $table->text('motivo');
            $table->enum('estado', ['emitida', 'anulada'])->default('emitida');
            $table->date('fecha_emision');
            $table->time('hora_emision');
            $table->string('hash_sunat', 100)->nullable();
            $table->string('codigo_sunat', 20)->nullable();
            $table->text('xml_firmado')->nullable();
            $table->text('cdr_respuesta')->nullable();
            $table->timestamps();
        });
    }
};
