<?php

namespace App\Console\Commands;

use App\Services\SunatService;
use Illuminate\Console\Command;

class ConsultarTicketSunat extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sunat:consultar-ticket {ticket}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Consulta el estado de un ticket de Resumen Diario en SUNAT';

    /**
     * Execute the console command.
     */
    public function handle(SunatService $sunatService): int
    {
        $ticket = $this->argument('ticket');

        $this->info("🔍 Consultando estado del ticket: {$ticket}");
        $this->newLine();

        $resultado = $sunatService->consultarTicketResumen($ticket);

        if ($resultado['success']) {
            $estado = $resultado['codigo'] === 0 ? ' ACEPTADO' : 'CON OBSERVACIONES';

            $this->info($estado);
            $this->newLine();
            $this->table(
                ['Campo', 'Valor'],
                [
                    ['Código SUNAT', $resultado['codigo']],
                    ['Mensaje', $resultado['mensaje']],
                    ['CDR Guardado', $resultado['cdr_path']],
                ]
            );

            if ($resultado['codigo'] === 0) {
                $this->info(" Resumen procesado correctamente por SUNAT");
            } else {
                $this->warn("Resumen aceptado pero con observaciones");
            }

            return Command::SUCCESS;
        } else {
            $this->error("{$resultado['mensaje']}");
            $this->newLine();
            $this->warn("El ticket puede tardar varios minutos en procesarse.");
            $this->line("   Intenta nuevamente en 5-10 minutos.");

            return Command::FAILURE;
        }
    }
}
