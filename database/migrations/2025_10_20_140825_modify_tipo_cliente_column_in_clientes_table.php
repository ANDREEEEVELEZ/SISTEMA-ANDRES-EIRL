<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Primero, migrar los datos existentes
        DB::statement("UPDATE clientes SET tipo_cliente = 'natural' WHERE tipo_doc = 'DNI'");
        DB::statement("UPDATE clientes SET tipo_cliente = 'natural_con_negocio' WHERE tipo_doc = 'RUC' AND LEFT(num_doc, 2) = '10'");
        DB::statement("UPDATE clientes SET tipo_cliente = 'juridica' WHERE tipo_doc = 'RUC' AND LEFT(num_doc, 2) = '20'");
        DB::statement("UPDATE clientes SET tipo_cliente = 'juridica' WHERE tipo_doc = 'RUC' AND tipo_cliente = 'juridica'");
        
        // Modificar la columna tipo_cliente con los nuevos valores enum
        DB::statement("ALTER TABLE clientes MODIFY COLUMN tipo_cliente ENUM('natural', 'natural_con_negocio', 'juridica') DEFAULT 'natural' NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revertir a los valores anteriores
        DB::statement("ALTER TABLE clientes MODIFY COLUMN tipo_cliente ENUM('natural', 'juridica', 'otro') DEFAULT 'natural' NOT NULL");
    }
};
