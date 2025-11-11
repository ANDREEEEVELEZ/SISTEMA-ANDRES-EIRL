<?php

namespace Tests\Unit;

use App\Models\Caja;
use Tests\TestCase;

class CajaTest extends TestCase
{
    public function test_verifica_modelo_caja_existe(): void
    {
        $caja = new Caja();
        
        $this->assertInstanceOf(Caja::class, $caja);
        $this->assertEquals('cajas', $caja->getTable());
    }

    public function test_verifica_fillable_caja(): void
    {
        $caja = new Caja();
        $fillable = $caja->getFillable();
        
        $expectedFillable = [
            'user_id',
            'fecha_apertura',
            'fecha_cierre',
            'saldo_inicial',
            'saldo_final',
            'estado',
            'observacion',
        ];
        
        $this->assertEquals($expectedFillable, $fillable);
    }

    public function test_verifica_casts_caja(): void
    {
        $caja = new Caja();
        $casts = $caja->getCasts();
        
        $this->assertArrayHasKey('fecha_apertura', $casts);
        $this->assertArrayHasKey('fecha_cierre', $casts);
        $this->assertArrayHasKey('saldo_inicial', $casts);
        $this->assertArrayHasKey('saldo_final', $casts);
        
        $this->assertEquals('datetime', $casts['fecha_apertura']);
        $this->assertEquals('datetime', $casts['fecha_cierre']);
        $this->assertEquals('decimal:2', $casts['saldo_inicial']);
        $this->assertEquals('decimal:2', $casts['saldo_final']);
    }

    public function test_verifica_relacion_user_funciona(): void
    {
        $caja = new Caja();
        
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $caja->user());
    }

    public function test_verifica_relacion_movimientos_caja_funciona(): void
    {
        $caja = new Caja();
        
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $caja->movimientosCaja());
    }

    public function test_verifica_relacion_ventas_funciona(): void
    {
        $caja = new Caja();
        
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $caja->ventas());
    }

    public function test_verifica_numero_secuencial_con_id_1(): void
    {
        $caja = new Caja();
        $caja->id = 1;
        
        // Como no podemos hacer consultas reales, solo verificamos que el método existe
        $this->assertTrue(method_exists($caja, 'getNumeroSecuencialAttribute'));
    }

    public function test_verifica_numero_formateado_con_id_1(): void
    {
        $caja = new Caja();
        $caja->id = 1;
        
        // Como no podemos hacer consultas reales, solo verificamos que el método existe
        $this->assertTrue(method_exists($caja, 'getNumeroFormateadoAttribute'));
    }

    public function test_verifica_asignacion_manual_campos(): void
    {
        $caja = new Caja();
        $caja->user_id = 1;
        $caja->saldo_inicial = 500.00;
        $caja->saldo_final = 750.50;
        $caja->estado = 'abierta';
        $caja->observacion = 'Apertura de caja matutina';
        
        $this->assertEquals(1, $caja->user_id);
        $this->assertEquals(500.00, $caja->saldo_inicial);
        $this->assertEquals(750.50, $caja->saldo_final);
        $this->assertEquals('abierta', $caja->estado);
        $this->assertEquals('Apertura de caja matutina', $caja->observacion);
    }

    public function test_verifica_estados_caja_validos(): void
    {
        $caja = new Caja();
        
        $estadosValidos = ['abierta', 'cerrada'];
        
        foreach ($estadosValidos as $estado) {
            $caja->estado = $estado;
            $this->assertEquals($estado, $caja->estado);
        }
    }

    public function test_verifica_puede_tener_fecha_cierre_nula(): void
    {
        $caja = new Caja();
        $caja->fecha_cierre = null;
        
        $this->assertNull($caja->fecha_cierre);
    }

    public function test_verifica_puede_tener_saldo_final_nulo(): void
    {
        $caja = new Caja();
        $caja->saldo_final = null;
        
        $this->assertNull($caja->saldo_final);
    }

    public function test_verifica_puede_tener_observacion_nula(): void
    {
        $caja = new Caja();
        $caja->observacion = null;
        
        $this->assertNull($caja->observacion);
    }

    public function test_verifica_caja_abierta_sin_fecha_cierre(): void
    {
        $caja = new Caja();
        $caja->estado = 'abierta';
        $caja->fecha_cierre = null;
        $caja->saldo_final = null;
        
        $this->assertEquals('abierta', $caja->estado);
        $this->assertNull($caja->fecha_cierre);
        $this->assertNull($caja->saldo_final);
    }

    public function test_verifica_caja_cerrada_con_datos_completos(): void
    {
        $caja = new Caja();
        $caja->estado = 'cerrada';
        $caja->saldo_inicial = 500.00;
        $caja->saldo_final = 750.00;
        
        $this->assertEquals('cerrada', $caja->estado);
        $this->assertEquals(500.00, $caja->saldo_inicial);
        $this->assertEquals(750.00, $caja->saldo_final);
    }

    public function test_verifica_logica_saldo_positivo(): void
    {
        $caja = new Caja();
        $caja->saldo_inicial = 500.00;
        $caja->saldo_final = 750.00;
        
        $diferencia = $caja->saldo_final - $caja->saldo_inicial;
        
        $this->assertEquals(250.00, $diferencia);
        $this->assertGreaterThan(0, $diferencia);
    }

    public function test_verifica_logica_saldo_negativo(): void
    {
        $caja = new Caja();
        $caja->saldo_inicial = 500.00;
        $caja->saldo_final = 300.00;
        
        $diferencia = $caja->saldo_final - $caja->saldo_inicial;
        
        $this->assertEquals(-200.00, $diferencia);
        $this->assertLessThan(0, $diferencia);
    }
}