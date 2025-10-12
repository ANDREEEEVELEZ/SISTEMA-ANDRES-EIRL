<?php

namespace App\Filament\Resources\Ventas\Schemas;

use App\Models\Cliente;
use App\Models\Producto;
use App\Models\PrecioProducto;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Closure;

class VentaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                // === INFORMACIÓN DEL USUARIO Y CAJA ===
                Select::make('user_id')
                    ->label('Vendedor')
                    ->relationship('user', 'name')
                    ->default(Auth::id())
                    ->required()
                    ->disabled()
                    ->dehydrated(),
                
                Select::make('caja_id')
                    ->label('Caja')
                    ->relationship('caja', 'id')
                    ->getOptionLabelFromRecordUsing(fn ($record) => 
                        "Caja #{$record->id} - " . 
                        $record->fecha_apertura->format('d/m/Y') . 
                        " ({$record->estado})"
                    )
                    ->searchable()
                    ->preload()
                    ->required()
                    ->helperText('Seleccione la caja donde se registrará la venta'),
                
                // === INFORMACIÓN DEL CLIENTE ===
                Select::make('cliente_id')
                    ->label('Cliente')
                    ->relationship('cliente', 'nombre_razon')
                    ->getOptionLabelFromRecordUsing(fn ($record) => 
                        "{$record->tipo_doc}: {$record->num_doc} - {$record->nombre_razon}"
                    )
                    ->searchable(['num_doc', 'nombre_razon'])
                    ->preload()
                    ->required()
                    ->createOptionForm([
                        Select::make('tipo_doc')
                            ->label('Tipo de Documento')
                            ->options([
                                'DNI' => 'DNI',
                                'RUC' => 'RUC',
                                'CE' => 'Carnet de Extranjería',
                                'PASAPORTE' => 'Pasaporte',
                            ])
                            ->required(),
                        
                        Select::make('tipo_cliente')
                            ->label('Tipo de Cliente')
                            ->options([
                                'natural' => 'Persona Natural',
                                'juridica' => 'Persona Jurídica',
                            ])
                            ->required(),
                        
                        TextInput::make('num_doc')
                            ->label('Número de Documento')
                            ->required()
                            ->maxLength(20),
                        
                        TextInput::make('nombre_razon')
                            ->label('Nombre / Razón Social')
                            ->required()
                            ->maxLength(255),
                        
                        TextInput::make('telefono')
                            ->label('Teléfono')
                            ->tel()
                            ->maxLength(20),
                        
                        Textarea::make('direccion')
                            ->label('Dirección')
                            ->rows(2),
                    ])
                    ->createOptionModalHeading('Registrar Nuevo Cliente')
                    ->helperText('Busque por documento o nombre, o cree uno nuevo'),
                
                // === FECHA Y HORA DE LA VENTA ===
                DatePicker::make('fecha_venta')
                    ->label('Fecha de Venta')
                    ->default(now())
                    ->required()
                    ->native(false)
                    ->displayFormat('d/m/Y'),
                
                TimePicker::make('hora_venta')
                    ->label('Hora de Venta')
                    ->default(now()->format('H:i'))
                    ->required()
                    ->seconds(false),
                
                // === DETALLES DE LA VENTA (PRODUCTOS) ===
                Repeater::make('detalleVentas')
                    ->label('Productos de la Venta')
                    ->relationship('detalleVentas')
                    ->schema([
                        Select::make('producto_id')
                            ->label('Producto')
                            ->options(
                                Producto::where('estado', 'activo')
                                    ->where('stock_total', '>', 0)
                                    ->get()
                                    ->mapWithKeys(fn ($producto) => [
                                        $producto->id => "{$producto->nombre_producto} (Stock: {$producto->stock_total} {$producto->unidad_medida})"
                                    ])
                            )
                            ->searchable()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, $set, $get) {
                                if (!$state) return;
                                
                                $cantidad = $get('cantidad_venta') ?? 1;
                                $precio = self::obtenerPrecioSegunCantidad($state, $cantidad);
                                $set('precio_unitario', $precio);
                                
                                // Recalcular subtotal del item
                                $descuento = $get('descuento_unitario') ?? 0;
                                $subtotal = ($precio - $descuento) * $cantidad;
                                $set('subtotal', round($subtotal, 2));
                                
                                // Recalcular totales generales - usar ruta relativa correcta
                                $detalles = $get('../../detalleVentas');
                                self::actualizarTotales($detalles, $set);
                            })
                            ->helperText('Seleccione el producto a vender'),
                        
                        TextInput::make('cantidad_venta')
                            ->label('Cantidad')
                            ->required()
                            ->numeric()
                            ->default(1)
                            ->minValue(1)
                            ->suffix('unidades')
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, $set, $get) {
                                $productoId = $get('producto_id');
                                if (!$productoId || !$state) return;
                                
                                // Actualizar precio según cantidad
                                $precio = self::obtenerPrecioSegunCantidad($productoId, $state);
                                $set('precio_unitario', $precio);
                                
                                // Recalcular subtotal del item
                                $descuento = $get('descuento_unitario') ?? 0;
                                $subtotal = ($precio - $descuento) * $state;
                                $set('subtotal', round($subtotal, 2));
                                
                                // Recalcular totales generales
                                $detalles = $get('../../detalleVentas');
                                self::actualizarTotales($detalles, $set);
                            }),
                        
                        TextInput::make('precio_unitario')
                            ->label('Precio Unitario')
                            ->required()
                            ->numeric()
                            ->prefix('S/')
                            ->step(0.01)
                            ->minValue(0.01)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, $set, $get) {
                                $cantidad = $get('cantidad_venta') ?? 1;
                                $descuento = $get('descuento_unitario') ?? 0;
                                $subtotal = ($state - $descuento) * $cantidad;
                                $set('subtotal', round($subtotal, 2));
                                
                                // Recalcular totales generales
                                $detalles = $get('../../detalleVentas');
                                self::actualizarTotales($detalles, $set);
                            })
                            ->helperText('Verifique el precio según la cantidad'),
                        
                        TextInput::make('descuento_unitario')
                            ->label('Descuento x Unidad')
                            ->numeric()
                            ->prefix('S/')
                            ->default(0)
                            ->minValue(0)
                            ->step(0.01)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, $set, $get) {
                                $cantidad = $get('cantidad_venta') ?? 1;
                                $precio = $get('precio_unitario') ?? 0;
                                $subtotal = ($precio - ($state ?? 0)) * $cantidad;
                                $set('subtotal', round($subtotal, 2));
                                
                                // Recalcular totales generales
                                $detalles = $get('../../detalleVentas');
                                self::actualizarTotales($detalles, $set);
                            }),
                        
                        TextInput::make('subtotal')
                            ->label('Subtotal')
                            ->numeric()
                            ->prefix('S/')
                            ->required()
                            ->step(0.01)
                            ->disabled()
                            ->dehydrated()
                            ->helperText('(Precio - Descuento) x Cantidad'),
                    ])
                    ->columns(5)
                    ->defaultItems(1)
                    ->addActionLabel('➕ Agregar Producto')
                    ->collapsible()
                    ->itemLabel(fn (array $state): ?string => 
                        isset($state['producto_id']) && isset($state['cantidad_venta'])
                            ? Producto::find($state['producto_id'])?->nombre_producto . " x{$state['cantidad_venta']}"
                            : 'Nuevo producto'
                    )
                    ->reorderable(false)
                    ->live()
                    ->afterStateUpdated(function ($state, $set) {
                        self::calcularTotalesVenta($state, $set);
                    })
                    ->deleteAction(
                        fn ($action) => $action->after(fn ($state, $set) => self::calcularTotalesVenta($state, $set))
                    )
                    ->helperText('Agregue todos los productos que se están vendiendo. El sistema calculará los totales.')
                    ->columnSpanFull(),
                
                // === TOTALES DE LA VENTA ===
                TextInput::make('subtotal_venta')
                    ->label('Subtotal (Con IGV Incluido)')
                    ->numeric()
                    ->prefix('S/')
                    ->required()
                    ->step(0.01)
                    ->disabled()
                    ->dehydrated()
                    ->helperText('Suma de todos los productos (precios incluyen IGV)'),
                
                TextInput::make('descuento_total')
                    ->label('Descuento Total Aplicado')
                    ->numeric()
                    ->prefix('S/')
                    ->default(0)
                    ->required()
                    ->step(0.01)
                    ->disabled()
                    ->dehydrated(),
                
                TextInput::make('igv')
                    ->label('IGV (18%)')
                    ->numeric()
                    ->prefix('S/')
                    ->required()
                    ->step(0.01)
                    ->disabled()
                    ->dehydrated()
                    ->helperText('IGV extraído del total (ya incluido en precios)'),
                
                TextInput::make('total_venta')
                    ->label('TOTAL A PAGAR')
                    ->numeric()
                    ->prefix('S/')
                    ->required()
                    ->step(0.01)
                    ->disabled()
                    ->dehydrated()
                    ->extraAttributes(['class' => 'font-bold text-lg'])
                    ->helperText('Monto total con IGV incluido'),
                
                // === MÉTODO DE PAGO ===
                Select::make('metodo_pago')
                    ->label('Método de Pago')
                    ->options([
                        'efectivo' => 'Efectivo',
                        'tarjeta' => 'Tarjeta',
                        'yape' => 'Yape',
                        'plin' => 'Plin',
                        'transferencia' => 'Transferencia Bancaria',
                    ])
                    ->required()
                    ->helperText('Seleccione cómo pagará el cliente'),
                
                TextInput::make('cod_operacion')
                    ->label('Código de Operación / Transacción')
                    ->maxLength(100)
                    ->helperText('Solo para pagos digitales o con tarjeta'),
                
                // === ESTADO DE LA VENTA ===
                Select::make('estado_venta')
                    ->label('Estado de la Venta')
                    ->options([
                        'emitida' => 'Emitida',
                        'anulada' => 'Anulada',
                        'rechazada' => 'Rechazada'
                    ])
                    ->default('emitida')
                    ->required(),
            ]);
    }

    /**
     * Obtiene el precio del producto según la cantidad
     */
    protected static function obtenerPrecioSegunCantidad(int $productoId, float $cantidad): float
    {
        // Obtener todos los precios del producto ordenados por cantidad mínima descendente
        $precios = PrecioProducto::where('producto_id', $productoId)
            ->orderBy('cantidad_minima', 'desc')
            ->get();

        // Si no hay precios configurados, retornar 0
        if ($precios->isEmpty()) {
            return 0;
        }

        // Buscar el precio adecuado según la cantidad
        foreach ($precios as $precio) {
            if ($cantidad >= $precio->cantidad_minima) {
                return (float) $precio->precio_unitario;
            }
        }

        // Si no encuentra ninguno, retornar el precio del rango más bajo
        return (float) $precios->last()->precio_unitario;
    }

    /**
     * Calcula los totales de la venta
     * NOTA: Los precios YA INCLUYEN IGV (18%)
     */
    protected static function calcularTotalesVenta(?array $detalles, $set): void
    {
        if (!$detalles || empty($detalles)) {
            $set('subtotal_venta', 0);
            $set('descuento_total', 0);
            $set('igv', 0);
            $set('total_venta', 0);
            return;
        }

        $subtotalSinDescuento = 0;
        $descuentoTotal = 0;
        $totalConIgvIncluido = 0;

        foreach ($detalles as $detalle) {
            if (!isset($detalle['cantidad_venta']) || !isset($detalle['precio_unitario'])) {
                continue;
            }

            $cantidad = (float) $detalle['cantidad_venta'];
            $precioUnitario = (float) $detalle['precio_unitario'];
            $descuentoUnitario = (float) ($detalle['descuento_unitario'] ?? 0);

            // Calcular subtotal sin descuento (con IGV incluido)
            $subtotalSinDescuento += $precioUnitario * $cantidad;

            // Calcular descuento total
            $descuentoTotal += $descuentoUnitario * $cantidad;

            // Calcular total con descuento (ya viene con IGV incluido)
            if (isset($detalle['subtotal'])) {
                $totalConIgvIncluido += (float) $detalle['subtotal'];
            } else {
                $totalConIgvIncluido += ($precioUnitario - $descuentoUnitario) * $cantidad;
            }
        }

        // El total final es el subtotal con descuento (ya tiene IGV incluido)
        $totalVenta = $totalConIgvIncluido;

        // Extraer el IGV del total (IGV ya está incluido en los precios)
        // Fórmula: Base Imponible = Total / 1.18
        // IGV = Total - Base Imponible
        $baseImponible = $totalVenta / 1.18;
        $igv = $totalVenta - $baseImponible;

        // Establecer los valores con 2 decimales
        $set('subtotal_venta', round($subtotalSinDescuento, 2));
        $set('descuento_total', round($descuentoTotal, 2));
        $set('igv', round($igv, 2));
        $set('total_venta', round($totalVenta, 2));
    }

    /**
     * Actualiza los totales desde dentro del Repeater
     * Usa rutas relativas para $set
     * NOTA: Los precios YA INCLUYEN IGV (18%)
     */
    protected static function actualizarTotales(?array $detalles, $set): void
    {
        if (!$detalles || empty($detalles)) {
            $set('../../subtotal_venta', 0);
            $set('../../descuento_total', 0);
            $set('../../igv', 0);
            $set('../../total_venta', 0);
            return;
        }

        $subtotalSinDescuento = 0;
        $descuentoTotal = 0;
        $totalConIgvIncluido = 0;

        foreach ($detalles as $detalle) {
            if (!isset($detalle['cantidad_venta']) || !isset($detalle['precio_unitario'])) {
                continue;
            }

            $cantidad = (float) $detalle['cantidad_venta'];
            $precioUnitario = (float) $detalle['precio_unitario'];
            $descuentoUnitario = (float) ($detalle['descuento_unitario'] ?? 0);

            // Calcular subtotal sin descuento (con IGV incluido)
            $subtotalSinDescuento += $precioUnitario * $cantidad;

            // Calcular descuento total
            $descuentoTotal += $descuentoUnitario * $cantidad;

            // Calcular total con descuento (ya viene con IGV incluido)
            if (isset($detalle['subtotal'])) {
                $totalConIgvIncluido += (float) $detalle['subtotal'];
            } else {
                $totalConIgvIncluido += ($precioUnitario - $descuentoUnitario) * $cantidad;
            }
        }

        // El total final es el subtotal con descuento (ya tiene IGV incluido)
        $totalVenta = $totalConIgvIncluido;

        // Extraer el IGV del total (IGV ya está incluido en los precios)
        // Fórmula: Base Imponible = Total / 1.18
        // IGV = Total - Base Imponible
        $baseImponible = $totalVenta / 1.18;
        $igv = $totalVenta - $baseImponible;

        // Establecer los valores con 2 decimales usando rutas relativas
        $set('../../subtotal_venta', round($subtotalSinDescuento, 2));
        $set('../../descuento_total', round($descuentoTotal, 2));
        $set('../../igv', round($igv, 2));
        $set('../../total_venta', round($totalVenta, 2));
    }
}
