<?php

namespace App\Filament\Resources\Productos\Pages;

use App\Filament\Resources\Productos\ProductoResource;
use App\Models\Producto;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListProductos extends ListRecords
{
    protected static string $resource = ProductoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    public function mount(): void
    {
        parent::mount();
        
        // Mostrar notificación si hay productos con stock bajo o agotados
        $productosAgotados = Producto::agotados()->where('estado', 'activo')->count();
        $productosStockBajo = Producto::stockBajo()->where('estado', 'activo')->count();
        
        if ($productosAgotados > 0) {
            Notification::make()
                ->danger()
                ->title('⚠️ Productos Agotados')
                ->body("Hay {$productosAgotados} producto(s) sin stock. Es necesario reponer urgentemente.")
                ->icon('heroicon-o-exclamation-circle')
                ->persistent()
                ->send();
        }
        
        if ($productosStockBajo > 0) {
            Notification::make()
                ->warning()
                ->title('⚠️ Stock Bajo')
                ->body("Hay {$productosStockBajo} producto(s) con stock por debajo del mínimo.")
                ->icon('heroicon-o-exclamation-triangle')
                ->persistent()
                ->send();
        }
    }
}
