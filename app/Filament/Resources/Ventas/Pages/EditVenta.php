<?php

namespace App\Filament\Resources\Ventas\Pages;

use App\Filament\Resources\Ventas\VentaResource;
use App\Models\Producto;
use App\Models\MovimientoInventario;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class EditVenta extends EditRecord
{
    protected static string $resource = VentaResource::class;

    // Guardamos el estado original de la venta antes de editarla
    protected array $detallesOriginales = [];

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Guardar los detalles originales para compararlos después
        if ($this->record) {
            $this->detallesOriginales = $this->record->detalleVentas()
                ->get()
                ->map(function ($detalle) {
                    return [
                        'producto_id' => $detalle->producto_id,
                        'cantidad_venta' => $detalle->cantidad_venta,
                    ];
                })
                ->toArray();
        }

        return $data;
    }

    /**
     * Actualizar inventario después de editar la venta
     */
    protected function afterSave(): void
    {
        DB::transaction(function () {
            $venta = $this->record;
            
            // 1. Devolver el stock de los productos originales
            foreach ($this->detallesOriginales as $detalleOriginal) {
                $producto = Producto::find($detalleOriginal['producto_id']);
                
                if ($producto) {
                    // Devolver el stock (sumar lo que se había restado)
                    $producto->increment('stock_total', $detalleOriginal['cantidad_venta']);
                    
                    // Registrar movimiento de devolución
                    MovimientoInventario::create([
                        'producto_id' => $producto->id,
                        'user_id' => Auth::id(),
                        'tipo' => 'entrada',
                        'cantidad_movimiento' => $detalleOriginal['cantidad_venta'],
                        'motivo_movimiento' => "Devolución por edición de Venta #{$venta->id}",
                        'fecha_movimiento' => now(),
                    ]);
                }
            }

            // 2. Descontar el stock de los productos nuevos/editados
            $productosActualizados = [];
            foreach ($venta->detalleVentas as $detalleNuevo) {
                $producto = Producto::find($detalleNuevo->producto_id);
                
                if ($producto) {
                    // Verificar que hay suficiente stock
                    if ($producto->stock_total < $detalleNuevo->cantidad_venta) {
                        // Revertir las devoluciones anteriores antes de mostrar error
                        foreach ($this->detallesOriginales as $detalleOriginal) {
                            $prod = Producto::find($detalleOriginal['producto_id']);
                            if ($prod) {
                                $prod->decrement('stock_total', $detalleOriginal['cantidad_venta']);
                            }
                        }

                        Notification::make()
                            ->title('⚠️ Stock Insuficiente')
                            ->body("El producto '{$producto->nombre_producto}' solo tiene {$producto->stock_total} unidades disponibles. No se puede completar la edición.")
                            ->danger()
                            ->persistent()
                            ->send();
                        
                        $this->halt();
                    }

                    // Descontar del stock
                    $stockAnterior = $producto->stock_total;
                    $producto->decrement('stock_total', $detalleNuevo->cantidad_venta);
                    $nuevoStock = $producto->fresh()->stock_total;
                    
                    // Registrar movimiento de salida
                    MovimientoInventario::create([
                        'producto_id' => $producto->id,
                        'user_id' => Auth::id(),
                        'tipo' => 'salida',
                        'cantidad_movimiento' => $detalleNuevo->cantidad_venta,
                        'motivo_movimiento' => "Venta editada #{$venta->id} - Cliente: {$venta->cliente->nombre_razon}",
                        'fecha_movimiento' => now(),
                    ]);

                    $productosActualizados[] = "• {$producto->nombre_producto}: {$nuevoStock} unidades";
                }
            }

            // Notificación de éxito
            Notification::make()
                ->title('✅ Venta Actualizada')
                ->body("La venta #{$venta->id} se actualizó correctamente.\n\n📦 Inventario actualizado:\n" . implode("\n", $productosActualizados))
                ->success()
                ->duration(6000)
                ->send();
        });
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->before(function () {
                    // Devolver el stock antes de eliminar la venta
                    DB::transaction(function () {
                        $venta = $this->record;
                        
                        foreach ($venta->detalleVentas as $detalle) {
                            $producto = Producto::find($detalle->producto_id);
                            
                            if ($producto) {
                                // Devolver el stock
                                $producto->increment('stock_total', $detalle->cantidad_venta);
                                
                                // Registrar movimiento de entrada (devolución)
                                MovimientoInventario::create([
                                    'producto_id' => $producto->id,
                                    'user_id' => Auth::id(),
                                    'tipo' => 'entrada',
                                    'cantidad_movimiento' => $detalle->cantidad_venta,
                                    'motivo_movimiento' => "Devolución por eliminación de Venta #{$venta->id}",
                                    'fecha_movimiento' => now(),
                                ]);
                            }
                        }

                        Notification::make()
                            ->title('📦 Inventario Restaurado')
                            ->body('El stock de los productos ha sido devuelto al inventario.')
                            ->success()
                            ->send();
                    });
                }),
        ];
    }
}
