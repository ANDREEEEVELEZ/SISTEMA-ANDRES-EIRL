<?php

namespace App\Filament\Resources\Cajas\Pages;

use App\Filament\Resources\Cajas\CajaResource;
use App\Models\Caja;
use App\Services\CajaService;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;

class CreateCaja extends CreateRecord
{
    protected static string $resource = CajaResource::class;

    protected function beforeFill(): void
    {
        // Verificar si hay una caja del día anterior sin cerrar
        $cajaAnterior = CajaService::getCajaAbiertaDiaAnterior();

        if ($cajaAnterior) {
            Notification::make()
                ->title('ADVERTENCIA CRÍTICA: Caja del día anterior sin cerrar')
                ->danger()
                ->body("Hay una caja abierta desde el {$cajaAnterior->fecha_apertura->format('d/m/Y H:i')}. DEBE cerrarla MANUALMENTE antes de crear una nueva caja. El sistema NO la cerrará automáticamente.")
                ->persistent()
                ->send();

            // Redireccionar a editar la caja anterior
            $this->redirect(CajaResource::getUrl('edit', ['record' => $cajaAnterior]));
            return;
        }

        // Verificar si hay una caja del día actual
        if (CajaService::tieneCajaAbiertaHoy()) {
            $cajaHoy = Caja::where('estado', 'abierta')->first();
            Notification::make()
                ->title('Ya existe una caja abierta hoy')
                ->warning()
                ->body("Hay una caja abierta desde el {$cajaHoy->fecha_apertura->format('H:i')}. No puede crear otra caja el mismo día.")
                ->persistent()
                ->send();
        }
    }

    protected function beforeCreate(): void
    {
        // Verificar caja del día anterior
        if (CajaService::tieneCajaAbiertaDiaAnterior()) {
            Notification::make()
                ->title(' ERROR: Caja del día anterior sin cerrar')
                ->danger()
                ->body('Debe cerrar MANUALMENTE la caja del día anterior antes de crear una nueva. El sistema no permitirá continuar hasta que la cierre.')
                ->persistent()
                ->send();

            $this->halt();
        }

        // Verificar caja del día actual
        if (CajaService::tieneCajaAbiertaHoy()) {
            Notification::make()
                ->title('No se puede crear la caja')
                ->danger()
                ->body('Ya existe una caja abierta hoy. Solo se puede tener una caja por día.')
                ->persistent()
                ->send();

            $this->halt();
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
