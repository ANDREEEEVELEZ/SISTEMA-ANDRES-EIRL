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
        Schema::create('empleados', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('nombres', 100);
            $table->string('apellidos', 100);
            $table->string('dni', 15);
            $table->string('telefono', 20)->nullable();
            $table->string('direccion', 255)->nullable();
            $table->date('fecha_nacimiento');
            $table->string('correo_empleado', 100)->nullable();
            $table->string('distrito', 50)->nullable();
            $table->date('fecha_incorporacion');
            $table->string('estado_empleado', 20);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('empleados');
    }
};
