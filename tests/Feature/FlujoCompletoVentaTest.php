<?php

use App\Models\Cliente;
use App\Models\Venta;
use App\Models\Caja;
use App\Models\User;
use App\Models\Producto;
use App\Models\DetalleVenta;
use App\Models\Comprobante;
use App\Models\SerieComprobante;

describe('Integración Completa: Flujo de Venta', function () {

    it('puede realizar flujo completo de venta: apertura caja-venta-comprobante-cierre', function () {
        $user = User::factory()->create();
        $cliente = Cliente::factory()->conDNI()->create();
        $producto = Producto::factory()->create(['stock_total' => 100]);
        $caja = Caja::factory()->create([
            'user_id' => $user->id,
            'saldo_inicial' => 500.00,
            'estado' => 'abierta',
        ]);
        echo "1. Caja abierta - Saldo inicial: {$caja->saldo_inicial}\n";
        $venta = Venta::create([
            'user_id' => $user->id,
            'cliente_id' => $cliente->id,
            'caja_id' => $caja->id,
            'subtotal_venta' => 30.00,
            'igv' => 5.40,
            'descuento_total' => 0.00,
            'total_venta' => 35.40,
            'fecha_venta' => now(),
            'hora_venta' => now()->format('H:i:s'),
            'estado_venta' => 'emitida',
            'metodo_pago' => 'efectivo',
        ]);
        DetalleVenta::create([
            'venta_id' => $venta->id,
            'producto_id' => $producto->id,
            'cantidad_venta' => 3,
            'precio_unitario' => 10.00,
            'descuento' => 0,
            'descuento_unitario' => 0,
            'subtotal' => 30.00,
        ]);
        echo "2. Venta creada - Total: {$venta->total_venta}, Cliente: {$cliente->nombre_razon}\n";
        $serie = SerieComprobante::firstOrCreate([
            'codigo_tipo_comprobante' => '03',
            'tipo' => 'boleta',
            'serie' => 'B001',
        ], [
            'correlativo_actual' => 0,
            'aplica_a' => 'ninguno',
        ]);

        $comprobante = Comprobante::create([
            'venta_id' => $venta->id,
            'serie_comprobante_id' => $serie->id,
            'tipo' => 'boleta',
            'serie' => 'B001',
            'correlativo' => '00000001',
            'fecha_emision' => now(),
            'sub_total' => $venta->subtotal_venta,
            'igv' => $venta->igv,
            'total' => $venta->total_venta,
            'estado' => 'emitido',
        ]);

        echo "3. Comprobante generado - Tipo: {$comprobante->tipo}, Serie-Correlativo: {$comprobante->serie}-{$comprobante->correlativo}\n";
        $totalVentas = $caja->ventas()->where('metodo_pago', 'efectivo')->sum('total_venta');
        $caja->update([
            'estado' => 'cerrada',
            'fecha_cierre' => now(),
            'saldo_final' => $caja->saldo_inicial + $totalVentas,
        ]);
        echo "4. Caja cerrada - Saldo final: {$caja->saldo_final}\n";
        expect($caja->estado)->toBe('cerrada')
            ->and($venta->comprobantes)->toHaveCount(1)
            ->and($comprobante->tipo)->toBe('boleta')
            ->and((float)$comprobante->total)->toBe(35.40)
            ->and($caja->saldo_final)->toBeGreaterThan($caja->saldo_inicial);
    });

});

describe('Integración: Múltiples ventas en una caja', function () {

    it('puede procesar múltiples ventas de diferentes clientes en la misma caja', function () {
        $user = User::factory()->create();
        $caja = Caja::factory()->abierta()->create([
            'user_id' => $user->id,
            'saldo_inicial' => 500.00,
        ]);
        $cliente1 = Cliente::factory()->conDNI()->create();
        $venta1 = Venta::factory()->create([
            'user_id' => $user->id,
            'cliente_id' => $cliente1->id,
            'caja_id' => $caja->id,
            'total_venta' => 100.00,
            'metodo_pago' => 'efectivo',
            'estado_venta' => 'emitida',
        ]);
        $cliente2 = Cliente::factory()->conRUC()->create();
        $venta2 = Venta::factory()->create([
            'user_id' => $user->id,
            'cliente_id' => $cliente2->id,
            'caja_id' => $caja->id,
            'total_venta' => 200.00,
            'metodo_pago' => 'yape',
            'estado_venta' => 'emitida',
        ]);
        $cliente3 = Cliente::factory()->conDNI()->create();
        $venta3 = Venta::factory()->create([
            'user_id' => $user->id,
            'cliente_id' => $cliente3->id,
            'caja_id' => $caja->id,
            'total_venta' => 150.00,
            'metodo_pago' => 'efectivo',
            'estado_venta' => 'emitida',
        ]);
        $totalVentas = $caja->ventas()->count();
        $totalEfectivo = $caja->ventas()->where('metodo_pago', 'efectivo')->sum('total_venta');
        $totalYape = $caja->ventas()->where('metodo_pago', 'yape')->sum('total_venta');
        echo "=== Resumen de Caja ===\n";
        echo "Total ventas: {$totalVentas}\n";
        echo "Total efectivo: {$totalEfectivo}\n";
        echo "Total Yape: {$totalYape}\n";
        expect($totalVentas)->toBe(3)
            ->and((float)$totalEfectivo)->toBe(250.00)
            ->and((float)$totalYape)->toBe(200.00);
    });

});

describe('Integración: Anulación de ventas', function () {

    it('puede anular venta y revertir stock', function () {
        $producto = Producto::factory()->create(['stock_total' => 100]);
        $venta = Venta::factory()->create(['estado_venta' => 'emitida']);
        DetalleVenta::create([
            'venta_id' => $venta->id,
            'producto_id' => $producto->id,
            'cantidad_venta' => 10,
            'precio_unitario' => 5.00,
            'descuento' => 0,
            'descuento_unitario' => 0,
            'subtotal' => 50.00,
        ]);
        $producto->update(['stock_total' => $producto->stock_total - 10]);
        echo "Stock después de venta: {$producto->fresh()->stock_total}\n";
        expect($producto->fresh()->stock_total)->toBe(90);
        $venta->update(['estado_venta' => 'anulada']);
        $producto->update(['stock_total' => $producto->stock_total + 10]);
        echo "Venta anulada. Stock revertido: {$producto->fresh()->stock_total}\n";
        expect($venta->estado_venta)->toBe('anulada')
            ->and($producto->fresh()->stock_total)->toBe(100);
    });

});

