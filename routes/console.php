<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');


Schedule::command('sunat:enviar-resumen-diario')
    ->dailyAt('00:30')
    ->withoutOverlapping()
    ->onSuccess(function () {
        \Illuminate\Support\Facades\Log::info('Resumen Diario enviado automáticamente');
    })
    ->onFailure(function () {
        \Illuminate\Support\Facades\Log::error('Error al enviar Resumen Diario automático');
    });

// 2. Consultar tickets pendientes (resúmenes enviados pero sin respuesta)
//    Se ejecuta cada hora entre 1:00 AM y 8:00 AM
Schedule::call(function () {
    $sunatService = app(\App\Services\SunatService::class);

    // Buscar comprobantes con ticket pero sin codigo_sunat
    $comprobantesPendientes = \App\Models\Comprobante::whereNotNull('ticket_sunat')
        ->whereNull('codigo_sunat')
        ->whereNotNull('fecha_envio_sunat')
        ->where('fecha_envio_sunat', '>=', now()->subDays(7)) // Últimos 7 días
        ->get();

    foreach ($comprobantesPendientes->unique('ticket_sunat') as $comprobante) {
        $resultado = $sunatService->consultarTicketResumen($comprobante->ticket_sunat);

        if ($resultado['success']) {
            \Illuminate\Support\Facades\Log::info("✅ Ticket {$comprobante->ticket_sunat} procesado: {$resultado['mensaje']}");
        } else {
            \Illuminate\Support\Facades\Log::info("⏳ Ticket {$comprobante->ticket_sunat} aún pendiente");
        }

        // Esperar 2 segundos entre consultas para no saturar SUNAT
        sleep(2);
    }
})->hourly()->between('1:00', '8:00');

// 3. Limpiar XMLs/CDRs antiguos de la base de datos
//    Se ejecuta diariamente a las 3:00 AM
Schedule::command('sunat:limpiar-xml-viejos')->daily()->at('03:00');
