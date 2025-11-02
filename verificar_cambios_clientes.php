<?php

/**
 * Script de verificación de cambios en tabla clientes
 * Este script verifica que la migración se ejecutó correctamente
 *
 * Ejecutar con: php verificar_cambios_clientes.php
 */

require __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\DB;

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "\n";
echo "===========================================\n";
echo "  VERIFICACIÓN DE CAMBIOS - TABLA CLIENTES\n";
echo "===========================================\n\n";

try {
    // 1. Verificar estructura de la columna tipo_cliente
    echo "1. Verificando estructura de la tabla clientes...\n";
    $columns = DB::select("SHOW COLUMNS FROM clientes LIKE 'tipo_cliente'");

    if (empty($columns)) {
        echo "    ERROR: No se encontró la columna 'tipo_cliente'\n";
        exit(1);
    }

    $column = $columns[0];
    echo "    Columna encontrada\n";
    echo "   - Tipo: {$column->Type}\n";
    echo "   - Default: {$column->Default}\n\n";

    // Verificar que contiene los nuevos valores
    if (strpos($column->Type, 'natural_con_negocio') !== false &&
        strpos($column->Type, 'juridica') !== false) {
        echo "   La columna contiene los nuevos valores del enum\n\n";
    } else {
        echo "    ERROR: La columna NO contiene los valores esperados\n\n";
        exit(1);
    }

    // 2. Verificar clientes existentes
    echo "2. Verificando distribución de tipos de cliente...\n";
    $stats = DB::table('clientes')
        ->select('tipo_cliente', DB::raw('COUNT(*) as total'))
        ->groupBy('tipo_cliente')
        ->get();

    if ($stats->isEmpty()) {
        echo "   ℹ️  No hay clientes registrados aún\n\n";
    } else {
        foreach ($stats as $stat) {
            $emoji = match($stat->tipo_cliente) {
                'natural' => '👤',
                'natural_con_negocio' => '',
                'juridica' => '',
                default => ''
            };
            echo "   {$emoji} {$stat->tipo_cliente}: {$stat->total} cliente(s)\n";
        }
        echo "\n";
    }

    // 3. Verificar clientes con DNI
    echo "3. Verificando clientes con DNI...\n";
    $clientesDNI = DB::table('clientes')
        ->where('tipo_doc', 'DNI')
        ->get();

    $dniOK = true;
    foreach ($clientesDNI as $cliente) {
        if ($cliente->tipo_cliente !== 'natural') {
            echo "     Cliente ID {$cliente->id} con DNI tiene tipo_cliente: {$cliente->tipo_cliente}\n";
            $dniOK = false;
        }
    }

    if ($dniOK) {
        echo "    Todos los clientes con DNI son tipo 'natural' ({$clientesDNI->count()} cliente(s))\n\n";
    } else {
        echo "     Hay inconsistencias en clientes con DNI\n\n";
    }

    // 4. Verificar clientes con RUC
    echo "4. Verificando clientes con RUC...\n";
    $clientesRUC = DB::table('clientes')
        ->where('tipo_doc', 'RUC')
        ->get();

    $ruc10 = 0;
    $ruc20 = 0;
    $rucOtros = 0;

    foreach ($clientesRUC as $cliente) {
        $prefijo = substr($cliente->num_doc, 0, 2);

        if ($prefijo === '10') {
            $ruc10++;
            if ($cliente->tipo_cliente !== 'natural_con_negocio') {
                echo "    Cliente ID {$cliente->id} con RUC 10 tiene tipo: {$cliente->tipo_cliente}\n";
            }
        } elseif ($prefijo === '20') {
            $ruc20++;
            if ($cliente->tipo_cliente !== 'juridica') {
                echo "    Cliente ID {$cliente->id} con RUC 20 tiene tipo: {$cliente->tipo_cliente}\n";
            }
        } else {
            $rucOtros++;
        }
    }

    echo "   RUC con prefijo 10: {$ruc10} cliente(s)\n";
    echo "    RUC con prefijo 20: {$ruc20} cliente(s)\n";
    echo "    RUC con otros prefijos: {$rucOtros} cliente(s)\n\n";

    // 5. Resumen final
    echo "===========================================\n";
    echo "   VERIFICACIÓN COMPLETADA\n";
    echo "===========================================\n\n";
    echo "Todo parece estar funcionando correctamente.\n";
    echo "Ahora puedes:\n";
    echo "  1. Ir al módulo de Clientes en el sistema\n";
    echo "  2. Crear un nuevo cliente con DNI\n";
    echo "  3. Crear un nuevo cliente con RUC\n";
    echo "  4. Verificar que la lógica reactiva funcione\n\n";

} catch (\Exception $e) {
    echo "\n ERROR: " . $e->getMessage() . "\n\n";
    exit(1);
}
