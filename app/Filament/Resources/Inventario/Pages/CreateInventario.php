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
                    // Verificar el método de ajuste
                    if ($movimiento->metodo_ajuste === 'relativo') {
                        // Ajuste relativo: sumar o restar la cantidad del stock actual
                        $nuevoStock = $producto->stock_total + $movimiento->cantidad_movimiento;
                        
                        // Validar que no quede negativo
                        if ($nuevoStock < 0) {
                            Notification::make()
                                ->danger()
                                ->title('Stock insuficiente')
                                ->body("El ajuste de {$movimiento->cantidad_movimiento} unidades dejaría el stock en {$nuevoStock}. El stock no puede ser negativo.")
                                ->persistent()
                                ->send();
                            
                            $movimiento->delete();
                            return;
                        }
                        
                        $producto->stock_total = $nuevoStock;
                    } else {
                        // Ajuste absoluto: reemplazar el stock con la cantidad del ajuste
                        $producto->stock_total = $movimiento->cantidad_movimiento;
                    }
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