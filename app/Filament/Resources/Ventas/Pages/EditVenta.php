<?php

namespace App\Filament\Resources\Ventas\Pages;

use App\Filament\Resources\Ventas\VentaResource;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;

class EditVenta extends EditRecord
{
    protected static string $resource = VentaResource::class;

    // Método para montar la página y deshabilitar el formulario
    public function mount(int | string $record): void
    {
        parent::mount($record);

        // Deshabilitar todos los campos del formulario
        $this->form->disabled();
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        if ($this->record) {
            $comprobante = $this->record->comprobantes()->first();
            if ($comprobante) {
                $data['tipo_comprobante'] = $comprobante->tipo;
                $data['serie'] = $comprobante->serie;
                $data['numero'] = str_pad($comprobante->correlativo, 6, '0', STR_PAD_LEFT);
                $data['fecha_emision'] = $comprobante->fecha_emision;
            }
        }
        return $data;
    }

    protected function getHeaderActions(): array
    {
        $actions = [
            \Filament\Actions\Action::make('imprimir')
                ->label('Imprimir')
                ->icon('heroicon-o-printer')
                ->color('primary')
                ->action(function () {
                    Notification::make()
                        ->title('Función de impresión')
                        ->body('Aquí se implementará la impresión del comprobante')
                        ->info()
                        ->send();
                }),
        ];

        // Agregar botón anular solo si la venta está emitida
        if ($this->record->estado_venta === 'emitida') {
            $comprobante = $this->record->comprobantes()->first();
            $tipoComprobante = $comprobante ? $comprobante->tipo : 'ticket';

            if ($tipoComprobante === 'ticket') {
                // Para tickets: anulación directa desde aquí
                $actions[] = \Filament\Actions\Action::make('anular_ticket')
                    ->label('Anular Ticket')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Anular Ticket')
                    ->modalDescription('¿Está seguro de que desea anular este ticket? Esta acción no se puede deshacer.')
                    ->form([
                        \Filament\Forms\Components\Textarea::make('observacion')
                            ->label('Motivo de anulación')
                            ->required()
                            ->maxLength(500)
                            ->placeholder('Ingrese el motivo de la anulación del ticket'),
                    ])
                    ->action(function (array $data) {
                        $this->record->update([
                            'estado_venta' => 'anulada',
                            'observacion' => $data['observacion']
                        ]);

                        Notification::make()
                            ->title('Ticket anulado')
                            ->body("El ticket ha sido anulado exitosamente.")
                            ->success()
                            ->send();

                        return redirect()->to(VentaResource::getUrl('index'));
                    });
            }
        }

        return $actions;
    }

    protected function getFormActions(): array
    {
        return [];
    }

    protected function hasUnsavedDataChangesAlert(): bool
    {
        return false;
    }
}
