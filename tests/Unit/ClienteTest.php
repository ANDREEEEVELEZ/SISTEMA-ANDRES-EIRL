<?php

namespace Tests\Unit;

use App\Models\Cliente;
use Tests\TestCase;

class ClienteTest extends TestCase
{
    public function test_verifica_modelo_cliente_existe(): void
    {
        $cliente = new Cliente();
        
        $this->assertInstanceOf(Cliente::class, $cliente);
        $this->assertEquals('clientes', $cliente->getTable());
    }

    public function test_verifica_fillable_cliente(): void
    {
        $cliente = new Cliente();
        $fillable = $cliente->getFillable();
        
        $expectedFillable = [
            'tipo_doc',
            'tipo_cliente',
            'num_doc',
            'nombre_razon',
            'fecha_registro',
            'estado',
            'telefono',
            'direccion',
        ];
        
        $this->assertEquals($expectedFillable, $fillable);
    }

    public function test_verifica_casts_cliente(): void
    {
        $cliente = new Cliente();
        $casts = $cliente->getCasts();
        
        $this->assertArrayHasKey('fecha_registro', $casts);
        $this->assertEquals('date', $casts['fecha_registro']);
    }

    public function test_verifica_estado_por_defecto(): void
    {
        $cliente = new Cliente();
        
        $this->assertEquals('activo', $cliente->estado);
    }

    public function test_verifica_relacion_ventas_funciona(): void
    {
        $cliente = new Cliente();
        
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $cliente->ventas());
    }

    public function test_verifica_relacion_comprobantes_funciona(): void
    {
        $cliente = new Cliente();
        
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $cliente->comprobantes());
    }

    public function test_verifica_asignacion_manual_campos(): void
    {
        $cliente = new Cliente();
        $cliente->tipo_doc = 'DNI';
        $cliente->num_doc = '12345678';
        $cliente->nombre_razon = 'Juan Pérez';
        $cliente->telefono = '987654321';
        $cliente->direccion = 'Av. Los Olivos 123';
        
        $this->assertEquals('DNI', $cliente->tipo_doc);
        $this->assertEquals('12345678', $cliente->num_doc);
        $this->assertEquals('Juan Pérez', $cliente->nombre_razon);
        $this->assertEquals('987654321', $cliente->telefono);
        $this->assertEquals('Av. Los Olivos 123', $cliente->direccion);
    }

    public function test_verifica_tipos_documento_validos(): void
    {
        $cliente = new Cliente();
        
        $cliente->tipo_doc = 'DNI';
        $this->assertEquals('DNI', $cliente->tipo_doc);
        
        $cliente->tipo_doc = 'RUC';
        $this->assertEquals('RUC', $cliente->tipo_doc);
    }

    public function test_verifica_tipos_cliente_validos(): void
    {
        $cliente = new Cliente();
        
        $cliente->tipo_cliente = 'natural';
        $this->assertEquals('natural', $cliente->tipo_cliente);
        
        $cliente->tipo_cliente = 'juridica';
        $this->assertEquals('juridica', $cliente->tipo_cliente);
        
        $cliente->tipo_cliente = 'otro';
        $this->assertEquals('otro', $cliente->tipo_cliente);
    }

    public function test_verifica_estados_validos(): void
    {
        $cliente = new Cliente();
        
        $cliente->estado = 'activo';
        $this->assertEquals('activo', $cliente->estado);
        
        $cliente->estado = 'inactivo';
        $this->assertEquals('inactivo', $cliente->estado);
    }
}