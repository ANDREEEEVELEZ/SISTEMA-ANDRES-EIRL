<?php

use App\Models\Venta;
use App\Models\Caja;
use App\Models\User;
use App\Models\Cliente;
use App\Models\MovimientoCaja;

describe('Integración: Ventas y Cajas', function () {

    it('solo ventas en efectivo incrementan saldo de caja', function () {
        $caja = Caja::factory()->abierta()->create(['saldo_inicial' => 500.00]);
        Venta::factory()->create([
            'caja_id' => $caja->id,
            'total_venta' => 100.00,
            'metodo_pago' => 'efectivo',
            'estado_venta' => 'emitida',
        ]);
        Venta::factory()->create([
            'caja_id' => $caja->id,
            'total_venta' => 200.00,
            'metodo_pago' => 'yape',
            'estado_venta' => 'emitida',
        ]);
        $totalEfectivo = $caja->ventas()
            ->where('metodo_pago', 'efectivo')
            ->sum('total_venta');
        echo "Total en efectivo: {$totalEfectivo}\n";
        echo "Total yape: " . $caja->ventas()->where('metodo_pago', 'yape')->sum('total_venta') . "\n";
        expect((float)$totalEfectivo)->toBe(100.00);
    });


    it('calcula saldo esperado al cierre de caja', function () {
        $user = User::factory()->create();
        $caja = Caja::factory()->abierta()->create([
            'user_id' => $user->id,
            'saldo_inicial' => 500.00,
        ]);
        Venta::factory()->count(3)->create([
            'caja_id' => $caja->id,
            'total_venta' => 100.00,
            'metodo_pago' => 'efectivo',
            'estado_venta' => 'emitida',
        ]);
        MovimientoCaja::create([
            'caja_id' => $caja->id,
            'tipo' => 'ingreso',
            'monto' => 50.00,
            'descripcion' => 'Ingreso extra',
            'fecha_movimiento' => now(),
        ]);
        MovimientoCaja::create([
            'caja_id' => $caja->id,
            'tipo' => 'egreso',
            'monto' => 30.00,
            'descripcion' => 'Compra de insumos',
            'fecha_movimiento' => now(),
        ]);
        $ventasEfectivo = $caja->ventas()
            ->where('metodo_pago', 'efectivo')
            ->sum('total_venta');
        $ingresos = $caja->movimientosCaja()
            ->where('tipo', 'ingreso')
            ->sum('monto');
        $egresos = $caja->movimientosCaja()
            ->where('tipo', 'egreso')
            ->sum('monto');
        $saldoEsperado = $caja->saldo_inicial + $ventasEfectivo + $ingresos - $egresos;
        echo "Saldo inicial: {$caja->saldo_inicial}\n";
        echo "Ventas efectivo: {$ventasEfectivo}\n";
        echo "Ingresos: {$ingresos}\n";
        echo "Egresos: {$egresos}\n";
        echo "Saldo esperado: {$saldoEsperado}\n";
        expect((float)$saldoEsperado)->toBe(820.00);
    });

});

describe('Integración: Caja con Arqueos', function () {
    it('puede realizar arqueo de caja al final del día', function () {
        $user = User::factory()->create();
        $caja = Caja::factory()->abierta()->create([
            'user_id' => $user->id,
            'saldo_inicial' => 500.00,
        ]);
        Venta::factory()->count(10)->create([
            'caja_id' => $caja->id,
            'total_venta' => 50.00,
            'metodo_pago' => 'efectivo',
            'estado_venta' => 'emitida',
        ]);
        $montoSistema = $caja->saldo_inicial + $caja->ventas()
            ->where('metodo_pago', 'efectivo')
            ->sum('total_venta');
        $montoFisico = 990.00;
        $arqueo = \App\Models\Arqueo::create([
            'caja_id' => $caja->id,
            'user_id' => $user->id,
            'fecha_inicio' => now()->subHours(8),
            'fecha_fin' => now(),
            'saldo_inicial' => $caja->saldo_inicial,
            'total_ventas' => $caja->ventas()->sum('total_venta'),
            'total_ingresos' => 0,
            'total_egresos' => 0,
            'saldo_teorico' => $montoSistema,
            'efectivo_contado' => $montoFisico,
            'diferencia' => $montoFisico - $montoSistema,
            'estado' => 'confirmado',
            'observacion' => 'Diferencia de S/. 10.00',
        ]);
        echo "Saldo teórico: {$arqueo->saldo_teorico}\n";
        echo "Efectivo contado: {$arqueo->efectivo_contado}\n";
        echo "Diferencia: {$arqueo->diferencia}\n";
        $totalArqueos = \App\Models\Arqueo::where('caja_id', $caja->id)->count();
        expect((float)$arqueo->diferencia)->toBe(-10.00)
            ->and($arqueo->estado->value)->toBe('confirmado')
            ->and($totalArqueos)->toBe(1);
    });

});




