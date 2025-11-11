<?php

namespace Tests\Unit;

use App\Models\Producto;
use Tests\TestCase;

class ProductoTest extends TestCase
{
    public function test_verifica_modelo_producto_existe(): void
    {
        $producto = new Producto();
        
        $this->assertInstanceOf(Producto::class, $producto);
        $this->assertEquals('productos', $producto->getTable());
    }

    public function test_verifica_fillable_producto(): void
    {
        $producto = new Producto();
        $fillable = $producto->getFillable();
        
        $expectedFillable = [
            'categoria_id',
            'nombre_producto',
            'stock_total',
            'descripcion',
            'unidad_medida',
            'estado',
            'stock_minimo',
        ];
        
        $this->assertEquals($expectedFillable, $fillable);
    }

    public function test_verifica_casts_producto(): void
    {
        $producto = new Producto();
        $casts = $producto->getCasts();
        
        $this->assertArrayHasKey('stock_total', $casts);
        $this->assertArrayHasKey('stock_minimo', $casts);
        $this->assertEquals('integer', $casts['stock_total']);
        $this->assertEquals('integer', $casts['stock_minimo']);
    }

    public function test_verifica_relacion_categoria_funciona(): void
    {
        $producto = new Producto();
        
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $producto->categoria());
    }

    public function test_verifica_relacion_precios_productos_funciona(): void
    {
        $producto = new Producto();
        
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $producto->preciosProductos());
    }

    public function test_verifica_relacion_producciones_diarias_funciona(): void
    {
        $producto = new Producto();
        
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $producto->produccionesDiarias());
    }

    public function test_verifica_relacion_detalle_ventas_funciona(): void
    {
        $producto = new Producto();
        
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $producto->detalleVentas());
    }

    public function test_verifica_relacion_movimientos_inventario_funciona(): void
    {
        $producto = new Producto();
        
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $producto->movimientosInventario());
    }

    public function test_verifica_esta_agotado_cuando_stock_es_cero(): void
    {
        $producto = new Producto();
        $producto->stock_total = 0;
        
        $this->assertTrue($producto->estaAgotado());
    }

    public function test_verifica_esta_agotado_cuando_stock_es_negativo(): void
    {
        $producto = new Producto();
        $producto->stock_total = -1;
        
        $this->assertTrue($producto->estaAgotado());
    }

    public function test_verifica_no_esta_agotado_cuando_tiene_stock(): void
    {
        $producto = new Producto();
        $producto->stock_total = 5;
        
        $this->assertFalse($producto->estaAgotado());
    }

    public function test_verifica_tiene_stock_bajo(): void
    {
        $producto = new Producto();
        $producto->stock_total = 3;
        $producto->stock_minimo = 5;
        
        $this->assertTrue($producto->tieneStockBajo());
    }

    public function test_verifica_no_tiene_stock_bajo_cuando_stock_es_mayor_al_minimo(): void
    {
        $producto = new Producto();
        $producto->stock_total = 10;
        $producto->stock_minimo = 5;
        
        $this->assertFalse($producto->tieneStockBajo());
    }

    public function test_verifica_no_tiene_stock_bajo_cuando_esta_agotado(): void
    {
        $producto = new Producto();
        $producto->stock_total = 0;
        $producto->stock_minimo = 5;
        
        $this->assertFalse($producto->tieneStockBajo());
    }

    public function test_verifica_estado_stock_agotado(): void
    {
        $producto = new Producto();
        $producto->stock_total = 0;
        $producto->stock_minimo = 5;
        
        $this->assertEquals('agotado', $producto->estado_stock);
    }

    public function test_verifica_estado_stock_bajo(): void
    {
        $producto = new Producto();
        $producto->stock_total = 3;
        $producto->stock_minimo = 5;
        
        $this->assertEquals('bajo', $producto->estado_stock);
    }

    public function test_verifica_estado_stock_normal(): void
    {
        $producto = new Producto();
        $producto->stock_total = 20;
        $producto->stock_minimo = 5;
        
        $this->assertEquals('normal', $producto->estado_stock);
    }

    public function test_verifica_asignacion_manual_campos(): void
    {
        $producto = new Producto();
        $producto->categoria_id = 1;
        $producto->nombre_producto = 'Pan Integral';
        $producto->stock_total = 25;
        $producto->descripcion = 'Pan integral artesanal';
        $producto->unidad_medida = 'unidad';
        $producto->estado = 'activo';
        $producto->stock_minimo = 5;
        
        $this->assertEquals(1, $producto->categoria_id);
        $this->assertEquals('Pan Integral', $producto->nombre_producto);
        $this->assertEquals(25, $producto->stock_total);
        $this->assertEquals('Pan integral artesanal', $producto->descripcion);
        $this->assertEquals('unidad', $producto->unidad_medida);
        $this->assertEquals('activo', $producto->estado);
        $this->assertEquals(5, $producto->stock_minimo);
    }
}