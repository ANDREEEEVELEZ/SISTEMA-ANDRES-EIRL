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
        Schema::create('comprobante_relacion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('comprobante_origen_id')->constrained('comprobantes')->onDelete('cascade');
            $table->foreignId('comprobante_relacionado_id')->constrained('comprobantes')->onDelete('cascade');
            $table->enum('tipo_relacion', ['anulacion','nota de credito','nota de debito']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comprobante_relacion');
    }
};
