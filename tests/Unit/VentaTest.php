<?php

namespace Tests\Unit;

use App\Models\Venta;
use Tests\TestCase;

class VentaTest extends TestCase
{
    public function test_verifica_modelo_venta_existe(): void
    {
        $venta = new Venta();
        
        $this->assertInstanceOf(Venta::class, $venta);
        $this->assertEquals('ventas', $venta->getTable());
    }

    public function test_verifica_fillable_venta(): void
    {
        $venta = new Venta();
        $fillable = $venta->getFillable();
        
        $expectedFillable = [
            'user_id',
            'cliente_id',
            'caja_id',
            'subtotal_venta',
            'igv',
            'descuento_total',
            'total_venta',
            'fecha_venta',
            'hora_venta',
            'estado_venta',
            'metodo_pago',
            'cod_operacion',
            'nombre_cliente_temporal',
        ];
        
        $this->assertEquals($expectedFillable, $fillable);
    }

    public function test_verifica_casts_venta(): void
    {
        $venta = new Venta();
        $casts = $venta->getCasts();
        
        $this->assertArrayHasKey('subtotal_venta', $casts);
        $this->assertArrayHasKey('igv', $casts);
        $this->assertArrayHasKey('descuento_total', $casts);
        $this->assertArrayHasKey('total_venta', $casts);
        $this->assertArrayHasKey('fecha_venta', $casts);
        $this->assertArrayHasKey('hora_venta', $casts);
        
        $this->assertEquals('decimal:2', $casts['subtotal_venta']);
        $this->assertEquals('decimal:2', $casts['igv']);
        $this->assertEquals('decimal:2', $casts['descuento_total']);
        $this->assertEquals('decimal:2', $casts['total_venta']);
        $this->assertEquals('date', $casts['fecha_venta']);
        $this->assertEquals('datetime:H:i', $casts['hora_venta']);
    }

    public function test_verifica_relacion_user_funciona(): void
    {
        $venta = new Venta();
        
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $venta->user());
    }

    public function test_verifica_relacion_cliente_funciona(): void
    {
        $venta = new Venta();
        
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $venta->cliente());
    }

    public function test_verifica_relacion_caja_funciona(): void
    {
        $venta = new Venta();
        
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $venta->caja());
    }

    public function test_verifica_relacion_detalle_ventas_funciona(): void
    {
        $venta = new Venta();
        
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $venta->detalleVentas());
    }

    public function test_verifica_relacion_comprobantes_funciona(): void
    {
        $venta = new Venta();
        
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $venta->comprobantes());
    }

    public function test_verifica_asignacion_manual_campos(): void
    {
        $venta = new Venta();
        $venta->user_id = 1;
        $venta->cliente_id = 1;
        $venta->caja_id = 1;
        $venta->subtotal_venta = 100.00;
        $venta->igv = 18.00;
        $venta->descuento_total = 5.00;
        $venta->total_venta = 113.00;
        $venta->estado_venta = 'emitida';
        $venta->metodo_pago = 'efectivo';
        $venta->cod_operacion = null;
        $venta->nombre_cliente_temporal = 'Juan Pérez';
        
        $this->assertEquals(1, $venta->user_id);
        $this->assertEquals(1, $venta->cliente_id);
        $this->assertEquals(1, $venta->caja_id);
        $this->assertEquals(100.00, $venta->subtotal_venta);
        $this->assertEquals(18.00, $venta->igv);
        $this->assertEquals(5.00, $venta->descuento_total);
        $this->assertEquals(113.00, $venta->total_venta);
        $this->assertEquals('emitida', $venta->estado_venta);
        $this->assertEquals('efectivo', $venta->metodo_pago);
        $this->assertNull($venta->cod_operacion);
        $this->assertEquals('Juan Pérez', $venta->nombre_cliente_temporal);
    }

    public function test_verifica_estados_venta_validos(): void
    {
        $venta = new Venta();
        
        $estadosValidos = ['emitida', 'anulada', 'rechazada'];
        
        foreach ($estadosValidos as $estado) {
            $venta->estado_venta = $estado;
            $this->assertEquals($estado, $venta->estado_venta);
        }
    }

    public function test_verifica_metodos_pago_validos(): void
    {
        $venta = new Venta();
        
        $metodosValidos = ['efectivo', 'tarjeta', 'yape', 'plin', 'transferencia'];
        
        foreach ($metodosValidos as $metodo) {
            $venta->metodo_pago = $metodo;
            $this->assertEquals($metodo, $venta->metodo_pago);
        }
    }

    public function test_verifica_puede_tener_cod_operacion_nulo(): void
    {
        $venta = new Venta();
        $venta->cod_operacion = null;
        
        $this->assertNull($venta->cod_operacion);
    }

    public function test_verifica_puede_tener_nombre_cliente_temporal_nulo(): void
    {
        $venta = new Venta();
        $venta->nombre_cliente_temporal = null;
        
        $this->assertNull($venta->nombre_cliente_temporal);
    }

    public function test_verifica_calculo_total_basico(): void
    {
        $venta = new Venta();
        $venta->subtotal_venta = 100.00;
        $venta->igv = 18.00;
        $venta->descuento_total = 0.00;
        
        $totalEsperado = $venta->subtotal_venta + $venta->igv - $venta->descuento_total;
        $venta->total_venta = $totalEsperado;
        
        $this->assertEquals(118.00, $venta->total_venta);
    }

    public function test_verifica_calculo_total_con_descuento(): void
    {
        $venta = new Venta();
        $venta->subtotal_venta = 100.00;
        $venta->igv = 18.00;
        $venta->descuento_total = 10.00;
        
        $totalEsperado = $venta->subtotal_venta + $venta->igv - $venta->descuento_total;
        $venta->total_venta = $totalEsperado;
        
        $this->assertEquals(108.00, $venta->total_venta);
    }
}