<?php

namespace App\Filament\Resources\Cajas\Pages;

use App\Filament\Resources\Cajas\CajaResource;
use App\Services\CajaService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\DB;
use Filament\Notifications\Notification;
use Carbon\Carbon;

class EditCaja extends EditRecord
{
    protected static string $resource = CajaResource::class;

    protected function getHeaderActions(): array
    {
        if ($this->record->estado !== 'abierta') {
            return [];
        }

        // Verificar si es una caja del día anterior
        $fechaApertura = Carbon::parse($this->record->fecha_apertura);
        $hoy = Carbon::now();
        $esCajaDiaAnterior = !$fechaApertura->isSameDay($hoy);

        $actions = [];

        if ($esCajaDiaAnterior) {
            // Botón especial para caja del día anterior
            $actions[] = Action::make('cerrar_caja_anterior')
                ->label(' CERRAR CAJA DEL DÍA ANTERIOR')
                ->icon('heroicon-o-exclamation-triangle')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('ADVERTENCIA: Caja del día anterior sin cerrar')
                ->modalDescription("Esta caja fue abierta el {$fechaApertura->format('d/m/Y')} y no fue cerrada automáticamente. DEBE cerrarla MANUALMENTE para poder continuar con las operaciones del sistema. El sistema NO cierra cajas automáticamente. Una vez cerrada, podrá crear una nueva caja para hoy.")
                ->modalSubmitActionLabel('Sí, cerrar caja del día anterior')
                ->modalCancelActionLabel('Cancelar')
                ->action(function () {
                    try {
                        DB::transaction(function () {
                            // Calcular saldo final
                            $ingresos = $this->record->ventas()->sum('total');
                            $gastos = $this->record->movimientos_caja()->where('tipo', 'salida')->sum('monto');
                            $saldo_final = $this->record->saldo_inicial + $ingresos - $gastos;

                            // Cerrar la caja con observación especial
                            $this->record->update([
                                'estado' => 'cerrada',
                                'fecha_cierre' => Carbon::now(),
                                'saldo_final' => $saldo_final,
                                'observacion' => 'Caja cerrada tardíamente - No fue cerrada el día de apertura'
                            ]);
                        });

                        Notification::make()
                            ->title('Caja del día anterior cerrada')
                            ->success()
                            ->body("Saldo final: S/ " . number_format($this->record->saldo_final, 2) . ". Ahora puede crear una nueva caja para hoy.")
                            ->persistent()
                            ->send();

                        // Redireccionar a la lista de cajas
                        $this->redirect(CajaResource::getUrl('index'));

                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Error al cerrar la caja')
                            ->danger()
                            ->body('Ocurrió un error: ' . $e->getMessage())
                            ->send();
                    }
                });
        } else {
            // Botón normal para caja del día actual
            $actions[] = Action::make('cerrar_caja')
                ->label('Cerrar Caja')
                ->icon('heroicon-o-lock-closed')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('¿Cerrar esta caja?')
                ->modalDescription('Una vez cerrada, no se podrán realizar más movimientos en esta caja. Esta acción no se puede deshacer.')
                ->modalSubmitActionLabel('Sí, cerrar caja')
                ->modalCancelActionLabel('Cancelar')
                ->action(function () {
                    try {
                        DB::transaction(function () {
                            // Calcular saldo final
                            $ingresos = $this->record->ventas()->sum('total');
                            $gastos = $this->record->movimientos_caja()->where('tipo', 'salida')->sum('monto');
                            $saldo_final = $this->record->saldo_inicial + $ingresos - $gastos;

                            // Actualizar la caja
                            $this->record->update([
                                'estado' => 'cerrada',
                                'fecha_cierre' => Carbon::now(),
                                'saldo_final' => $saldo_final,
                            ]);
                        });

                        Notification::make()
                            ->title('Caja cerrada exitosamente')
                            ->success()
                            ->body("Saldo final: S/ " . number_format($this->record->saldo_final, 2))
                            ->send();

                        // Refrescar la página para mostrar los cambios
                        $this->refreshFormData([
                            'estado',
                            'fecha_cierre',
                            'saldo_final'
                        ]);

                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Error al cerrar la caja')
                            ->danger()
                            ->body('Ocurrió un error: ' . $e->getMessage())
                            ->send();
                    }
                });
        }

        return $actions;
    }
}
