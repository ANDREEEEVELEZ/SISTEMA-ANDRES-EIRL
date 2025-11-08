<?php

namespace App\Console\Commands;

use App\Models\Comprobante;
use App\Services\SunatService;
use Illuminate\Console\Command;

class PruebaEnvioSunat extends Command
{
    /**
     * Nombre y firma del comando.
     */
    protected $signature = 'sunat:probar {comprobante_id? : ID del comprobante a enviar}';

    /**
     * Descripción del comando.
     */
    protected $description = 'Prueba el envío de un comprobante a SUNAT (BETA)';

    /**
     * Ejecutar el comando.
     */
    public function handle(SunatService $sunatService): int
    {
        $this->info('═══════════════════════════════════════════════════════');
        $this->info('  PRUEBA DE ENVÍO A SUNAT (Modo: ' . config('sunat.mode') . ')');
        $this->info('═══════════════════════════════════════════════════════');
        $this->newLine();

        // Obtener ID del comprobante
        $comprobanteId = $this->argument('comprobante_id');

        if (!$comprobanteId) {
            // Listar comprobantes disponibles
            $this->warn('No se especificó ID de comprobante.');
            $this->info('Comprobantes disponibles (últimos 10):');
            $this->newLine();

            $comprobantes = Comprobante::with('venta.cliente')
                ->latest()
                ->take(10)
                ->get();

            $table = [];
            foreach ($comprobantes as $c) {
                $cliente = $c->venta->cliente ? $c->venta->cliente->nombre_razon : 'CLIENTE GENÉRICO';
                $table[] = [
                    $c->id,
                    $c->tipo,
                    $c->serie . '-' . $c->correlativo,
                    'S/ ' . number_format($c->total, 2),
                    $c->estado,
                    $cliente,
                    $c->codigo_sunat ?? '---',
                ];
            }

            $this->table(
                ['ID', 'Tipo', 'Número', 'Total', 'Estado', 'Cliente', 'Cód. SUNAT'],
                $table
            );

            $this->newLine();
            $this->comment('Usa: php artisan sunat:probar {ID}');

            return Command::SUCCESS;
        }

        // Buscar comprobante
        $comprobante = Comprobante::with('venta.cliente', 'venta.detalles.producto')->find($comprobanteId);

        if (!$comprobante) {
            $this->error("No se encontró el comprobante con ID: {$comprobanteId}");
            return Command::FAILURE;
        }

        // Mostrar datos del comprobante
        $this->info("Comprobante: {$comprobante->tipo} {$comprobante->serie}-{$comprobante->correlativo}");
        $this->info("Total: S/ {$comprobante->total}");
        $this->info("Cliente: " . ($comprobante->venta->cliente->nombre_razon ?? 'GENÉRICO'));
        $this->info(" Fecha: {$comprobante->fecha_emision->format('d/m/Y H:i')}");
        $this->info("Estado actual: {$comprobante->estado}");
        $this->newLine();

        // Confirmar envío
        if (!$this->confirm('¿Enviar este comprobante a SUNAT ' . config('sunat.mode') . '?', true)) {
            $this->warn('Operación cancelada.');
            return Command::SUCCESS;
        }

        $this->newLine();
        $this->info('⏳ Enviando a SUNAT...');
        $this->newLine();

        // Enviar según tipo
        try {
            if (in_array($comprobante->tipo, ['factura', 'boleta'])) {
                $resultado = $sunatService->enviarFacturaBoleta($comprobante);
            } elseif ($comprobante->tipo === 'nota de credito') {
                $resultado = $sunatService->enviarNotaCredito($comprobante);
            } else {
                $this->error("❌ Tipo de comprobante no soportado: {$comprobante->tipo}");
                return Command::FAILURE;
            }

            // Mostrar resultado
            if ($resultado['success']) {
                $this->info(' ÉXITO');
                $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
                $this->info("Código SUNAT: {$resultado['codigo']}");
                $this->info("Mensaje: {$resultado['message']}");
                $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

                // Refrescar comprobante para ver cambios
                $comprobante->refresh();
                $this->newLine();
                $this->info(" Estado actualizado: {$comprobante->estado}");
                $this->info(" Hash SUNAT: " . ($comprobante->hash_sunat ?? 'N/A'));

                if ($comprobante->ruta_xml) {
                    $this->info(" XML guardado en: {$comprobante->ruta_xml}");
                }
                if ($comprobante->ruta_cdr) {
                    $this->info(" CDR guardado en: {$comprobante->ruta_cdr}");
                }
            } else {
                $this->error(' ERROR');
                $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
                $this->error("Código: " . ($resultado['codigo'] ?? 'N/A'));
                $this->error("Mensaje: {$resultado['message']}");
                $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

                // Mostrar error guardado
                $comprobante->refresh();
                if ($comprobante->error_envio) {
                    $this->newLine();
                    $this->warn("Error guardado en BD:");
                    $this->line($comprobante->error_envio);
                }
            }
        } catch (\Exception $e) {
            $this->error('EXCEPCIÓN');
            $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
            $this->error($e->getMessage());
            $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

            if ($this->option('verbose')) {
                $this->newLine();
                $this->line($e->getTraceAsString());
            }

            return Command::FAILURE;
        }

        $this->newLine();
        return Command::SUCCESS;
    }
}
