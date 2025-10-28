<?php

use App\Filament\Resources\Ventas\Widgets\EstadisticasVentasWidget;
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
        Schema::create('arqueos', function (Blueprint $table) {
             $table->id();
            $table->unsignedBigInteger('caja_id');
            $table->unsignedBigInteger('user_id');
            $table->dateTime('fecha_inicio');
            $table->dateTime('fecha_fin');
            $table->decimal('saldo_inicial', 15, 2)->default(0);
            $table->decimal('total_ventas', 15, 2)->default(0);
            $table->decimal('total_ingresos', 15, 2)->default(0);
            $table->decimal('total_egresos', 15, 2)->default(0);
            $table->decimal('saldo_teorico', 15, 2)->default(0);
            $table->decimal('efectivo_contado', 15, 2)->nullable();
            $table->decimal('diferencia', 15, 2)->nullable();
            $table->text('observacion')->nullable();
            $table->enum('estado', ['confirmado', 'pendiente'])->default('pendiente');
            $table->timestamps();
            $table->foreign('caja_id')->references('id')->on('cajas')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('arqueos');
    }
};
