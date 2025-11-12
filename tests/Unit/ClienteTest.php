<?php

use App\Models\Cliente;
use function Pest\Laravel\{assertDatabaseHas};

describe('Cliente Model', function () {

    it('crea un cliente básico y establece valores por defecto', function () {
        $cliente = Cliente::factory()->conDNI()->create();

        dump($cliente->toArray());

        expect($cliente)->toBeInstanceOf(Cliente::class)
            ->and($cliente->tipo_doc)->toBe('DNI')
            ->and($cliente->num_doc)->toHaveLength(8)
            ->and($cliente->estado)->toBe('activo')
            ->and($cliente->fecha_registro)->not->toBeNull();
    });
        it('valida que num_doc sea único', function () {
            $numDoc = '12345678';
            $cliente = Cliente::factory()->create([
                'tipo_doc' => 'DNI',
                'num_doc' => $numDoc,
            ]);

            dump($cliente->toArray());
            $this->expectException(\Illuminate\Database\QueryException::class);
            Cliente::factory()->create([
                'tipo_doc' => 'DNI',
                'num_doc' => $numDoc,
            ]);
        });


    it('actualiza el estado del cliente correctamente en la base de datos', function () {
        $cliente = Cliente::factory()->create(['estado' => 'activo']);
        dump($cliente->toArray());
        $cliente->update(['estado' => 'inactivo']);
        dump($cliente->fresh()->toArray());
        expect($cliente->estado)->toBe('inactivo');
        assertDatabaseHas('clientes', [
            'id' => $cliente->id,
            'estado' => 'inactivo',
        ]);
    });

    it('determina tipo_cliente automáticamente para RUC', function () {
        $cliente = Cliente::factory()->create([
            'tipo_doc' => 'RUC',
            'num_doc' => '20609709406',
            'tipo_cliente' => null,
        ]);

        dump($cliente->toArray());

        expect($cliente->fresh()->tipo_cliente)->toBe('juridica');
    });

});
