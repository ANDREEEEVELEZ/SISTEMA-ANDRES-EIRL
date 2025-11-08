<?php

namespace App\Console\Commands;

use App\Services\SunatService;
use Illuminate\Console\Command;

class EnviarResumenDiario extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sunat:enviar-resumen-diario {fecha?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Envía el Resumen Diario de boletas a SUNAT para una fecha específica (por defecto: ayer)';

    /**
     * Execute the console command.
     */
    public function handle(SunatService $sunatService): int
    {
        // Obtener fecha (si no se proporciona, usar el día anterior)
        $fechaStr = $this->argument('fecha');

        if ($fechaStr) {
            try {
                $fecha = new \DateTime($fechaStr);
            } catch (\Exception $e) {
                $this->error("Formato de fecha inválido. Use: YYYY-MM-DD");
                return Command::FAILURE;
            }
        } else {
            // Por defecto: día anterior
            $fecha = new \DateTime('yesterday');
        }

        $this->info("📅 Procesando Resumen Diario para: {$fecha->format('Y-m-d')}");
        $this->newLine();

        // Enviar resumen
        $resultado = $sunatService->enviarResumenDiario($fecha);

        if ($resultado['success']) {
            $this->info(" {$resultado['message']}");
            $this->newLine();
            $this->table(
                ['Campo', 'Valor'],
                [
                    ['Ticket SUNAT', $resultado['ticket']],
                    ['XML Guardado', $resultado['xml_path']],
                ]
            );
            $this->newLine();
            $this->warn("⏳ El ticket debe consultarse más tarde con:");
            $this->line("   php artisan sunat:consultar-ticket {$resultado['ticket']}");

            return Command::SUCCESS;
        } else {
            $this->error("{$resultado['message']}");

            if ($resultado['xml_path']) {
                $this->info("   XML guardado en: {$resultado['xml_path']}");
            }

            return Command::FAILURE;
        }
    }
}
