<?php

namespace App\Filament\Resources\Ventas\Pages;

use App\Filament\Resources\Ventas\VentaResource;
use App\Models\Producto;
use App\Models\MovimientoInventario;
use App\Models\Comprobante;
use App\Models\SerieComprobante;
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
    
    // Guardar datos del comprobante para actualizar después
    protected array $datosComprobante = [];

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

            // CARGAR DATOS DEL COMPROBANTE si existe
            $comprobante = $this->record->comprobantes()->first();
            if ($comprobante) {
                $data['tipo_comprobante'] = $comprobante->tipo;
                $data['serie'] = $comprobante->serie;
                $data['numero'] = str_pad($comprobante->correlativo, 6, '0', STR_PAD_LEFT);
                $data['fecha_emision'] = $comprobante->fecha_emision;
            }
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Guardar datos del comprobante para actualizarlos después
        $this->datosComprobante = [
            'tipo' => $data['tipo_comprobante'] ?? null,
            'serie' => $data['serie'] ?? null,
            'numero' => $data['numero'] ?? null,
            'fecha_emision' => $data['fecha_emision'] ?? now(),
        ];

        // Eliminar campos que no pertenecen a la tabla ventas
        unset($data['tipo_comprobante']);
        unset($data['serie']);
        unset($data['numero']);
        unset($data['fecha_emision']);

        return $data;
    }

    /**
     * Actualizar inventario y comprobante después de editar la venta
     */
    protected function afterSave(): void
    {
        DB::transaction(function () {
            $venta = $this->record;
            
            // 1. ACTUALIZAR O CREAR COMPROBANTE
            if (!empty($this->datosComprobante['tipo'])) {
                $comprobante = $venta->comprobantes()->first();
                
                if ($comprobante) {
                    // Actualizar comprobante existente
                    $comprobante->update([
                        'tipo' => $this->datosComprobante['tipo'],
                        'serie' => $this->datosComprobante['serie'],
                        'correlativo' => (int) $this->datosComprobante['numero'],
                        'fecha_emision' => $this->datosComprobante['fecha_emision'],
                        'sub_total' => $venta->subtotal_venta,
                        'igv' => $venta->igv,
                        'total' => $venta->total_venta,
                    ]);
                } else {
                    // Crear comprobante si no existe
                    $serieComprobante = SerieComprobante::where('tipo', $this->datosComprobante['tipo'])->first();
                    
                    if ($serieComprobante) {
                        Comprobante::create([
                            'venta_id' => $venta->id,
                            'serie_comprobante_id' => $serieComprobante->id,
                            'tipo' => $this->datosComprobante['tipo'],
                            'serie' => $this->datosComprobante['serie'],
                            'correlativo' => (int) $this->datosComprobante['numero'],
                            'fecha_emision' => $this->datosComprobante['fecha_emision'],
                            'sub_total' => $venta->subtotal_venta,
                            'igv' => $venta->igv,
                            'total' => $venta->total_venta,
                            'estado' => 'emitido',
                        ]);
                    }
                }
            }
            
            // 2. PROCESAR INVENTARIO - Devolver el stock de los productos originales
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

            // 3. DESCONTAR EL STOCK DE LOS PRODUCTOS NUEVOS/EDITADOS
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

            // 4. NOTIFICACIÓN DE ÉXITO
            Notification::make()
                ->title('✅ Venta y Comprobante Actualizados')
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
