<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "\n";
echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║     REENVIAR NOTAS DE CRÉDITO PENDIENTES                    ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n";
echo "\n";

// Buscar todas las notas de crédito sin XML que anulan comprobantes enviados a SUNAT
$notasPendientes = \App\Models\Comprobante::where('tipo', 'nota de credito')
    ->whereNull('ruta_xml')
    ->whereNull('codigo_sunat')
    ->get();

$notasParaEnviar = [];

foreach ($notasPendientes as $nota) {
    $relacion = \App\Models\ComprobanteRelacion::where('comprobante_relacionado_id', $nota->id)->first();
    
    if ($relacion) {
        $origen = $relacion->comprobanteOrigen;
        
        // Verificar si el comprobante original fue enviado a SUNAT
        $fueEnviado = !empty($origen->ruta_xml) || !empty($origen->ruta_cdr) || !empty($origen->codigo_sunat);
        
        if ($origen->tipo === 'boleta') {
            $fueEnviado = $fueEnviado && !empty($origen->ticket_sunat);
        }
        
        if ($fueEnviado) {
            $notasParaEnviar[] = [
                'nota' => $nota,
                'origen' => $origen,
            ];
        }
    }
}

if (empty($notasParaEnviar)) {
    echo "✅ No hay notas de crédito pendientes de enviar\n";
    echo "\n";
    exit(0);
}

echo "📋 Se encontraron " . count($notasParaEnviar) . " notas de crédito pendientes de enviar:\n";
echo "\n";

foreach ($notasParaEnviar as $i => $item) {
    $nota = $item['nota'];
    $origen = $item['origen'];
    
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo ($i + 1) . ". Nota: {$nota->serie}-{$nota->correlativo} (ID: {$nota->id})\n";
    echo "   Anula: {$origen->serie}-{$origen->correlativo} ({$origen->tipo})\n";
    echo "\n";
}

$respuesta = readline("¿Deseas enviar TODAS estas notas de crédito a SUNAT? (s/n): ");

if (strtolower($respuesta) !== 's') {
    echo "\n❌ Operación cancelada\n\n";
    exit(0);
}

echo "\n";
echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║               ENVIANDO NOTAS A SUNAT...                     ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n";
echo "\n";

$sunatService = new \App\Services\SunatService();
$enviadas = 0;
$fallidas = 0;

foreach ($notasParaEnviar as $i => $item) {
    $nota = $item['nota'];
    $origen = $item['origen'];
    
    echo ($i + 1) . ". Enviando {$nota->serie}-{$nota->correlativo}... ";
    
    try {
        $resultado = $sunatService->enviarNotaCredito($nota);
        
        if ($resultado['success']) {
            echo "✅ ACEPTADA\n";
            $enviadas++;
        } else {
            echo "❌ RECHAZADA: {$resultado['message']}\n";
            $fallidas++;
        }
    } catch (\Exception $e) {
        echo "❌ ERROR: {$e->getMessage()}\n";
        $fallidas++;
    }
}

echo "\n";
echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║                      RESUMEN                                 ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n";
echo "\n";
echo "✅ Enviadas exitosamente: {$enviadas}\n";
echo "❌ Fallidas: {$fallidas}\n";
echo "\n";
