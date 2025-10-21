<?php

namespace App\Filament\Resources\Clientes\Pages;

use App\Filament\Resources\Clientes\ClienteResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditCliente extends EditRecord
{
    protected static string $resource = ClienteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('imprimirCliente')
                ->label('Imprimir información de cliente')
                ->icon('heroicon-o-printer')
                ->color('primary')
                ->url(fn () => route('clientes.imprimir.pdf', ['id' => $this->record->id]))
                ->openUrlInNewTab(),

            Action::make('inactivar')
                ->label('Inactivar Cliente')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Inactivar Cliente')
                ->modalDescription('¿Está seguro de que desea inactivar este cliente? No podrá realizar nuevas ventas con este cliente.')
                ->modalSubmitActionLabel('Inactivar')
                ->visible(fn () => $this->record->estado === 'activo')
                ->action(function () {
                    $this->record->update(['estado' => 'inactivo']);

                    Notification::make()
                        ->title('Cliente inactivado exitosamente')
                        ->success()
                        ->send();

                    // Refrescar la página para actualizar la vista
                    $this->refreshFormData([
                        'estado',
                    ]);
                }),

            // Acción ACTIVAR - Solo aparece cuando el cliente está INACTIVO
            Action::make('activar')
                ->label('Activar Cliente')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Activar Cliente')
                ->modalDescription('¿Está seguro de que desea activar este cliente? Podrá realizar nuevamente ventas con este cliente.')
                ->modalSubmitActionLabel('Activar')
                ->visible(fn () => $this->record->estado === 'inactivo')
                ->action(function () {
                    $this->record->update(['estado' => 'activo']);

                    Notification::make()
                        ->title('Cliente activado exitosamente')
                        ->success()
                        ->send();

                    // Refrescar la página para actualizar la vista
                    $this->refreshFormData([
                        'estado',
                    ]);
                }),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
