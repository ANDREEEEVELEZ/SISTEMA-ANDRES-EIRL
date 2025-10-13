<?php

namespace App\Filament\Resources\Ventas\Schemas;

use App\Models\Cliente;
use App\Models\Producto;
use App\Models\PrecioProducto;
use App\Models\SerieComprobante;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Grid;
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
                    ->relationship('caja', 'id', fn ($query) =>
                        $query->where('estado', 'abierta')
                              ->where('user_id', Auth::id()) // Solo cajas del usuario actual
                    )
                    ->getOptionLabelFromRecordUsing(fn ($record) =>
                        "Caja #{$record->id} - " .
                        $record->fecha_apertura->format('d/m/Y H:i') .
                        " (Activa)"
                    )
                    ->searchable()
                    ->preload()
                    ->required()
                    ->live() // Hace reactivo el campo
                    ->default(function () {
                        // Buscar si hay una caja abierta para el usuario actual
                        $cajaAbierta = \App\Models\Caja::where('estado', 'abierta')
                            ->where('user_id', Auth::id())
                            ->first();

                        return $cajaAbierta?->id;
                    })
                    ->afterStateUpdated(function ($state, $set) {
                        // Cuando se selecciona una caja, mostrar notificación informativa
                        if ($state) {
                            $caja = \App\Models\Caja::find($state);
                            if ($caja) {
                                // Opcional: Aquí puedes agregar lógica adicional cuando se seleccione la caja
                                // Por ejemplo, resetear campos o configurar valores por defecto
                            }
                        }
                    })
                    ->helperText(function () {
                        $cajaAbierta = \App\Models\Caja::where('estado', 'abierta')
                            ->where('user_id', Auth::id())
                            ->first();

                        if ($cajaAbierta) {
                            return "Caja seleccionada automáticamente";
                        } else {
                            $cajasCerradas = \App\Models\Caja::where('user_id', Auth::id())
                                ->where('estado', 'cerrada')
                                ->count();

                            if ($cajasCerradas > 0) {
                                return "No hay cajas abiertas debe aperturar una nueva caja.";
                            } else {
                                return "Debe aperturar una caja para comenzar a registrar ventas.";
                            }
                        }
                    })
                    ->createOptionForm([
                        TextInput::make('saldo_inicial')
                            ->label('Saldo Inicial')
                            ->required()
                            ->numeric()
                            ->default(0)
                            ->prefix('S/.')
                            ->helperText('Ingrese el saldo inicial para aperturar la caja')
                            ->placeholder('0.00'),

                        Textarea::make('observacion')
                            ->label('Observación')
                            ->placeholder('Observaciones de apertura (opcional)')
                            ->maxLength(255)
                            ->rows(2),
                    ])
                    ->createOptionUsing(function (array $data): int {
                        // VALIDACIÓN: Verificar que no se intente reabrir una caja cerrada
                        $cajasCerradas = \App\Models\Caja::where('user_id', Auth::id())
                            ->where('estado', 'cerrada')
                            ->exists();

                        // Cerrar cualquier caja abierta del usuario antes de crear una nueva
                        $cajasAbiertas = \App\Models\Caja::where('user_id', Auth::id())
                            ->where('estado', 'abierta')
                            ->get();

                        foreach ($cajasAbiertas as $cajaAbierta) {
                            $cajaAbierta->update([
                                'estado' => 'cerrada',
                                'fecha_cierre' => now(),
                                'saldo_final' => $cajaAbierta->saldo_inicial, // Mantener el saldo inicial como final por defecto
                            ]);
                        }

                        // Crear la nueva caja (siempre se crea una NUEVA caja, nunca se reabre una cerrada)
                        $caja = \App\Models\Caja::create([
                            'user_id' => Auth::id(),
                            'fecha_apertura' => now(),
                            'fecha_cierre' => null,
                            'saldo_inicial' => $data['saldo_inicial'],
                            'saldo_final' => null,
                            'estado' => 'abierta',
                            'observacion' => $cajasCerradas
                                ? 'Nueva caja aperturada (no se pueden reabrir cajas cerradas)'
                                : ($data['observacion'] ?? 'Aperturada desde el módulo de ventas'),
                        ]);

                        return $caja->id;
                    })
                    ->createOptionModalHeading('🏦 Aperturar Nueva Caja')
                    ->createOptionAction(function ($action) {
                        return $action->label('➕ Aperturar Caja')
                            ->color('success')
                            ->icon('heroicon-o-plus-circle');
                    }),

                // === DATOS GENERALES ===

                // -- Datos del Comprobante --
                Select::make('tipo_comprobante')
                    ->label('Tipo de Comprobante')
                    ->options([
                        'boleta' => 'Boleta',
                        'factura' => 'Factura',
                        'ticket' => 'Ticket',
                        'nota de credito' => 'Nota de Crédito',
                        'nota de debito' => 'Nota de Débito',
                    ])
                    ->required()
                    ->placeholder('Seleccione una opción')
                    ->disabled(fn (callable $get) => !$get('caja_id')) // Deshabilitado si no hay caja
                    ->helperText(fn (callable $get) => !$get('caja_id') ? 'Primero debe seleccionar una caja' : 'Seleccione el tipo de comprobante')
                    ->live()
                    ->afterStateUpdated(function ($state, $set) {
                        if (!$state) return;

                        // Buscar la serie correspondiente al tipo seleccionado
                        $serieComprobante = SerieComprobante::where('tipo', $state)->first();

                        if ($serieComprobante) {
                            // Asignar la serie automáticamente
                            $set('serie', $serieComprobante->serie);

                            // Calcular el siguiente número (último_numero + 1)
                            $siguienteNumero = $serieComprobante->ultimo_numero + 1;
                            $set('numero', str_pad($siguienteNumero, 6, '0', STR_PAD_LEFT));
                        }
                    }),

                TextInput::make('serie')
                    ->label('Serie')
                    ->required()
                    ->maxLength(10)
                    ->disabled(fn (callable $get) => !$get('caja_id') || true) // Deshabilitado si no hay caja o siempre (solo lectura)
                    ->helperText(fn (callable $get) => !$get('caja_id') ? 'Primero debe seleccionar una caja' : 'Se asigna automáticamente')
                    ->dehydrated(),

                TextInput::make('numero')
                    ->label('Número')
                    ->required()
                    ->maxLength(10)
                    ->disabled(fn (callable $get) => !$get('caja_id') || true) // Deshabilitado si no hay caja o siempre (solo lectura)
                    ->helperText(fn (callable $get) => !$get('caja_id') ? 'Primero debe seleccionar una caja' : 'Se asigna automáticamente')
                    ->dehydrated(),


                // -- Fecha y Hora --
                DatePicker::make('fecha_emision')
                    ->label('Fecha de Emisión')
                    ->default(now())
                    ->required()
                    ->native(false)
                    ->displayFormat('d/m/Y')
                    ->disabled(fn (callable $get) => !$get('caja_id')) // Deshabilitado si no hay caja
                    ->helperText(fn (callable $get) => !$get('caja_id') ? 'Primero debe seleccionar una caja' : null),

                TimePicker::make('hora_venta')
                    ->label('Hora de Venta')
                    ->default(now()->format('H:i'))
                    ->required()
                    ->seconds(false)
                    ->disabled(fn (callable $get) => !$get('caja_id')) // Deshabilitado si no hay caja
                    ->helperText(fn (callable $get) => !$get('caja_id') ? 'Primero debe seleccionar una caja' : null),

                // -- Información del Cliente --
                Select::make('cliente_id')
                    ->label('Cliente')
                    ->relationship('cliente', 'nombre_razon', fn ($query) => $query->where('estado', 'activo'))
                    ->getOptionLabelFromRecordUsing(fn ($record) =>
                        "{$record->tipo_doc}: {$record->num_doc} - {$record->nombre_razon}"
                    )
                    ->searchable(['num_doc', 'nombre_razon'])
                    ->disabled(fn (callable $get) => !$get('caja_id')) // Deshabilitado si no hay caja
                    ->helperText(fn (callable $get) => !$get('caja_id') ? 'Primero debe seleccionar una caja' : 'Buscar por documento o nombre')
                    ->preload()
                    ->required()
                    ->createOptionForm([
                        Select::make('tipo_doc')
                            ->label('Tipo de Documento')
                            ->options([
                                'DNI' => 'DNI',
                                'RUC' => 'RUC',
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
                         Select::make('tipo_cliente')
                            ->label('Tipo de Cliente')
                            ->options([
                                'natural' => 'Persona Natural',
                                'juridica' => 'Persona Jurídica',
                            ])
                            ->required(),

                    ])
                    ->createOptionModalHeading('Registrar Nuevo Cliente'),

                // === DETALLES DE LA VENTA (PRODUCTOS) ===
                Repeater::make('detalleVentas')
                    ->label('Productos de la Venta')
                    ->relationship('detalleVentas')
                    ->disabled(fn (callable $get) => !$get('caja_id')) // Deshabilitado si no hay caja
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
                            ->label('Total')
                            ->numeric()
                            ->prefix('S/')
                            ->required()
                            ->step(0.01)
                            ->disabled()
                            ->dehydrated(),

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
                    ->columnSpanFull(),

                // === TOTALES DE LA VENTA ===
                TextInput::make('subtotal_venta')
                    ->label('Subtotal')
                    ->numeric()
                    ->prefix('S/')
                    ->required()
                    ->step(0.01)
                    ->disabled(fn (callable $get) => !$get('caja_id') || true) // Deshabilitado si no hay caja o siempre (calculado)
                    ->helperText(fn (callable $get) => !$get('caja_id') ? 'Primero debe seleccionar una caja' : 'Calculado automáticamente')
                    ->dehydrated(),

                TextInput::make('descuento_total')
                    ->label('Descuento Total Aplicado')
                    ->numeric()
                    ->prefix('S/')
                    ->default(0)
                    ->required()
                    ->step(0.01)
                    ->disabled(fn (callable $get) => !$get('caja_id') || true) // Deshabilitado si no hay caja o siempre (calculado)
                    ->helperText(fn (callable $get) => !$get('caja_id') ? 'Primero debe seleccionar una caja' : 'Calculado automáticamente')
                    ->dehydrated(),

                TextInput::make('igv')
                    ->label('IGV (18%)')
                    ->numeric()
                    ->prefix('S/')
                    ->required()
                    ->step(0.01)
                    ->disabled(fn (callable $get) => !$get('caja_id') || true) // Deshabilitado si no hay caja o siempre (calculado)
                    ->helperText(fn (callable $get) => !$get('caja_id') ? 'Primero debe seleccionar una caja' : 'IGV extraído del total')
                    ->dehydrated(),

                TextInput::make('total_venta')
                    ->label('TOTAL A PAGAR (Con IGV)')
                    ->numeric()
                    ->prefix('S/')
                    ->required()
                    ->step(0.01)
                    ->disabled(fn (callable $get) => !$get('caja_id') || true) // Deshabilitado si no hay caja o siempre (calculado)
                    ->helperText(fn (callable $get) => !$get('caja_id') ? 'Primero debe seleccionar una caja' : 'Monto total con IGV incluido')
                    ->dehydrated()
                    ->extraAttributes(['class' => 'font-bold text-lg']),

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
                    ->disabled(fn (callable $get) => !$get('caja_id')) // Deshabilitado si no hay caja
                    ->helperText(fn (callable $get) => !$get('caja_id') ? 'Primero debe seleccionar una caja' : 'Seleccione cómo pagará el cliente'),

                TextInput::make('cod_operacion')
                    ->label('Código de Operación / Transacción')
                    ->maxLength(100)
                    ->disabled(fn (callable $get) => !$get('caja_id')) // Deshabilitado si no hay caja
                    ->helperText(fn (callable $get) => !$get('caja_id') ? 'Primero debe seleccionar una caja' : 'Solo para pagos digitales o con tarjeta'),

                // === ESTADO DE LA VENTA ===
                Select::make('estado_venta')
                    ->label('Estado de la Venta')
                    ->options([
                        'emitida' => 'Emitida',
                        'anulada' => 'Anulada',
                        'rechazada' => 'Rechazada'
                    ])
                    ->default('emitida')
                    ->required()
                    ->disabled(fn (callable $get) => !$get('caja_id')) // Deshabilitado si no hay caja
                    ->helperText(fn (callable $get) => !$get('caja_id') ? 'Primero debe seleccionar una caja' : null),
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

        $totalProductosSinDescuento = 0;
        $descuentoTotal = 0;
        $totalConIgvIncluido = 0;

        foreach ($detalles as $detalle) {
            if (!isset($detalle['cantidad_venta']) || !isset($detalle['precio_unitario'])) {
                continue;
            }

            $cantidad = (float) $detalle['cantidad_venta'];
            $precioUnitario = (float) $detalle['precio_unitario'];
            $descuentoUnitario = (float) ($detalle['descuento_unitario'] ?? 0);

            // Calcular total de productos sin descuento (con IGV incluido)
            $totalProductosSinDescuento += $precioUnitario * $cantidad;

            // Calcular descuento total
            $descuentoTotal += $descuentoUnitario * $cantidad;

            // Calcular total con descuento (ya viene con IGV incluido)
            if (isset($detalle['subtotal'])) {
                $totalConIgvIncluido += (float) $detalle['subtotal'];
            } else {
                $totalConIgvIncluido += ($precioUnitario - $descuentoUnitario) * $cantidad;
            }
        }

        // El total final es el total con IGV incluido
        $totalVenta = $totalConIgvIncluido;

        // Extraer el IGV del total (IGV ya está incluido en los precios)
        // Fórmula: Base Imponible (Subtotal) = Total / 1.18
        // IGV = Total - Subtotal
        $subtotalVenta = $totalVenta / 1.18;  // Base imponible sin IGV
        $igv = $totalVenta - $subtotalVenta;

        // Establecer los valores con 2 decimales
        $set('subtotal_venta', round($subtotalVenta, 2));      // Base imponible (sin IGV)
        $set('descuento_total', round($descuentoTotal, 2));
        $set('igv', round($igv, 2));
        $set('total_venta', round($totalVenta, 2));            // Total con IGV
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

        $totalProductosSinDescuento = 0;
        $descuentoTotal = 0;
        $totalConIgvIncluido = 0;

        foreach ($detalles as $detalle) {
            if (!isset($detalle['cantidad_venta']) || !isset($detalle['precio_unitario'])) {
                continue;
            }

            $cantidad = (float) $detalle['cantidad_venta'];
            $precioUnitario = (float) $detalle['precio_unitario'];
            $descuentoUnitario = (float) ($detalle['descuento_unitario'] ?? 0);

            // Calcular total de productos sin descuento (con IGV incluido)
            $totalProductosSinDescuento += $precioUnitario * $cantidad;

            // Calcular descuento total
            $descuentoTotal += $descuentoUnitario * $cantidad;

            // Calcular total con descuento (ya viene con IGV incluido)
            if (isset($detalle['subtotal'])) {
                $totalConIgvIncluido += (float) $detalle['subtotal'];
            } else {
                $totalConIgvIncluido += ($precioUnitario - $descuentoUnitario) * $cantidad;
            }
        }

        // El total final es el total con IGV incluido
        $totalVenta = $totalConIgvIncluido;

        // Extraer el IGV del total (IGV ya está incluido en los precios)
        // Fórmula: Base Imponible (Subtotal) = Total / 1.18
        // IGV = Total - Subtotal
        $subtotalVenta = $totalVenta / 1.18;  // Base imponible sin IGV
        $igv = $totalVenta - $subtotalVenta;

        // Establecer los valores con 2 decimales usando rutas relativas
        $set('../../subtotal_venta', round($subtotalVenta, 2));      // Base imponible (sin IGV)
        $set('../../descuento_total', round($descuentoTotal, 2));
        $set('../../igv', round($igv, 2));
        $set('../../total_venta', round($totalVenta, 2));            // Total con IGV
    }
}
