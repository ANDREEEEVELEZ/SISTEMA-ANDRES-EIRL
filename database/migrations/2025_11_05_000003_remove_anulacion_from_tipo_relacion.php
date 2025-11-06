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
        // Eliminar registros con tipo_relacion = 'anulacion' si existen
        // (los tickets no necesitan registros en comprobante_relacion)
        DB::table('comprobante_relacion')
            ->where('tipo_relacion', 'anulacion')
            ->delete();

        // Modificar el enum para quitar 'anulacion'
        // Solo mantener: 'nota de credito' y 'nota de debito'
        DB::statement("ALTER TABLE comprobante_relacion
            MODIFY COLUMN tipo_relacion ENUM('nota de credito', 'nota de debito') NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Restaurar el enum con 'anulacion'
        DB::statement("ALTER TABLE comprobante_relacion
            MODIFY COLUMN tipo_relacion ENUM('anulacion', 'nota de credito', 'nota de debito') NOT NULL");
    }
};
