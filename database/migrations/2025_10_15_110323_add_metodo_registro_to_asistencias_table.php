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
        Schema::table('asistencias', function (Blueprint $table) {
            // Verificar y agregar solo las columnas que no existen
            if (!Schema::hasColumn('asistencias', 'metodo_registro')) {
                $table->enum('metodo_registro', ['facial', 'manual_dni'])
                    ->default('facial')
                    ->after('estado')
                    ->comment('Método utilizado para registrar la asistencia');
            }
            
            if (!Schema::hasColumn('asistencias', 'razon_manual')) {
                $table->text('razon_manual')
                    ->nullable()
                    ->after('metodo_registro')
                    ->comment('Motivo del registro manual cuando falla el reconocimiento facial');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('asistencias', function (Blueprint $table) {
            if (Schema::hasColumn('asistencias', 'metodo_registro')) {
                $table->dropColumn('metodo_registro');
            }
            if (Schema::hasColumn('asistencias', 'razon_manual')) {
                $table->dropColumn('razon_manual');
            }
        });
    }
};
