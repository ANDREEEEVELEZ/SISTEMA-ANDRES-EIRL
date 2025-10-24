<?php

namespace App\Filament\Resources\Ventas\Pages;

use App\Filament\Resources\Ventas\VentaResource;
use App\Filament\Resources\Cajas\CajaResource;
use App\Services\CajaService;
use App\Models\Producto;
use App\Models\MovimientoInventario;
use App\Models\Comprobante;
use App\Models\SerieComprobante;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CreateVenta extends CreateRecord
{
    protected static string $resource = VentaResource::class;

    // Almacenar temporalmente los datos del comprobante
    protected array $datosComprobante = [];

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

        // Validar stock disponible antes de crear la venta
        $data = $this->form->getState();

        // Validar que hay detalles de venta
        if (empty($data['detalleVentas'])) {
            Notification::make()
                ->title('Error en la Venta')
                ->body('Debe agregar al menos un producto a la venta.')
                ->danger()
                ->persistent()
                ->send();

            $this->halt();
        }

        // Validar stock disponible para cada producto
        foreach ($data['detalleVentas'] as $index => $detalle) {
            $producto = Producto::find($detalle['producto_id']);

            if (!$producto) {
                Notification::make()
                    ->title(' Error de Producto')
                    ->body('Uno de los productos seleccionados no existe.')
                    ->danger()
                    ->persistent()
                    ->send();

                $this->halt();
            }

            // Validar que hay suficiente stock
            if ($producto->stock_total < $detalle['cantidad_venta']) {
                Notification::make()
                    ->title('Stock Insuficiente')
                    ->body("El producto '{$producto->nombre_producto}' solo tiene {$producto->stock_total} unidades disponibles en inventario. Usted intentó vender {$detalle['cantidad_venta']} unidades.")
                    ->danger()
                    ->persistent()
                    ->send();

                $this->halt();
            }
        }
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = Auth::id();
        $data['fecha_venta'] = $data['fecha_emision'] ?? now();

        // Guardar temporalmente los datos del comprobante para usarlos después
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
     * Descontar inventario, crear comprobante y registrar movimientos después de crear la venta
     */
    protected function afterCreate(): void
    {
        DB::transaction(function () {
            $venta = $this->record;
            $productosActualizados = [];
            $alertasStockBajo = [];

            // 1. CREAR EL COMPROBANTE ASOCIADO A LA VENTA
            if (!empty($this->datosComprobante['tipo'])) {
                // Buscar la serie del comprobante
                $serieComprobante = SerieComprobante::where('tipo', $this->datosComprobante['tipo'])->first();

                if ($serieComprobante) {
                    // Crear el comprobante
                    $comprobante = Comprobante::create([
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

                    // Actualizar el último número de la serie
                    $serieComprobante->update([
                        'ultimo_numero' => (int) $this->datosComprobante['numero']
                    ]);
                }
            }

            // 2. PROCESAR INVENTARIO - Descontar stock de cada producto
            foreach ($venta->detalleVentas as $detalle) {
                $producto = Producto::find($detalle->producto_id);

                if ($producto) {
                    // Descontar del stock
                    $stockAnterior = $producto->stock_total;
                    $nuevoStock = $stockAnterior - $detalle->cantidad_venta;

                    $producto->update([
                        'stock_total' => $nuevoStock
                    ]);

                    // Registrar movimiento de inventario (SALIDA)
                    MovimientoInventario::create([
                        'producto_id' => $producto->id,
                        'user_id' => Auth::id(),
                        'tipo' => 'salida',
                        'cantidad_movimiento' => $detalle->cantidad_venta,
                        'motivo_movimiento' => "Venta #{$venta->id} - Cliente: {$venta->cliente->nombre_razon}",
                        'fecha_movimiento' => now(),
                    ]);

                    $productosActualizados[] = "• {$producto->nombre_producto}: {$stockAnterior} → {$nuevoStock} unidades";

                    // Verificar si el stock está por debajo del mínimo
                    if ($nuevoStock <= $producto->stock_minimo) {
                        $alertasStockBajo[] = "• {$producto->nombre_producto}: {$nuevoStock} unidades (Mínimo: {$producto->stock_minimo})";
                    }
                }
            }

            // 3. NOTIFICACIONES
            $mensaje = "La venta #{$venta->id} se registró correctamente";
            if (isset($comprobante)) {
                $mensaje .= " con {$comprobante->tipo} {$comprobante->serie}-{$comprobante->correlativo}";
            }
            $mensaje .= ".\n\n Inventario actualizado:\n" . implode("\n", $productosActualizados);

            Notification::make()
                ->title(' Venta Registrada Exitosamente')
                ->body($mensaje)
                ->success()
                ->duration(8000)
                ->send();

            // Alertas de stock bajo (si aplica)
            if (!empty($alertasStockBajo)) {
                Notification::make()
                    ->title(' Alerta de Stock Bajo')
                    ->body("Los siguientes productos tienen stock bajo o insuficiente:\n\n" . implode("\n", $alertasStockBajo))
                    ->warning()
                    ->persistent()
                    ->send();
            }
        });
    }
}
