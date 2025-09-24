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
        Schema::create('ventas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('cliente_id')->constrained('clientes')->onDelete('cascade');
            $table->foreignId('caja_id')->constrained('cajas')->onDelete('cascade');
            $table->decimal('subtotal_venta', 12, 2);
            $table->decimal('igv', 12, 2);
            $table->decimal('descuento_total', 12, 2);
            $table->decimal('total_venta', 12, 2);
            $table->date('fecha_venta');
            $table->time('hora_venta');
            $table->enum('estado_venta', ['emitida', 'anulada', 'rechazada']);
            $table->string('metodo_pago', 50);
            $table->string('cod_operacion', 30)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ventas');
    }
};
