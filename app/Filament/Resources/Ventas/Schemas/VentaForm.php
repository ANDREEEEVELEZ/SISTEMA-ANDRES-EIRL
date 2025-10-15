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
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Services\CajaService;
use App\Services\ApisNetPeService;
use Closure;

class VentaForm
{
    // Helper para verificar si los campos deben estar deshabilitados
    protected static function shouldDisableFields(): bool
    {
        return CajaService::tieneCajaAbiertaDiaAnterior();
    }

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
                    ->disabled(self::shouldDisableFields()) // Deshabilitar si hay caja anterior abierta
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
                        // Verificar si hay caja del día anterior abierta
                        if (self::shouldDisableFields()) {
                            $cajaAnterior = CajaService::getCajaAbiertaDiaAnterior();
                            return "Hay una caja abierta desde el {$cajaAnterior->fecha_apertura->format('d/m/Y H:i')}. Para continuar registrando ventas, debe cerrar la caja anterior y aperturar una nueva para hoy.";
                        }

                        $cajaAbierta = \App\Models\Caja::where('estado', 'abierta')
                            ->where('user_id', Auth::id())
                            ->first();

                        if ($cajaAbierta) {
                            return null;
                        } else {
                            $cajasCerradas = \App\Models\Caja::where('user_id', Auth::id())
                                ->where('estado', 'cerrada')
                                ->count();

                            if ($cajasCerradas > 0) {
                                return "No hay cajas abiertas. Use el botón '+' para aperturar caja ";
                            } else {
                                return "Para comenzar a registrar ventas, debe aperturar una caja usando el botón '+' para aperturar caja";
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
                    ])
                    ->required()
                    ->default('ticket')
                    ->placeholder('Seleccione una opción')
                    ->disabled(fn (callable $get) => self::shouldDisableFields() || !$get('caja_id')) // Deshabilitado si hay caja anterior o no hay caja
                    //->helperText(fn (callable $get) => !$get('caja_id') ? 'Primero debe seleccionar una caja' : 'Seleccione el tipo de comprobante')
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
                    ->disabled(fn (callable $get) => self::shouldDisableFields() || !$get('caja_id') || true) // Deshabilitado si hay caja anterior, no hay caja o siempre (solo lectura)
                   // ->helperText(fn (callable $get) => !$get('caja_id') ? 'Primero debe seleccionar una caja' : 'Se asigna automáticamente')
                    ->default(function () {
                        // Asignar serie por defecto para ticket
                        $serieComprobante = SerieComprobante::where('tipo', 'ticket')->first();
                        return $serieComprobante?->serie;
                    })
                    ->dehydrated(),

                TextInput::make('numero')
                    ->label('Número')
                    ->required()
                    ->maxLength(10)
                    ->disabled(fn (callable $get) => self::shouldDisableFields() || !$get('caja_id') || true) // Deshabilitado si hay caja anterior, no hay caja o siempre (solo lectura)
                   // ->helperText(fn (callable $get) => !$get('caja_id') ? 'Primero debe seleccionar una caja' : 'Se asigna automáticamente')
                    ->default(function () {
                        // Asignar número por defecto para ticket
                        $serieComprobante = SerieComprobante::where('tipo', 'ticket')->first();
                        if ($serieComprobante) {
                            $siguienteNumero = $serieComprobante->ultimo_numero + 1;
                            return str_pad($siguienteNumero, 6, '0', STR_PAD_LEFT);
                        }
                        return null;
                    })
                    ->dehydrated(),


                // -- Fecha y Hora --
                DatePicker::make('fecha_emision')
                    ->label('Fecha de Emisión')
                    ->default(now())
                    ->required()
                    ->native(false)
                    ->displayFormat('d/m/Y')
                    ->disabled(fn (callable $get) => self::shouldDisableFields() || !$get('caja_id')) // Deshabilitado si hay caja anterior o no hay caja
                    ->helperText(fn (callable $get) => !$get('caja_id') ? 'Primero debe seleccionar una caja' : null),

                TimePicker::make('hora_venta')
                    ->label('Hora de Venta')
                    ->default(now()->format('H:i'))
                    ->required()
                    ->seconds(false)
                    ->disabled(fn (callable $get) => self::shouldDisableFields() || !$get('caja_id')) // Deshabilitado si hay caja anterior o no hay caja
                    ->helperText(fn (callable $get) => !$get('caja_id') ? 'Primero debe seleccionar una caja' : null),

                // -- Información del Cliente --
                Select::make('cliente_id')
                    ->label('Cliente')
                    ->relationship('cliente', 'nombre_razon', fn ($query) => $query->where('estado', 'activo'))
                    ->getOptionLabelFromRecordUsing(fn ($record) =>
                        "{$record->tipo_doc}: {$record->num_doc} - {$record->nombre_razon}"
                    )
                    ->searchable(['num_doc', 'nombre_razon'])
                    ->disabled(fn (callable $get) => self::shouldDisableFields() || !$get('caja_id')) // Deshabilitado si hay caja anterior o no hay caja
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
                            ->maxLength(20)
                            ->suffixActions([
                                Action::make('consultarDocumento')
                                    ->label('Consultar')
                                    ->icon('heroicon-o-magnifying-glass')
                                    ->color('primary')
                                    ->action(function ($state, $set, $get) {
                                        if (!$state) {
                                            Notification::make()
                                                ->title('Error')
                                                ->danger()
                                                ->body('Debe ingresar un número de documento primero')
                                                ->send();
                                            return;
                                        }

                                        $tipoDoc = $get('tipo_doc');
                                        if (!$tipoDoc) {
                                            Notification::make()
                                                ->title('Error')
                                                ->danger()
                                                ->body('Debe seleccionar el tipo de documento primero')
                                                ->send();
                                            return;
                                        }

                                        // Validar formato de documento
                                        if ($tipoDoc === 'DNI' && strlen($state) !== 8) {
                                            Notification::make()
                                                ->title('Formato Incorrecto')
                                                ->warning()
                                                ->body('El DNI debe tener 8 dígitos')
                                                ->send();
                                            return;
                                        }

                                        if ($tipoDoc === 'RUC' && strlen($state) !== 11) {
                                            Notification::make()
                                                ->title('Formato Incorrecto')
                                                ->warning()
                                                ->body('El RUC debe tener 11 dígitos')
                                                ->send();
                                            return;
                                        }

                                        // Mostrar notificación de consulta en progreso
                                        Notification::make()
                                            ->title('Consultando...')
                                            ->info()
                                            ->body('Consultando documento ' . $state . ' en SUNAT/RENIEC...')
                                            ->send();

                                        try {
                                            $apisNetService = app(ApisNetPeService::class);

                                            if ($tipoDoc === 'DNI') {
                                                $response = $apisNetService->consultarDni($state);

                                                // Debug: mostrar respuesta completa
                                                Log::info('Respuesta DNI API:', $response);

                                                // Verificar si hay mensaje de error
                                                if (isset($response['message'])) {
                                                    if ($response['message'] === 'not found') {
                                                        Notification::make()
                                                            ->title('DNI No Encontrado')
                                                            ->warning()
                                                            ->body('No se encontraron datos para el DNI: ' . $state)
                                                            ->send();
                                                        return;
                                                    } else {
                                                        Notification::make()
                                                            ->title('Error de API')
                                                            ->danger()
                                                            ->body('Error: ' . $response['message'])
                                                            ->send();
                                                        return;
                                                    }
                                                }

                                                if (isset($response['error'])) {
                                                    Notification::make()
                                                        ->title('Error de Consulta')
                                                        ->danger()
                                                        ->body('Error al consultar DNI: ' . $response['error'])
                                                        ->send();
                                                    return;
                                                }

                                                // Verificar diferentes estructuras de respuesta posibles
                                                if (isset($response['full_name']) || isset($response['first_name']) || isset($response['nombres'])) {
                                                    $data = $response['data'] ?? $response;

                                                    // Priorizar full_name si existe (Decolecta API)
                                                    if (isset($data['full_name'])) {
                                                        $nombreCompleto = trim($data['full_name']);
                                                    } else {
                                                        // Construir nombre desde campos individuales
                                                        $nombreCompleto = trim(
                                                            ($data['first_name'] ?? $data['nombres'] ?? $data['primer_nombre'] ?? '') . ' ' .
                                                            ($data['first_last_name'] ?? $data['apellidoPaterno'] ?? $data['apellido_paterno'] ?? '') . ' ' .
                                                            ($data['second_last_name'] ?? $data['apellidoMaterno'] ?? $data['apellido_materno'] ?? '')
                                                        );
                                                    }

                                                    if ($nombreCompleto) {
                                                        $set('nombre_razon', $nombreCompleto);
                                                        $set('tipo_cliente', 'natural');

                                                        // Completar dirección si está disponible, si no, colocar "-"
                                                        if (isset($data['direccion']) && $data['direccion']) {
                                                            $set('direccion', $data['direccion']);
                                                        } else {
                                                            $set('direccion', '-');
                                                        }

                                                        Notification::make()
                                                            ->title('Consulta Exitosa')
                                                            ->success()
                                                            ->body('Datos encontrados: ' . $nombreCompleto)
                                                            ->send();
                                                    } else {
                                                        Notification::make()
                                                            ->title('Datos Incompletos')
                                                            ->warning()
                                                            ->body('La API devolvió datos pero están incompletos')
                                                            ->send();
                                                    }
                                                } else {
                                                    Notification::make()
                                                        ->title('DNI No Encontrado')
                                                        ->warning()
                                                        ->body('No se encontraron datos para el DNI: ' . $state . '. Respuesta: ' . json_encode($response))
                                                        ->send();
                                                }

                                            } elseif ($tipoDoc === 'RUC') {
                                                $response = $apisNetService->consultarRuc($state);

                                                // Debug: mostrar respuesta completa
                                                Log::info('Respuesta RUC API:', $response);

                                                // Verificar si hay mensaje de error
                                                if (isset($response['message'])) {
                                                    if ($response['message'] === 'not found') {
                                                        Notification::make()
                                                            ->title('RUC No Encontrado')
                                                            ->warning()
                                                            ->body('No se encontraron datos para el RUC: ' . $state)
                                                            ->send();
                                                        return;
                                                    } else {
                                                        Notification::make()
                                                            ->title('Error de API')
                                                            ->danger()
                                                            ->body('Error: ' . $response['message'])
                                                            ->send();
                                                        return;
                                                    }
                                                }

                                                if (isset($response['error'])) {
                                                    Notification::make()
                                                        ->title('Error de Consulta')
                                                        ->danger()
                                                        ->body('Error al consultar RUC: ' . $response['error'])
                                                        ->send();
                                                    return;
                                                }

                                                // Verificar diferentes estructuras de respuesta posibles
                                                if (isset($response['razon_social']) || isset($response['razonSocial']) || isset($response['data']['razon_social'])) {
                                                    $data = $response['data'] ?? $response;
                                                    $razonSocial = $data['razon_social'] ?? $data['razonSocial'] ?? '';

                                                    if ($razonSocial) {
                                                        $set('nombre_razon', $razonSocial);
                                                        $set('tipo_cliente', 'juridica');

                                                        // Completar dirección si está disponible
                                                        if (isset($data['direccion']) && $data['direccion']) {
                                                            $set('direccion', $data['direccion']);
                                                        }

                                                        Notification::make()
                                                            ->title('Consulta Exitosa')
                                                            ->success()
                                                            ->body('Empresa encontrada: ' . $razonSocial)
                                                            ->send();
                                                    } else {
                                                        Notification::make()
                                                            ->title('Datos Incompletos')
                                                            ->warning()
                                                            ->body('La API devolvió datos pero están incompletos')
                                                            ->send();
                                                    }
                                                } else {
                                                    Notification::make()
                                                        ->title('RUC No Encontrado')
                                                        ->warning()
                                                        ->body('No se encontraron datos para el RUC: ' . $state . '. Respuesta: ' . json_encode($response))
                                                        ->send();
                                                }
                                            }

                                        } catch (\Exception $e) {
                                            Notification::make()
                                                ->title('Error del Sistema')
                                                ->danger()
                                                ->body('Error interno: ' . $e->getMessage())
                                                ->send();
                                        }
                                    })
                                    ->tooltip('Consultar documento en base de datos externa')
                            ]),

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
                        Textarea::make('direccion')
                            ->label('Dirección')
                            ->rows(2)
                            ->maxLength(500),


                    ])
                    ->createOptionModalHeading('Registrar Nuevo Cliente'),

                // === DETALLES DE LA VENTA (PRODUCTOS) ===
                Repeater::make('detalleVentas')
                    ->label('Productos de la Venta')
                    ->relationship('detalleVentas')
                    ->disabled(fn (callable $get) => self::shouldDisableFields() || !$get('caja_id')) // Deshabilitado si hay caja anterior o no hay caja
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
                    ->disabled(fn (callable $get) => self::shouldDisableFields() || !$get('caja_id') || true) // Deshabilitado si hay caja anterior, no hay caja o siempre (calculado)
                    ->helperText(fn (callable $get) => !$get('caja_id') ? 'Primero debe seleccionar una caja' : 'Calculado automáticamente')
                    ->dehydrated(),

                TextInput::make('descuento_total')
                    ->label('Descuento Total Aplicado')
                    ->numeric()
                    ->prefix('S/')
                    ->default(0)
                    ->required()
                    ->step(0.01)
                    ->disabled(fn (callable $get) => self::shouldDisableFields() || !$get('caja_id') || true) // Deshabilitado si hay caja anterior, no hay caja o siempre (calculado)
                    ->helperText(fn (callable $get) => !$get('caja_id') ? 'Primero debe seleccionar una caja' : 'Calculado automáticamente')
                    ->dehydrated(),

                TextInput::make('igv')
                    ->label('IGV (18%)')
                    ->numeric()
                    ->prefix('S/')
                    ->required()
                    ->step(0.01)
                    ->disabled(fn (callable $get) => self::shouldDisableFields() || !$get('caja_id') || true) // Deshabilitado si hay caja anterior, no hay caja o siempre (calculado)
                    ->helperText(fn (callable $get) => !$get('caja_id') ? 'Primero debe seleccionar una caja' : 'IGV extraído del total')
                    ->dehydrated(),

                TextInput::make('total_venta')
                    ->label('TOTAL A PAGAR (Con IGV)')
                    ->numeric()
                    ->prefix('S/')
                    ->required()
                    ->step(0.01)
                    ->disabled(fn (callable $get) => self::shouldDisableFields() || !$get('caja_id') || true) // Deshabilitado si hay caja anterior, no hay caja o siempre (calculado)
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
                    ->disabled(fn (callable $get) => self::shouldDisableFields() || !$get('caja_id')) // Deshabilitado si hay caja anterior o no hay caja
                    ->helperText(fn (callable $get) => !$get('caja_id') ? 'Primero debe seleccionar una caja' : 'Seleccione cómo pagará el cliente'),

                TextInput::make('cod_operacion')
                    ->label('Código de Operación / Transacción')
                    ->maxLength(100)
                    ->disabled(fn (callable $get) => self::shouldDisableFields() || !$get('caja_id')) // Deshabilitado si hay caja anterior o no hay caja
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
                    ->disabled(true) // Siempre bloqueado por defecto como "emitida"
                    ->dehydrated() // Asegurar que el valor se envíe aunque esté deshabilitado
                    //->helperText('Las ventas se registran automáticamente como "Emitida"'),
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
