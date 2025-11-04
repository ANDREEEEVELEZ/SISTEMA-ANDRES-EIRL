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

    // Deshabilitar el botón de guardar - solo vista de lectura
    protected function getFormActions(): array
    {
        return [
            // No hay acciones de formulario (sin botón guardar)
        ];
    }

    // Prevenir cualquier guardado accidental
    protected function mutateFormDataBeforeSave(array $data): array
    {
        // No permitir guardado - redirigir
        Notification::make()
            ->title('Edición no permitida')
            ->warning()
            ->body('Los registros de caja no se pueden editar. Use los widgets de apertura/cierre para gestionar cajas.')
            ->send();

        $this->halt();

        return $data;
    }

    protected function getHeaderActions(): array
    {
        // No mostrar acciones de header en la página de edición.
        // El cierre de caja se gestiona desde los widgets de apertura/cierre.
        return [];
    }
}
