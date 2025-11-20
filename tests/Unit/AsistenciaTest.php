<?php

namespace Tests\Unit;

use App\Models\Asistencia;
use Tests\TestCase;

class AsistenciaTest extends TestCase
{
    public function test_verifica_modelo_asistencia_existe(): void
    {
        $asistencia = new Asistencia();
        
        $this->assertInstanceOf(Asistencia::class, $asistencia);
        $this->assertEquals('asistencias', $asistencia->getTable());
    }

    public function test_verifica_fillable_asistencia(): void
    {
        $asistencia = new Asistencia();
        $fillable = $asistencia->getFillable();
        
        $expectedFillable = [
            'empleado_id',
            'fecha',
            'hora_entrada',
            'hora_salida',
            'estado',
            'observacion',
            'metodo_registro',
            'razon_manual',
        ];
        
        $this->assertEquals($expectedFillable, $fillable);
    }

    public function test_verifica_casts_asistencia(): void
    {
        $asistencia = new Asistencia();
        $casts = $asistencia->getCasts();
        
        $this->assertArrayHasKey('fecha', $casts);
        $this->assertArrayHasKey('hora_entrada', $casts);
        $this->assertArrayHasKey('hora_salida', $casts);
        $this->assertEquals('date', $casts['fecha']);
        $this->assertEquals('datetime:H:i', $casts['hora_entrada']);
        $this->assertEquals('datetime:H:i', $casts['hora_salida']);
    }

    public function test_verifica_relacion_empleado_funciona(): void
    {
        $asistencia = new Asistencia();
        
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $asistencia->empleado());
    }

    public function test_verifica_metodo_registro_formateado_facial(): void
    {
        $asistencia = new Asistencia();
        $asistencia->metodo_registro = 'facial';
        
        $this->assertEquals('Reconocimiento Facial', $asistencia->metodo_registro_formateado);
    }

    public function test_verifica_metodo_registro_formateado_manual_dni(): void
    {
        $asistencia = new Asistencia();
        $asistencia->metodo_registro = 'manual_dni';
        
        $this->assertEquals('Manual (DNI)', $asistencia->metodo_registro_formateado);
    }

    public function test_verifica_metodo_registro_formateado_desconocido(): void
    {
        $asistencia = new Asistencia();
        $asistencia->metodo_registro = 'otro_metodo';
        
        $this->assertEquals('Desconocido', $asistencia->metodo_registro_formateado);
    }

    public function test_verifica_es_registro_manual_verdadero(): void
    {
        $asistencia = new Asistencia();
        $asistencia->metodo_registro = 'manual_dni';
        
        $this->assertTrue($asistencia->esRegistroManual());
    }

    public function test_verifica_es_registro_manual_falso(): void
    {
        $asistencia = new Asistencia();
        $asistencia->metodo_registro = 'facial';
        
        $this->assertFalse($asistencia->esRegistroManual());
    }

    public function test_verifica_asignacion_manual_campos(): void
    {
        $asistencia = new Asistencia();
        $asistencia->empleado_id = 1;
        $asistencia->estado = 'presente';
        $asistencia->observacion = 'Asistencia normal';
        $asistencia->metodo_registro = 'facial';
        $asistencia->razon_manual = null;
        
        $this->assertEquals(1, $asistencia->empleado_id);
        $this->assertEquals('presente', $asistencia->estado);
        $this->assertEquals('Asistencia normal', $asistencia->observacion);
        $this->assertEquals('facial', $asistencia->metodo_registro);
        $this->assertNull($asistencia->razon_manual);
    }

    public function test_verifica_estados_validos(): void
    {
        $asistencia = new Asistencia();
        
        $estadosValidos = ['presente', 'falta', 'permiso'];
        
        foreach ($estadosValidos as $estado) {
            $asistencia->estado = $estado;
            $this->assertEquals($estado, $asistencia->estado);
        }
    }

    public function test_verifica_metodos_registro_validos(): void
    {
        $asistencia = new Asistencia();
        
        $metodosValidos = ['facial', 'manual_dni'];
        
        foreach ($metodosValidos as $metodo) {
            $asistencia->metodo_registro = $metodo;
            $this->assertEquals($metodo, $asistencia->metodo_registro);
        }
    }

    public function test_verifica_puede_tener_observacion_nula(): void
    {
        $asistencia = new Asistencia();
        $asistencia->observacion = null;
        
        $this->assertNull($asistencia->observacion);
    }

    public function test_verifica_puede_tener_razon_manual_nula(): void
    {
        $asistencia = new Asistencia();
        $asistencia->razon_manual = null;
        
        $this->assertNull($asistencia->razon_manual);
    }
}