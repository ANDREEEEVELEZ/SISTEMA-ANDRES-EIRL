<?php

namespace App\Filament\Resources\Ventas\Pages;

use App\Filament\Resources\Ventas\VentaResource;
use App\Filament\Resources\Cajas\CajaResource;
use App\Services\CajaService;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class CreateVenta extends CreateRecord
{
    protected static string $resource = VentaResource::class;

    protected function getHeaderActions(): array
    {
        $actions = [];

        // Si hay una caja del día anterior abierta, mostrar acciones para gestionarla
        if (CajaService::tieneCajaAbiertaDiaAnterior()) {
            $cajaAnterior = CajaService::getCajaAbiertaDiaAnterior();

            // Acción para cerrar caja anterior y abrir nueva
            $actions[] = Action::make('cerrarYAbrirCaja')
                ->label('Cerrar y Abrir Nueva Caja')
                ->color('warning')
                ->form([
                    TextInput::make('saldo_final_anterior')
                        ->label('Saldo Final de la Caja Anterior')
                        ->required()
                        ->numeric()
                        ->prefix('S/.')
                        ->default($cajaAnterior ? $cajaAnterior->saldo_inicial : 0)
                        ->helperText('Ingrese el saldo final real de la caja anterior'),

                    Textarea::make('observacion_cierre')
                        ->label('Observación de Cierre')
                        ->placeholder('Motivo del cierre (opcional)')
                        ->maxLength(255)
                        ->rows(2),

                    TextInput::make('saldo_inicial_nueva')
                        ->label('Saldo Inicial para Nueva Caja')
                        ->required()
                        ->numeric()
                        ->default(0)
                        ->prefix('S/.')
                        ->helperText('Ingrese el saldo inicial para la nueva caja'),

                    Textarea::make('observacion_apertura')
                        ->label('Observación de Apertura')
                        ->placeholder('Observaciones para la nueva caja (opcional)')
                        ->maxLength(255)
                        ->rows(2),
                ])
                ->action(function (array $data) {
                    // 1. Cerrar la caja del día anterior
                    $cajaAnterior = CajaService::getCajaAbiertaDiaAnterior();
                    if ($cajaAnterior) {
                        $cajaAnterior->update([
                            'estado' => 'cerrada',
                            'fecha_cierre' => now(),
                            'saldo_final' => $data['saldo_final_anterior'],
                            'observacion' => $cajaAnterior->observacion . ' | CIERRE: ' . ($data['observacion_cierre'] ?? 'Cerrada desde módulo de ventas'),
                        ]);
                    }

                    // 2. Crear nueva caja para hoy
                    $nuevaCaja = \App\Models\Caja::create([
                        'user_id' => Auth::id(),
                        'fecha_apertura' => now(),
                        'fecha_cierre' => null,
                        'saldo_inicial' => $data['saldo_inicial_nueva'],
                        'saldo_final' => null,
                        'estado' => 'abierta',
                        'observacion' => $data['observacion_apertura'] ?? 'Aperturada desde módulo de ventas tras cerrar caja anterior',
                    ]);

                    // 3. Mostrar notificación de éxito y recargar página
                    Notification::make()
                        ->title('Operación Completada')
                        ->success()
                        ->body("Caja anterior cerrada y nueva caja #{$nuevaCaja->id} aperturada correctamente. Ya puede registrar ventas.")
                        ->persistent()
                        ->send();

                    // Recargar la página para actualizar el formulario
                    redirect()->to($this->getResource()::getUrl('create'));
                })
                ->modalHeading('Cerrar Caja Anterior y Abrir Nueva')
                ->modalDescription("Esta acción cerrará la caja abierta desde el {$cajaAnterior->fecha_apertura->format('d/m/Y H:i')} y creará una nueva caja para hoy.")
                ->requiresConfirmation()
                ->modalSubmitActionLabel('Cerrar y Abrir')
                ->modalCancelActionLabel('Cancelar');

            // Acción solo para cerrar la caja anterior
            $actions[] = Action::make('cerrarCajaAnterior')
                ->label('Solo Cerrar Caja Anterior')
                ->color('danger')
                ->form([
                    TextInput::make('saldo_final')
                        ->label('Saldo Final')
                        ->required()
                        ->numeric()
                        ->prefix('S/.')
                        ->default($cajaAnterior ? $cajaAnterior->saldo_inicial : 0)
                        ->helperText('Ingrese el saldo final real de la caja'),

                    Textarea::make('observacion')
                        ->label('Observación de Cierre')
                        ->placeholder('Motivo del cierre (opcional)')
                        ->maxLength(255)
                        ->rows(2),
                ])
                ->action(function (array $data) {
                    $cajaAnterior = CajaService::getCajaAbiertaDiaAnterior();
                    if ($cajaAnterior) {
                        $cajaAnterior->update([
                            'estado' => 'cerrada',
                            'fecha_cierre' => now(),
                            'saldo_final' => $data['saldo_final'],
                            'observacion' => $cajaAnterior->observacion . ' | CIERRE: ' . ($data['observacion'] ?? 'Cerrada desde módulo de ventas'),
                        ]);

                        Notification::make()
                            ->title('Caja Cerrada')
                            ->success()
                            ->body('La caja del día anterior ha sido cerrada correctamente. Ahora puede aperturar una nueva caja.')
                            ->send();

                        // Recargar la página para actualizar el formulario
                        redirect()->to($this->getResource()::getUrl('create'));
                    }
                })
                ->modalHeading('Cerrar Caja del Día Anterior')
                ->modalDescription("Esta acción cerrará la caja abierta desde el {$cajaAnterior->fecha_apertura->format('d/m/Y H:i')}. Luego deberá aperturar una nueva caja para registrar ventas.")
                ->requiresConfirmation()
                ->modalSubmitActionLabel('Cerrar Caja')
                ->modalCancelActionLabel('Cancelar');
        }

        return $actions;
    }

    protected function beforeFill(): void
    {
        // Verificar si hay una caja del día anterior sin cerrar
        $cajaAnterior = CajaService::getCajaAbiertaDiaAnterior();

        if ($cajaAnterior) {
            Notification::make()
                ->title(' ADVERTENCIA: Caja del día anterior sin cerrar')
                ->warning()
                ->body("ATENCIÓN: Hay una caja abierta desde el {$cajaAnterior->fecha_apertura->format('d/m/Y H:i')} que debería cerrar. ")
                ->persistent()
                ->send();
            // NO redireccionar - permitir continuar
        }

    }

    protected function beforeCreate(): void
    {
        // Verificar caja del día anterior antes de crear la venta
        if (CajaService::tieneCajaAbiertaDiaAnterior()) {
            Notification::make()
                ->title('ERROR: Caja del día anterior sin cerrar')
                ->danger()
                ->body('Debe cerrar MANUALMENTE la caja del día anterior antes de registrar ventas. El sistema no permitirá continuar hasta que la cierre.')
                ->persistent()
                ->send();

            $this->halt();
        }

        // Verificar que hay caja abierta hoy
        if (!CajaService::tieneCajaAbiertaHoy()) {
            Notification::make()
                ->title('ERROR: No hay caja abierta')
                ->danger()
                ->body('Debe abrir una caja antes de registrar ventas.')
                ->persistent()
                ->send();

            $this->halt();
        }
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = Auth::id();

        return $data;
    }
}
