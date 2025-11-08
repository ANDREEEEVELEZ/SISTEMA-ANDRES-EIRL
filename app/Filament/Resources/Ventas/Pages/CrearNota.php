<?php

namespace App\Filament\Resources\Ventas\Pages;

use App\Filament\Resources\Ventas\VentaResource;
use App\Services\SunatService;
use Filament\Resources\Pages\Page;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use App\Models\Venta;
use App\Models\Comprobante;
use App\Models\SerieComprobante;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CrearNota extends Page
{
    protected static string $resource = VentaResource::class;
    protected string $view = 'filament.resources.ventas.pages.crear-nota';

    public ?array $data = [];
    public Venta $venta;

    public function mount(Venta $record): void
    {
        $this->venta = $record;
        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('tipo_nota')
                    ->label('Nota')
                    ->options([
                        'nota_credito' => 'Nota de Crédito',
                       // 'nota_debito' => 'Nota de Débito',
                    ])
                     ->default('Nota de Crédito')
                    ->required()
                    ->live(),

                TextInput::make('serie')
                    ->label('Serie')
                    ->required()
                    ->default(function (callable $get) {
                        $tipo = $get('tipo_nota');
                        if ($tipo) {
                            $serie = SerieComprobante::where('tipo', $tipo)->first();
                            return $serie ? $serie->serie : '';
                        }
                        return '';
                    })
                    ->disabled(),

                TextInput::make('numero')
                    ->label('Número')
                    ->required()
                    ->default(function (callable $get) {
                        $tipo = $get('tipo_nota');
                        if ($tipo) {
                            $serie = SerieComprobante::where('tipo', $tipo)->first();
                            return $serie ? str_pad($serie->correlativo_actual + 1, 6, '0', STR_PAD_LEFT) : '';
                        }
                        return '';
                    })
                    ->disabled(),

                Textarea::make('motivo')
                    ->label('Motivo de la nota')
                    ->required()
                    ->maxLength(500)
                    ->placeholder('Ingrese el motivo detallado para emitir esta nota'),

                TextInput::make('monto')
                    ->label('Monto')
                    ->numeric()
                    ->required()
                    ->default($this->venta->total_venta)
                    ->prefix('S/ ')
                    ->helperText('Monto por el cual se emite la nota'),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('emitir_nota')
                ->label('Emitir Nota')
                ->icon('heroicon-o-document-text')
                ->color('success')
                ->action(function () {
                    $data = $this->form->getState();

                    DB::transaction(function () use ($data) {
                        // Crear el comprobante de la nota
                        $serie = SerieComprobante::where('tipo', $data['tipo_nota'])->first();

                        if ($serie) {
                            $comprobante = Comprobante::create([
                                'venta_id' => $this->venta->id,
                                'tipo' => $data['tipo_nota'],
                                'serie' => $data['serie'],
                                'correlativo' => $serie->correlativo_actual + 1,
                                'fecha_emision' => now(),
                                'sub_total' => $data['monto'] / 1.18, // Asumiendo IGV 18%
                                'igv' => $data['monto'] - ($data['monto'] / 1.18),
                                'total' => $data['monto'],
                                'motivo' => $data['motivo'],
                            ]);

                            // Actualizar correlativo
                            $serie->increment('correlativo_actual');

                            // 🚀 ENVÍO AUTOMÁTICO A SUNAT (Nota de Crédito)
                            try {
                                $sunatService = new SunatService();
                                $resultado = $sunatService->enviarNotaCredito($comprobante);

                                if ($resultado['success']) {
                                    Log::info("Nota de crédito #{$comprobante->id} enviada a SUNAT exitosamente", [
                                        'codigo' => $resultado['codigo'] ?? null,
                                        'mensaje' => $resultado['message'] ?? null,
                                    ]);

                                    Notification::make()
                                        ->title(' Nota enviada a SUNAT')
                                        ->body($resultado['message'] ?? 'Nota aceptada por SUNAT')
                                        ->success()
                                        ->send();
                                } else {
                                    Log::warning(" Error al enviar nota #{$comprobante->id} a SUNAT", [
                                        'error' => $resultado['message'] ?? 'Error desconocido',
                                    ]);

                                    Notification::make()
                                        ->title('Advertencia SUNAT')
                                        ->body($resultado['message'] ?? 'No se pudo enviar a SUNAT. Puede reintentar.')
                                        ->warning()
                                        ->send();
                                }
                            } catch (\Exception $e) {
                                Log::error("Excepción al enviar nota #{$comprobante->id} a SUNAT", [
                                    'error' => $e->getMessage(),
                                    'trace' => $e->getTraceAsString(),
                                ]);

                                Notification::make()
                                    ->title('No se pudo enviar a SUNAT')
                                    ->body('La nota se registró correctamente, pero hubo un error con SUNAT.')
                                    ->warning()
                                    ->send();
                            }

                            // Actualizar estado de la venta original
                            $this->venta->update(['estado_venta' => 'anulada']);
                        }
                    });

                    Notification::make()
                        ->title('Nota emitida exitosamente')
                        ->body("Se ha emitido la {$data['tipo_nota']} correctamente.")
                        ->success()
                        ->send();

                    return redirect()->to(VentaResource::getUrl('index'));
                })
                ->requiresConfirmation()
                ->modalHeading('Confirmar emisión')
                ->modalDescription('¿Está seguro de que desea emitir esta nota? Esta acción anulará la venta original.'),

            Action::make('cancelar')
                ->label('Cancelar')
                ->color('gray')
                ->url(VentaResource::getUrl('index')),
        ];
    }

    public function getTitle(): string
    {
        $comprobante = $this->venta->comprobantes()->first();
        $tipoOriginal = $comprobante ? ucfirst($comprobante->tipo) : 'Comprobante';

        return "Anular {$tipoOriginal} - Venta #{$this->venta->id}";
    }
}
