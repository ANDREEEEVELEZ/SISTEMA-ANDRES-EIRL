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
        Schema::create('clientes', function (Blueprint $table) {
            $table->id();
           $table->enum('tipo_doc', ['DNI', 'RUC']);
            $table->enum('tipo_cliente', ['natural','juridica','otro']);
            $table->string('num_doc', 20);
            $table->string('nombre_razon', 150);
            $table->date('fecha_registro');
            $table->enum('estado', ['activo','inactivo']);
            $table->string('telefono', 20)->nullable();
            $table->string('direccion', 255)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clientes');
    }
};
