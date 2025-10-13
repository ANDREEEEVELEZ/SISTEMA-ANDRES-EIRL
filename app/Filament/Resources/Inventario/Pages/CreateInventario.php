<?php

namespace App\Filament\Resources\Inventario\Pages;

use App\Filament\Resources\Inventario\InventarioResource;
use App\Models\Producto;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;

class CreateInventario extends CreateRecord
{
    protected static string $resource = InventarioResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    /**
     * Después de crear el movimiento de inventario, actualizar el stock del producto
     */
    protected function afterCreate(): void
    {
        DB::transaction(function () {
            $movimiento = $this->record;
            $producto = Producto::findOrFail($movimiento->producto_id);
            $stockAnterior = $producto->stock_total;

            // Aplicar el movimiento según el tipo
            switch ($movimiento->tipo) {
                case 'entrada':
                    // Sumar al stock
                    $producto->stock_total += $movimiento->cantidad_movimiento;
                    break;

                case 'salida':
                    // Restar del stock (validar que no quede negativo)
                    if ($producto->stock_total < $movimiento->cantidad_movimiento) {
                        Notification::make()
                            ->danger()
                            ->title('Stock insuficiente')
                            ->body("El producto '{$producto->nombre_producto}' solo tiene {$producto->stock_total} unidades disponibles. No se puede realizar una salida de {$movimiento->cantidad_movimiento} unidades.")
                            ->persistent()
                            ->send();
                        
                        // Revertir la creación del movimiento
                        $movimiento->delete();
                        return;
                    }
                    $producto->stock_total -= $movimiento->cantidad_movimiento;
                    break;

                case 'ajuste':
                    // Reemplazar el stock con la cantidad del ajuste
                    $producto->stock_total = $movimiento->cantidad_movimiento;
                    break;
            }

            // Guardar el producto actualizado
            $producto->save();

            // Mostrar notificación de éxito
            $tipoTexto = match($movimiento->tipo) {
                'entrada' => 'Entrada',
                'salida' => 'Salida',
                'ajuste' => 'Ajuste',
                default => 'Movimiento'
            };

            Notification::make()
                ->success()
                ->title("{$tipoTexto} registrada correctamente")
                ->body("Stock actualizado de '{$producto->nombre_producto}': {$stockAnterior} → {$producto->stock_total} {$producto->unidad_medida}")
                ->send();
        });
    }
}