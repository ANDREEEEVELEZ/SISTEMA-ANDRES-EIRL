<?php

namespace Tests\Unit;

use App\Models\Empleado;
use Tests\TestCase;

class EmpleadoTest extends TestCase
{
    public function test_verifica_modelo_empleado_existe(): void
    {
        $empleado = new Empleado();
        
        $this->assertInstanceOf(Empleado::class, $empleado);
        $this->assertEquals('empleados', $empleado->getTable());
    }

    public function test_verifica_fillable_empleado(): void
    {
        $empleado = new Empleado();
        $fillable = $empleado->getFillable();
        
        $expectedFillable = [
            'user_id',
            'nombres',
            'apellidos',
            'dni',
            'telefono',
            'direccion',
            'fecha_nacimiento',
            'correo_empleado',
            'distrito',
            'fecha_incorporacion',
            'estado_empleado',
            'foto_facial_path',
            'face_descriptors',
        ];
        
        $this->assertEquals($expectedFillable, $fillable);
    }

    public function test_verifica_casts_empleado(): void
    {
        $empleado = new Empleado();
        $casts = $empleado->getCasts();
        
        $this->assertArrayHasKey('fecha_nacimiento', $casts);
        $this->assertArrayHasKey('fecha_incorporacion', $casts);
        $this->assertEquals('date', $casts['fecha_nacimiento']);
        $this->assertEquals('date', $casts['fecha_incorporacion']);
    }

    public function test_verifica_relacion_user_funciona(): void
    {
        $empleado = new Empleado();
        
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $empleado->user());
    }

    public function test_verifica_relacion_asistencias_funciona(): void
    {
        $empleado = new Empleado();
        
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $empleado->asistencias());
    }

    public function test_verifica_relacion_producciones_diarias_funciona(): void
    {
        $empleado = new Empleado();
        
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $empleado->produccionesDiarias());
    }

    public function test_verifica_relacion_ventas_funciona(): void
    {
        $empleado = new Empleado();
        
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $empleado->ventas());
    }

    public function test_verifica_accessor_nombre_completo(): void
    {
        $empleado = new Empleado();
        $empleado->nombres = 'Juan Carlos';
        $empleado->apellidos = 'García López';
        
        $this->assertEquals('Juan Carlos García López', $empleado->nombre_completo);
    }

    public function test_verifica_accessor_nombre(): void
    {
        $empleado = new Empleado();
        $empleado->nombres = 'María Elena';
        
        $this->assertEquals('María Elena', $empleado->nombre);
    }

    public function test_verifica_accessor_apellido(): void
    {
        $empleado = new Empleado();
        $empleado->apellidos = 'Rodríguez Silva';
        
        $this->assertEquals('Rodríguez Silva', $empleado->apellido);
    }
}