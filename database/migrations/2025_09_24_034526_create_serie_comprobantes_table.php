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
        Schema::create('serie_comprobantes', function (Blueprint $table) {
            $table->id();
           $table->enum('tipo', [
                'boleta',
                'factura',
                'ticket',
                'nota de credito',
                'nota de debito',
            ]);
            $table->string('serie', 10);
            $table->integer('ultimo_numero');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('serie_comprobantes');
    }
};
