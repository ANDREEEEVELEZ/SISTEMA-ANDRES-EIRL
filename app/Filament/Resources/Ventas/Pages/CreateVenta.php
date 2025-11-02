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

        // Ya no permitimos cerrar/abrir cajas desde Ventas
        // Todo se maneja desde el módulo de Caja con su flujo de arqueo

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
                ->body("Hay una caja abierta desde el {$cajaAnterior->fecha_apertura->format('d/m/Y H:i')}. Debe ir al módulo de Caja, generar el reporte de arqueo y cerrar la caja antes de continuar.")
                ->persistent()
                ->send();

            // Redirigir al módulo de Caja
            redirect()->to(CajaResource::getUrl('index'));
            return;
        }

        // Verificar si hay una caja abierta HOY
        if (!CajaService::tieneCajaAbiertaHoy()) {
            Notification::make()
                ->title('No hay caja abierta')
                ->warning()
                ->body('Debe abrir una caja antes de registrar ventas. Será redirigido al módulo de Caja.')
                ->persistent()
                ->send();

            // Redirigir al módulo de Caja
            redirect()->to(CajaResource::getUrl('index'));
            return;
        }
    }

    protected function beforeCreate(): void
    {
        // Verificar caja del día anterior antes de crear la venta
        if (CajaService::tieneCajaAbiertaDiaAnterior()) {
            Notification::make()
                ->title('ERROR: Caja del día anterior sin cerrar')
                ->danger()
                ->body('Debe cerrar la caja del día anterior desde el módulo de Caja (con reporte de arqueo) antes de registrar ventas.')
                ->persistent()
                ->send();

            $this->halt();
        }

        // Verificar que hay caja abierta hoy
        if (!CajaService::tieneCajaAbiertaHoy()) {
            Notification::make()
                ->title(' ERROR: No hay caja abierta')
                ->danger()
                ->body('Debe abrir una caja desde el módulo de Caja antes de registrar ventas.')
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
                    ->title('Error de Producto')
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
