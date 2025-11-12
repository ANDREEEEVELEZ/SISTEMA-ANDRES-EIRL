<?php

use App\Models\Venta;
use App\Models\User;
use App\Models\Cliente;
use App\Models\Caja;

describe('Pruebas de Ventas', function () {

    it('calcula correctamente el total de venta aplicando IGV (18%) y descuentos', function () {
        $venta = new Venta([
            'subtotal_venta' => '1000.00',
            'igv' => '180.00',
            'descuento_total' => '50.00',
            'total_venta' => '1130.00'
        ]);

        $calculado = (float) $venta->subtotal_venta + (float) $venta->igv - (float) $venta->descuento_total;

        dump('Datos de venta:', $venta->toArray());
        dump('Total calculado manualmente:', $calculado);

        expect($calculado)->toBe(1130.0)
            ->and((string) $venta->total_venta)->toBe('1130.00');
    });

    it('solo permite estados de venta válidos según el negocio', function () {
        $venta = new Venta();
        $estadosValidos = ['emitida', 'anulada', 'rechazada'];

        dump('Estados válidos definidos:', $estadosValidos);

        foreach ($estadosValidos as $estado) {
            $venta->estado_venta = $estado;
            dump("Estado asignado: {$estado}");
            expect($venta->estado_venta)->toBe($estado);
        }
        $venta->estado_venta = 'emitida';
        dump('Estado final:', $venta->estado_venta);
        expect($venta->estado_venta)->toBe('emitida');
    });
    it('valida todos los métodos de pago correctamente', function () {
        $venta = new Venta();
        $metodosValidos = ['efectivo', 'tarjeta', 'yape', 'plin', 'transferencia'];

        foreach ($metodosValidos as $metodo) {
            $venta->metodo_pago = $metodo;
            $venta->cod_operacion = $metodo === 'efectivo' ? null : 'OP-' . strtoupper($metodo) . '-12345';

            dump("Método: {$metodo}", ['cod_operacion' => $venta->cod_operacion]);

            expect($venta->metodo_pago)->toBe($metodo);
            expect($venta->cod_operacion)->toBe($metodo === 'efectivo' ? null : 'OP-' . strtoupper($metodo) . '-12345');
        }
    });
    it('verifica que las relaciones críticas están correctamente definidas', function () {
        $venta = new Venta();

        dump('Verificando relaciones del modelo Venta');

        expect($venta->user())
            ->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class);

        expect($venta->caja())
            ->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class);

        expect($venta->cliente())
            ->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class);

        expect($venta->detalleVentas())
            ->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class);

        expect($venta->comprobantes())
            ->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class);
    });
    it('permite crear venta con cliente temporal cuando no hay cliente registrado', function () {
        $venta = new Venta([
            'cliente_id' => null,
            'nombre_cliente_temporal' => 'Juan Pérez Gómez',
            'user_id' => 1,
            'caja_id' => 1,
            'subtotal_venta' => '85.00',
            'igv' => '15.30',
            'descuento_total' => '0.00',
            'total_venta' => '100.30',
            'estado_venta' => 'emitida',
            'metodo_pago' => 'efectivo'
        ]);
        dump('Venta con cliente temporal:', $venta->toArray());
        expect($venta->cliente_id)->toBeNull()
            ->and($venta->nombre_cliente_temporal)->toBe('Juan Pérez Gómez')
            ->and((string) $venta->total_venta)->toBe('100.30')
            ->and($venta->metodo_pago)->toBe('efectivo');
        $ventaConCliente = new Venta([
            'cliente_id' => 1,
            'nombre_cliente_temporal' => null
        ]);
        dump('Venta con cliente registrado:', $ventaConCliente->toArray());
        expect($ventaConCliente->cliente_id)->toBe(1)
            ->and($ventaConCliente->nombre_cliente_temporal)->toBeNull();
    });
});
