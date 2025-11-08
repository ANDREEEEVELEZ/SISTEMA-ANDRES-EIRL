<?php

namespace App\Console\Commands;

use App\Models\Comprobante;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class LimpiarXmlViejos extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sunat:limpiar-xml-viejos {--dias=30 : Días de antigüedad para limpiar}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Limpia XMLs y CDRs antiguos de la base de datos (mantiene archivos en storage)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dias = (int) $this->option('dias');

        $this->info("🧹 Limpiando XMLs/CDRs con más de {$dias} días...");


        $comprobantes = Comprobante::where('created_at', '<', now()->subDays($dias))
            ->where(function ($query) {
                $query->whereNotNull('xml_firmado')
                    ->orWhereNotNull('cdr_respuesta');
            })
            ->get();

        $total = $comprobantes->count();

        if ($total === 0) {
            $this->info('No hay comprobantes antiguos que limpiar');
            return 0;
        }

        $this->info("Encontrados {$total} comprobantes para limpiar...");

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $limpiados = 0;
        foreach ($comprobantes as $comprobante) {
            // Verificar que los archivos existen en storage antes de limpiar BD
            $xmlExiste = $comprobante->ruta_xml && Storage::disk('sunat')->exists($comprobante->ruta_xml);

            // Solo limpiar xml_firmado si el archivo está en storage
            // (cdr_respuesta no se usa, ya que CDR es binario)
            if ($xmlExiste) {
                $comprobante->update([
                    'xml_firmado' => null,
                ]);
                $limpiados++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        $this->info("Limpiados {$limpiados} registros (archivos permanecen en storage)");
        $this->comment("Espacio liberado: ~" . round(($limpiados * 10) / 1024, 2) . " MB");

        return 0;
    }
}
