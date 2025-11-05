<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * This migration makes ventas.cliente_id nullable and adjusts the foreign key
     * to use ON DELETE SET NULL so ticket ventas can be created without a cliente record.
     */
    public function up(): void
    {
        // Try to drop existing foreign key if it exists
        try {
            // Name convention: ventas_cliente_id_foreign
            Schema::table('ventas', function (Blueprint $table) {
                $table->dropForeign(['cliente_id']);
            });
        } catch (\Exception $e) {
            // ignore if FK does not exist
        }

        // Alter the column to be nullable using raw SQL to avoid requiring doctrine/dbal
        $driver = DB::getDriverName();
        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE `ventas` MODIFY `cliente_id` BIGINT UNSIGNED NULL');
            // Recreate the foreign key to allow SET NULL on delete
            DB::statement('ALTER TABLE `ventas` ADD CONSTRAINT `ventas_cliente_id_foreign` FOREIGN KEY (`cliente_id`) REFERENCES `clientes`(`id`) ON DELETE SET NULL');
        } else {
            // Fallback using schema builder (may require doctrine/dbal)
            Schema::table('ventas', function (Blueprint $table) {
                $table->unsignedBigInteger('cliente_id')->nullable()->change();
                $table->foreign('cliente_id')->references('id')->on('clientes')->onDelete('set null');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop the FK we created
        try {
            Schema::table('ventas', function (Blueprint $table) {
                $table->dropForeign(['cliente_id']);
            });
        } catch (\Exception $e) {
            // ignore
        }

        $driver = DB::getDriverName();
        if ($driver === 'mysql') {
            // Make column NOT NULL again (this will fail if there are NULLs present)
            DB::statement('ALTER TABLE `ventas` MODIFY `cliente_id` BIGINT UNSIGNED NOT NULL');
            DB::statement('ALTER TABLE `ventas` ADD CONSTRAINT `ventas_cliente_id_foreign` FOREIGN KEY (`cliente_id`) REFERENCES `clientes`(`id`) ON DELETE CASCADE');
        } else {
            Schema::table('ventas', function (Blueprint $table) {
                $table->unsignedBigInteger('cliente_id')->nullable(false)->change();
                $table->foreign('cliente_id')->references('id')->on('clientes')->onDelete('cascade');
            });
        }
    }
};
