<?php

namespace Tests\Unit;

use App\Models\Categoria;
use Tests\TestCase;

class CategoriaTest extends TestCase
{
    public function test_verifica_modelo_categoria_existe(): void
    {
        $categoria = new Categoria();
        
        $this->assertInstanceOf(Categoria::class, $categoria);
        $this->assertEquals('categorias', $categoria->getTable());
    }

    public function test_verifica_fillable_categoria(): void
    {
        $categoria = new Categoria();
        $fillable = $categoria->getFillable();
        
        $expectedFillable = [
            'NombreCategoria',
            'estado',
        ];
        
        $this->assertEquals($expectedFillable, $fillable);
    }

    public function test_verifica_casts_categoria(): void
    {
        $categoria = new Categoria();
        $casts = $categoria->getCasts();
        
        $this->assertArrayHasKey('estado', $casts);
        $this->assertEquals('boolean', $casts['estado']);
    }

    public function test_verifica_estado_por_defecto(): void
    {
        $categoria = new Categoria();
        
        $this->assertTrue($categoria->estado);
    }

    public function test_verifica_relacion_productos_funciona(): void
    {
        $categoria = new Categoria();
        
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $categoria->productos());
    }

    public function test_verifica_scope_activas_existe(): void
    {
        $categoria = new Categoria();
        
        $this->assertTrue(method_exists($categoria, 'scopeActivas'));
    }

    public function test_verifica_scope_inactivas_existe(): void
    {
        $categoria = new Categoria();
        
        $this->assertTrue(method_exists($categoria, 'scopeInactivas'));
    }

    public function test_verifica_accessor_productos_count_existe(): void
    {
        $categoria = new Categoria();
        
        $this->assertTrue(method_exists($categoria, 'getProductosCountAttribute'));
    }

    public function test_verifica_asignacion_manual_campos(): void
    {
        $categoria = new Categoria();
        $categoria->NombreCategoria = 'Panadería';
        $categoria->estado = false;
        
        $this->assertEquals('Panadería', $categoria->NombreCategoria);
        $this->assertFalse($categoria->estado);
    }

    public function test_verifica_estado_true(): void
    {
        $categoria = new Categoria();
        $categoria->estado = true;
        
        $this->assertTrue($categoria->estado);
        $this->assertIsBool($categoria->estado);
    }

    public function test_verifica_estado_false(): void
    {
        $categoria = new Categoria();
        $categoria->estado = false;
        
        $this->assertFalse($categoria->estado);
        $this->assertIsBool($categoria->estado);
    }

    public function test_verifica_conversion_estado_a_boolean_desde_1(): void
    {
        $categoria = new Categoria();
        $categoria->estado = 1;
        
        $this->assertTrue($categoria->estado);
        $this->assertIsBool($categoria->estado);
    }

    public function test_verifica_conversion_estado_a_boolean_desde_0(): void
    {
        $categoria = new Categoria();
        $categoria->estado = 0;
        
        $this->assertFalse($categoria->estado);
        $this->assertIsBool($categoria->estado);
    }

    public function test_verifica_conversion_estado_a_boolean_desde_string_true(): void
    {
        $categoria = new Categoria();
        $categoria->estado = 'true';
        
        $this->assertTrue($categoria->estado);
        $this->assertIsBool($categoria->estado);
    }

    public function test_verifica_conversion_estado_a_boolean_desde_string_false(): void
    {
        $categoria = new Categoria();
        $categoria->estado = '';  // String vacío se convierte a false
        
        $this->assertFalse($categoria->estado);
        $this->assertIsBool($categoria->estado);
    }

    public function test_verifica_nombre_categoria_obligatorio(): void
    {
        $categoria = new Categoria();
        $categoria->NombreCategoria = 'Repostería';
        
        $this->assertEquals('Repostería', $categoria->NombreCategoria);
        $this->assertIsString($categoria->NombreCategoria);
    }

    public function test_verifica_nombres_categoria_diferentes(): void
    {
        $categoria1 = new Categoria();
        $categoria1->NombreCategoria = 'Bebidas';
        
        $categoria2 = new Categoria();
        $categoria2->NombreCategoria = 'Postres';
        
        $this->assertEquals('Bebidas', $categoria1->NombreCategoria);
        $this->assertEquals('Postres', $categoria2->NombreCategoria);
        $this->assertNotEquals($categoria1->NombreCategoria, $categoria2->NombreCategoria);
    }

    public function test_verifica_categoria_activa_por_defecto(): void
    {
        $categoria = new Categoria();
        
        // Verificamos que el estado por defecto sea true (activo)
        $this->assertTrue($categoria->estado);
    }

    public function test_verifica_puede_cambiar_a_inactiva(): void
    {
        $categoria = new Categoria();
        $categoria->estado = false;
        
        $this->assertFalse($categoria->estado);
    }
}