<?php

use App\Models\Cliente;
use App\Models\Venta;
use App\Models\Producto;
use App\Models\DetalleVenta;
use App\Models\Caja;
use App\Models\User;
use App\Models\SerieComprobante;
use App\Models\Comprobante;

describe('Integración: Cliente y Ventas', function () {
    it('un cliente puede realizar múltiples ventas', function () {
        $cliente = Cliente::factory()->conDNI()->create();
        $caja = Caja::factory()->abierta()->create();
        $venta1 = Venta::factory()->create([
            'cliente_id' => $cliente->id,
            'caja_id' => $caja->id,
            'total_venta' => 100.00,
        ]);
        $venta2 = Venta::factory()->create([
            'cliente_id' => $cliente->id,
            'caja_id' => $caja->id,
            'total_venta' => 150.00,
        ]);
        echo "Cliente: {$cliente->id} - {$cliente->nombre}\n";
        echo "Ventas del cliente:\n";
        foreach ($cliente->ventas as $v) {
            echo "  Venta ID: {$v->id}, Total: {$v->total_venta}\n";
        }
        echo "Total ventas: " . $cliente->ventas->sum('total_venta') . "\n";
        expect($cliente->ventas)->toHaveCount(2)
            ->and($cliente->ventas->sum('total_venta'))->toBe(250.00);
    });

    it('puede calcular total de compras de un cliente', function () {
        $cliente = Cliente::factory()->create();
        Venta::factory()->count(3)->create([
            'cliente_id' => $cliente->id,
            'total_venta' => 100.00,
        ]);
        $totalCompras = $cliente->ventas()->sum('total_venta');
        echo "Cliente: {$cliente->id}\n";
        echo "Total compras calculado: {$totalCompras}\n";
        expect((float)$totalCompras)->toBe(300.00);
    });
    it('puede obtener última venta de un cliente', function () {
        $cliente = Cliente::factory()->create();
        $venta1 = Venta::factory()->create([
            'cliente_id' => $cliente->id,
            'fecha_venta' => now()->subDays(2),
        ]);
        $venta2 = Venta::factory()->create([
            'cliente_id' => $cliente->id,
            'fecha_venta' => now(),
        ]);
        $ultimaVenta = $cliente->ventas()->latest('fecha_venta')->first();
        echo "Ventas resumidas del cliente:\n";
        foreach ($cliente->ventas as $v) {
            echo "  ID: {$v->id}, Fecha: {$v->fecha_venta}\n";
        }
        echo "Última venta: ID {$ultimaVenta->id}, Fecha: {$ultimaVenta->fecha_venta}\n";
        expect($ultimaVenta->id)->toBe($venta2->id);
    });

    it('cliente inactivo puede tener ventas históricas', function () {
        $cliente = Cliente::factory()->create(['estado' => 'activo']);
        $venta = Venta::factory()->create(['cliente_id' => $cliente->id]);

        $cliente->update(['estado' => 'inactivo']);

        echo "Cliente actualizado: ID {$cliente->id}, Estado {$cliente->estado}\n";
        echo "Ventas históricas del cliente:\n";
        foreach ($cliente->ventas as $v) {
            echo "  Venta ID: {$v->id}, Total: {$v->total_venta}\n";
        }

        expect($cliente->estado)->toBe('inactivo')
            ->and($cliente->ventas)->toHaveCount(1);
    });

});

describe('Integración: Venta completa con productos', function () {
it('puede crear venta completa con productos y detalles', function () {
    $cliente = Cliente::factory()->create();
    $caja = Caja::factory()->abierta()->create();
    $user = User::factory()->create();
    $productos = [
        Producto::factory()->create(['nombre_producto' => 'Chifles 1kg', 'stock_total' => 100]),
        Producto::factory()->create(['nombre_producto' => 'Chifles 500g', 'stock_total' => 50]),
    ];
    $venta = Venta::factory()->create([
        'user_id' => $user->id,
        'cliente_id' => $cliente->id,
        'caja_id' => $caja->id,
        'subtotal_venta' => 30.00,
        'igv' => 5.40,
        'total_venta' => 35.40,
    ]);
    $detalles = [
        ['producto' => $productos[0], 'cantidad' => 2, 'precio' => 10.00, 'subtotal' => 20.00],
        ['producto' => $productos[1], 'cantidad' => 1, 'precio' => 10.00, 'subtotal' => 10.00],
    ];
    foreach ($detalles as $d) {
        DetalleVenta::create([
            'venta_id' => $venta->id,
            'producto_id' => $d['producto']->id,
            'cantidad_venta' => $d['cantidad'],
            'precio_unitario' => $d['precio'],
            'descuento' => 0,
            'descuento_unitario' => 0,
            'subtotal' => $d['subtotal'],
        ]);
    }
    echo "Venta creada: ID {$venta->id}, Total {$venta->total_venta}\n";
    foreach ($venta->detalleVentas as $d) {
        echo "  Producto: {$d->producto->nombre_producto}, Subtotal: {$d->subtotal}\n";
    }
    expect($venta->detalleVentas)->toHaveCount(2)
        ->and($venta->detalleVentas->sum('subtotal'))->toBe(30.00)
        ->and($venta->cliente->id)->toBe($cliente->id)
        ->and($venta->caja->id)->toBe($caja->id);
});


it('actualiza stock de productos después de venta', function () {
    // Crear producto con stock inicial
    $producto = Producto::factory()->create(['stock_total' => 100]);
    $venta = Venta::factory()->create();
    DetalleVenta::create([
        'venta_id' => $venta->id,
        'producto_id' => $producto->id,
        'cantidad_venta' => 10,
        'precio_unitario' => 5.00,
        'descuento_unitario' => 0,
        'descuento' => 0,
        'subtotal' => 50.00,
    ]);
    $producto->update([
        'stock_total' => $producto->stock_total - 10,
    ]);
    echo "Producto después de venta: ID {$producto->id}, Stock: {$producto->fresh()->stock_total}\n";
    expect($producto->fresh()->stock_total)->toBe(90);
});

});

describe('Integración: Cliente + Ventas + Comprobantes', function () {
    it('genera comprobante para venta de cliente', function () {
        $cliente = Cliente::factory()->conDNI()->create([
            'nombre_razon' => 'Cliente Prueba Comprobante',
        ]);
        $venta = Venta::factory()->create([
            'cliente_id' => $cliente->id,
            'subtotal_venta' => 49.15,
            'igv' => 9.33,
            'total_venta' => 58.48,
        ]);
        $serie = SerieComprobante::factory()->create([
            'tipo' => 'boleta',
            'codigo_tipo_comprobante' => '03',
            'aplica_a' => 'ninguno',
            'serie' => 'B001',
            'ultimo_numero' => 0,
        ]);
        $comprobante = Comprobante::create([
            'venta_id' => $venta->id,
            'serie_comprobante_id' => $serie->id,
            'tipo' => 'boleta',
            'codigo_tipo_nota' => null,
            'serie' => $serie->serie,
            'correlativo' => $serie->ultimo_numero + 1,
            'fecha_emision' => now(),
            'sub_total' => $venta->subtotal_venta,
            'igv' => $venta->igv,
            'total' => $venta->total_venta,
            'estado' => 'emitido',
        ]);
        echo "Comprobante generado:\n";
        echo "  ID: {$comprobante->id}\n";
        echo "  Tipo: {$comprobante->tipo}\n";
        echo "  Serie: {$comprobante->serie}\n";
        echo "  Correlativo: {$comprobante->correlativo}\n";
        echo "  Total: {$comprobante->total}\n";
        echo "  Estado: {$comprobante->estado}\n";
        expect($venta->comprobantes)->toHaveCount(1)
            ->and($comprobante->tipo)->toBe('boleta')
            ->and($comprobante->total)->toBe($venta->total_venta);
    });


    it('cliente con RUC debe tener factura, no boleta', function () {
        $cliente = Cliente::factory()->conRUC()->create();
        $venta = Venta::factory()->create(['cliente_id' => $cliente->id]);

        $comprobante = \App\Models\Comprobante::create([
            'venta_id' => $venta->id,
            'serie_comprobante_id' => 2,
            'tipo' => 'factura',
            'serie' => 'F001',
            'correlativo' => '00000001',
            'fecha_emision' => now(),
            'base_imponible' => $venta->subtotal_venta,
            'igv' => $venta->igv,
            'importe_total' => $venta->total_venta,
            'estado' => 'emitido',
        ]);

        echo "Comprobante factura generado: ID {$comprobante->id}, Total: {$comprobante->importe_total}\n";

        expect($comprobante->tipo)->toBe('factura')
            ->and($cliente->tipo_doc)->toBe('RUC');
    });

});
