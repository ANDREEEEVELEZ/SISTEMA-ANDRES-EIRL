<?php

namespace App\Filament\Resources\Inventario\Pages;

use App\Filament\Resources\Inventario\InventarioResource;
use App\Models\Producto;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\DB;

class EditInventario extends EditRecord
{
    protected static string $resource = InventarioResource::class;

    // Guardar los datos originales antes de editar
    public ?array $datosOriginales = null;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Guardar los datos originales del movimiento para poder revertirlos
        $this->datosOriginales = [
            'producto_id' => $data['producto_id'],
            'tipo' => $data['tipo'],
            'cantidad_movimiento' => $data['cantidad_movimiento'],
            'metodo_ajuste' => $data['metodo_ajuste'] ?? null,
        ];

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            // No se permiten acciones de eliminación por políticas de seguridad
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    /**
     * Después de guardar la edición, ajustar el stock
     */
    protected function afterSave(): void
    {
        DB::transaction(function () {
            $movimiento = $this->record;
            $producto = Producto::findOrFail($movimiento->producto_id);

            // Si cambió el producto, necesitamos revertir en el producto anterior
            if ($this->datosOriginales['producto_id'] !== $movimiento->producto_id) {
                $productoAnterior = Producto::findOrFail($this->datosOriginales['producto_id']);
                $this->revertirMovimientoEnProducto($productoAnterior, $this->datosOriginales);
            } else {
                // Si es el mismo producto, revertir el movimiento original
                $this->revertirMovimientoEnProducto($producto, $this->datosOriginales);
            }

            // Aplicar el nuevo movimiento
            $stockAnterior = $producto->stock_total;
            $this->aplicarMovimiento($producto, $movimiento);

            // Mostrar notificación de éxito
            $tipoTexto = match($movimiento->tipo) {
                'entrada' => 'Entrada',
                'salida' => 'Salida',
                'ajuste' => 'Ajuste',
                default => 'Movimiento'
            };

            Notification::make()
                ->success()
                ->title("{$tipoTexto} actualizada correctamente")
                ->body("Stock actualizado de '{$producto->nombre_producto}': {$stockAnterior} → {$producto->stock_total} {$producto->unidad_medida}")
                ->send();
        });
    }

    /**
     * Revertir un movimiento del stock
     */
    private function revertirMovimiento($movimiento): void
    {
        $producto = Producto::findOrFail($movimiento->producto_id);
        $this->revertirMovimientoEnProducto($producto, [
            'tipo' => $movimiento->tipo,
            'cantidad_movimiento' => $movimiento->cantidad_movimiento,
        ]);
    }

    /**
     * Revertir movimiento en un producto específico
     */
    private function revertirMovimientoEnProducto(Producto $producto, array $datosMovimiento): void
    {
        switch ($datosMovimiento['tipo']) {
            case 'entrada':
                // Si fue entrada, restar para revertir
                $producto->stock_total -= $datosMovimiento['cantidad_movimiento'];
                break;

            case 'salida':
                // Si fue salida, sumar para revertir
                $producto->stock_total += $datosMovimiento['cantidad_movimiento'];
                break;

            case 'ajuste':
                // Para ajustes relativos, invertir la operación
                if (isset($datosMovimiento['metodo_ajuste']) && $datosMovimiento['metodo_ajuste'] === 'relativo') {
                    $producto->stock_total -= $datosMovimiento['cantidad_movimiento'];
                }
                // Para ajustes absolutos, no podemos revertir automáticamente
                // porque no sabemos cuál era el valor anterior
                break;
        }

        $producto->save();
    }

    /**
     * Aplicar un movimiento al stock
     */
    private function aplicarMovimiento(Producto $producto, $movimiento): void
    {
        switch ($movimiento->tipo) {
            case 'entrada':
                $producto->stock_total += $movimiento->cantidad_movimiento;
                break;

            case 'salida':
                if ($producto->stock_total < $movimiento->cantidad_movimiento) {
                    Notification::make()
                        ->danger()
                        ->title('Stock insuficiente')
                        ->body("El producto '{$producto->nombre_producto}' solo tiene {$producto->stock_total} unidades disponibles.")
                        ->persistent()
                        ->send();
                    
                    throw new \Exception('Stock insuficiente');
                }
                $producto->stock_total -= $movimiento->cantidad_movimiento;
                break;

            case 'ajuste':
                // Verificar el método de ajuste
                if ($movimiento->metodo_ajuste === 'relativo') {
                    // Ajuste relativo: sumar o restar la cantidad del stock actual
                    $nuevoStock = $producto->stock_total + $movimiento->cantidad_movimiento;
                    
                    if ($nuevoStock < 0) {
                        Notification::make()
                            ->danger()
                            ->title('Stock insuficiente')
                            ->body("El ajuste de {$movimiento->cantidad_movimiento} unidades dejaría el stock en {$nuevoStock}. El stock no puede ser negativo.")
                            ->persistent()
                            ->send();
                        
                        throw new \Exception('Stock insuficiente');
                    }
                    
                    $producto->stock_total = $nuevoStock;
                } else {
                    // Ajuste absoluto: reemplazar el stock con la cantidad
                    $producto->stock_total = $movimiento->cantidad_movimiento;
                }
                break;
        }

        $producto->save();
    }
}