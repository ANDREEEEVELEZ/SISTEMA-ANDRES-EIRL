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
use Filament\Forms\Components\Hidden;
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
                    ->dehydrated()
                    ->columnSpan(1),

                Select::make('caja_id')
                    ->label('Caja')
                    ->relationship('caja', 'id', fn ($query) =>
                        $query->where('estado', 'abierta')
                              ->where('user_id', Auth::id()) // Solo cajas del usuario actual
                    )
                    ->getOptionLabelFromRecordUsing(fn ($record) =>
                        "Caja #{$record->numero_secuencial} - " .
                        $record->fecha_apertura->format('d/m/Y H:i') .
                        " (Activa)"
                    )
                    ->searchable()
                    ->preload()
                    ->required()
                    ->disabled() // Bloqueado - se asigna automáticamente
                    ->dehydrated() // Asegurar que se guarde aunque esté deshabilitado
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
                    
                    ->columnSpan(1),

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
                    ->afterStateUpdated(function ($state, $set, $get) {
                        if (!$state) return;

                        if ($state === 'boleta') {
                            try {
                                $clienteDefaultId = Cliente::where('num_doc', '00000000')->value('id');
                                if ($clienteDefaultId) {
                                    $currentCliente = $get('cliente_id');
                                    if (empty($currentCliente)) {
                                        $set('cliente_id', $clienteDefaultId);

                                        // También establecer información legible para el formulario
                                        $cliente = Cliente::find($clienteDefaultId);
                                        if ($cliente) {
                                            $set('cliente_encontrado', [
                                                'tipo_doc' => $cliente->tipo_doc,
                                                'num_doc' => $cliente->num_doc,
                                                'nombre' => $cliente->nombre_razon,
                                                'direccion' => $cliente->direccion,
                                            ]);
                                        }
                                    }
                                }
                            } catch (\Exception $e) {
                                Log::warning('No se pudo preseleccionar cliente por defecto: ' . $e->getMessage());
                            }
                        } elseif ($state === 'factura') {
                            // Asegurarnos de que el cliente por defecto (DNI 00000000)
                            // no quede seleccionado para facturas (solo RUC permitidos)
                            try {
                                $currentCliente = $get('cliente_id');
                                if (!empty($currentCliente)) {
                                    $cliente = Cliente::find($currentCliente);
                                    if ($cliente && $cliente->num_doc === '00000000') {
                                        // Limpiar selección para forzar elegir un RUC válido
                                        $set('cliente_id', null);
                                        $set('cliente_encontrado', null);
                                        $set('cliente_inactivo_encontrado', null);
                                        $set('cliente_inactivo_nombre', null);
                                    }
                                }
                            } catch (\Exception $e) {
                                Log::warning('Error al limpiar cliente por defecto al seleccionar factura: ' . $e->getMessage());
                            }
                        }

                        // Buscar la serie correspondiente al tipo seleccionado
                        $serieComprobante = SerieComprobante::where('tipo', $state)->first();

                        if ($serieComprobante) {
                            // Asignar la serie automáticamente
                            $set('serie', $serieComprobante->serie);

                            // Calcular el siguiente número (último_numero + 1)
                            $siguienteNumero = $serieComprobante->ultimo_numero + 1;
                            $set('numero', str_pad($siguienteNumero, 6, '0', STR_PAD_LEFT));
                        }
                    })
                    ->columnSpan(1),

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
                    ->dehydrated()
                    ->columnSpan(1),

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
                    ->dehydrated()
                    ->columnSpan(1),


                // -- Fecha y Hora --
                DatePicker::make('fecha_emision')
                    ->label('Fecha de Emisión')
                    ->default(now())
                    ->required()
                    ->native(false)
                    ->displayFormat('d/m/Y')
                    ->disabled() // Bloqueado - se asigna automáticamente
                    ->dehydrated()
                    ->columnSpan(1),

                TimePicker::make('hora_venta')
                    ->label('Hora de Venta')
                    ->default(now()->format('H:i'))
                    ->required()
                    ->seconds(false)
                    ->disabled() // Bloqueado - se asigna automáticamente
                    ->dehydrated()
                    ->columnSpan(1),



                // -- Información del Cliente --
                // Campos de control (no se guardan en BD)
                Hidden::make('mostrar_formulario_cliente')
                    ->default(false)
                    ->dehydrated(false),

                Hidden::make('cliente_encontrado')
                    ->dehydrated(false),

                Hidden::make('cliente_inactivo_encontrado')
                    ->dehydrated(),

                // Campo de búsqueda/selección de cliente con filtrado
                Select::make('cliente_id')
                    ->label('Cliente')
                    ->placeholder(function (callable $get) {
                        $tipoComprobante = $get('tipo_comprobante');
                        if ($tipoComprobante === 'factura') {
                            return 'Buscar por RUC o nombre de empresa...';
                        }
                        return '';
                    })
                    ->reactive()
                    ->searchable()
                    ->allowHtml()
                    ->disabled(fn (callable $get) => self::shouldDisableFields() || !$get('caja_id'))
                    ->visible(fn (callable $get) => $get('tipo_comprobante') !== 'ticket')
                  //  ->helperText('Escribe para filtrar clientes. Si no hay coincidencias, usa el botón para consultar SUNAT.')
                    ->hintActions([
                        Action::make('reactivarCliente')
                            ->label('✓ Reactivar Cliente')
                            ->color('success')
                            ->icon('heroicon-o-arrow-path')
                            ->visible(fn (callable $get) => !empty($get('cliente_inactivo_encontrado')))
                            ->action(function ($set, $get) {
                                $clienteInactivo = $get('cliente_inactivo_encontrado');

                                if (!$clienteInactivo || !isset($clienteInactivo['id'])) {
                                    Notification::make()
                                        ->title('Error')
                                        ->danger()
                                        ->body('No se encontró información del cliente')
                                        ->send();
                                    return;
                                }

                                try {
                                    $cliente = Cliente::find($clienteInactivo['id']);
                                    if ($cliente) {
                                        $cliente->estado = 'activo';
                                        $cliente->save();

                                        $set('cliente_id', $cliente->id);
                                        $set('cliente_encontrado', [
                                            'tipo_doc' => $cliente->tipo_doc,
                                            'num_doc' => $cliente->num_doc,
                                            'nombre' => $cliente->nombre_razon,
                                            'direccion' => $cliente->direccion,
                                        ]);

                                        $set('cliente_inactivo_encontrado', null);

                                        Notification::make()
                                            ->title('Cliente Reactivado')
                                            ->success()
                                            ->body("El cliente {$cliente->nombre_razon} ha sido reactivado exitosamente")
                                            ->send();
                                    }
                                } catch (\Exception $e) {
                                    Notification::make()
                                        ->title('Error al Reactivar')
                                        ->danger()
                                        ->body('Error: ' . $e->getMessage())
                                        ->send();
                                }
                            }),
                    ])
                    ->getSearchResultsUsing(function (string $search, callable $get, callable $set) {
                        // Guardar lo que está escribiendo en un campo oculto
                        $set('ultima_busqueda_cliente', $search);

                        if (empty($search)) {
                            return [];
                        }

                        $tipoComprobante = $get('tipo_comprobante');

                        // 1) Buscar clientes ACTIVOS (prioridad)
                        $queryActivos = Cliente::where('estado', 'activo');
                        if ($tipoComprobante === 'factura') {
                            $queryActivos->where('tipo_doc', 'RUC');
                        } elseif ($tipoComprobante === 'boleta') {
                            $queryActivos->where('tipo_doc', '!=', 'RUC');
                        }

                        $activos = $queryActivos->where(function ($q) use ($search) {
                            $q->where('num_doc', 'LIKE', "%{$search}%")
                              ->orWhere('nombre_razon', 'LIKE', "%{$search}%");
                        })
                        ->limit(10)
                        ->get();

                        $resultados = $activos->mapWithKeys(function ($cliente) {
                            $label = "<div class='flex flex-col'><span class='font-semibold text-gray-900'>{$cliente->nombre_razon}</span><span class='text-xs text-gray-500'>{$cliente->tipo_doc}: {$cliente->num_doc}</span></div>";
                            return [$cliente->id => $label];
                        })->toArray();

                        // 2) Buscar clientes INACTIVOS (mostrar al final, limitado)
                        $queryInactivos = Cliente::where('estado', 'inactivo');
                        if ($tipoComprobante === 'factura') {
                            $queryInactivos->where('tipo_doc', 'RUC');
                        } elseif ($tipoComprobante === 'boleta') {
                            $queryInactivos->where('tipo_doc', '!=', 'RUC');
                        }

                        $inactivos = $queryInactivos->where(function ($q) use ($search) {
                            $q->where('num_doc', 'LIKE', "%{$search}%")
                              ->orWhere('nombre_razon', 'LIKE', "%{$search}%");
                        })
                        ->limit(5)
                        ->get();

                        foreach ($inactivos as $cliente) {
                            $label = "<div class='flex flex-col'><span class='font-semibold text-red-700'>{$cliente->nombre_razon} <span class=\'text-red-600 font-normal text-sm\'>(INACTIVO)</span></span><span class='text-xs text-gray-500'>{$cliente->tipo_doc}: {$cliente->num_doc}</span></div>";
                            $resultados[$cliente->id] = $label;
                        }

                        return $resultados;
                    })
                    ->getOptionLabelUsing(function ($value) {
                        if (!$value) return '';

                        $cliente = Cliente::find($value);
                        if ($cliente) {
                            return "{$cliente->nombre_razon} - {$cliente->tipo_doc}: {$cliente->num_doc}";
                        }
                        return $value;
                    })
                    ->live()
                    ->afterStateUpdated(function ($state, $set, $get) {
                        // Limpiar si se deselecciona
                        if (empty($state)) {
                            $set('mostrar_formulario_cliente', false);
                            $set('cliente_inactivo_encontrado', null);
                            $set('cliente_inactivo_nombre', null);
                            return;
                        }

                        // Cliente seleccionado del dropdown
                        $cliente = Cliente::find($state);
                        if ($cliente) {
                            if ($cliente->estado === 'inactivo') {
                                // MANTENER cliente_id para que llegue a la validación
                                // NO poner a null
                                $set('cliente_encontrado', [
                                    'tipo_doc' => $cliente->tipo_doc,
                                    'num_doc' => $cliente->num_doc,
                                    'nombre' => $cliente->nombre_razon,
                                    'direccion' => $cliente->direccion,
                                ]);
                                $set('cliente_inactivo_encontrado', [
                                    'id' => $cliente->id,
                                    'tipo_doc' => $cliente->tipo_doc,
                                    'num_doc' => $cliente->num_doc,
                                    'nombre' => $cliente->nombre_razon,
                                    'direccion' => $cliente->direccion,
                                ]);
                                // Valor legible para mostrar en el TextInput de alerta
                                $set('cliente_inactivo_nombre', $cliente->nombre_razon . ' (' . $cliente->tipo_doc . ': ' . $cliente->num_doc . ')');
                                $set('mostrar_formulario_cliente', false);

                                Notification::make()
                                    ->title('Cliente Inactivo')
                                    ->warning()
                                    ->body("{$cliente->nombre_razon} está INACTIVO. Use el botón '✓ Reactivar Cliente' debajo del campo Cliente antes de continuar con la venta.")
                                    ->persistent()
                                    ->send();
                            } else {
                                // Cliente activo seleccionado
                                $set('cliente_id', $cliente->id);
                                $set('cliente_encontrado', [
                                    'tipo_doc' => $cliente->tipo_doc,
                                    'num_doc' => $cliente->num_doc,
                                    'nombre' => $cliente->nombre_razon,
                                    'direccion' => $cliente->direccion,
                                ]);
                                $set('mostrar_formulario_cliente', false);
                                $set('cliente_inactivo_encontrado', null);
                                $set('cliente_inactivo_nombre', null);

                                Notification::make()
                                    ->title('Cliente Seleccionado')
                                    ->success()
                                    ->body("{$cliente->nombre_razon}")
                                    ->send();
                            }
                        }
                    })
                    ->suffixAction(
                        Action::make('consultarAPI')
                            ->label(fn (callable $get) => $get('tipo_comprobante') === 'factura' ? 'Consultar SUNAT' : 'Consultar RENIEC')
                            ->icon('heroicon-o-magnifying-glass-circle')
                            ->color('primary')
                            ->action(function ($get, $set) {
                                // Usar lo que ya escribió en la búsqueda
                                $documentoConsulta = $get('ultima_busqueda_cliente');
                                $tipoComprobante = $get('tipo_comprobante');

                                if (empty($documentoConsulta)) {
                                    Notification::make()
                                        ->title('Campo Vacío')
                                        ->warning()
                                        ->body('Escriba un documento en el campo de búsqueda primero')
                                        ->send();
                                    return;
                                }

                                // Validar según el tipo de comprobante
                                if ($tipoComprobante === 'factura') {
                                    // Para facturas: solo RUC (11 dígitos)
                                    if (!preg_match('/^\d{11}$/', $documentoConsulta)) {
                                        Notification::make()
                                            ->title('RUC Inválido')
                                            ->danger()
                                            ->body('El RUC debe tener exactamente 11 dígitos numéricos')
                                            ->send();
                                        return;
                                    }
                                } elseif ($tipoComprobante === 'boleta') {
                                    // Para boletas: solo DNI (8 dígitos)
                                    if (!preg_match('/^\d{8}$/', $documentoConsulta)) {
                                        Notification::make()
                                            ->title('DNI Inválido')
                                            ->danger()
                                            ->body('El DNI debe tener exactamente 8 dígitos numéricos')
                                            ->send();
                                        return;
                                    }
                                }

                                // Verificar primero si ya existe
                                $clienteExistente = Cliente::where('num_doc', $documentoConsulta)->first();

                                if ($clienteExistente) {
                                    if ($clienteExistente->estado === 'activo') {
                                        $set('cliente_id', $clienteExistente->id);
                                        $set('cliente_encontrado', [
                                            'tipo_doc' => $clienteExistente->tipo_doc,
                                            'num_doc' => $clienteExistente->num_doc,
                                            'nombre' => $clienteExistente->nombre_razon,
                                            'direccion' => $clienteExistente->direccion,
                                        ]);

                                        Notification::make()
                                            ->title('Cliente Encontrado Localmente')
                                            ->success()
                                            ->body($clienteExistente->nombre_razon)
                                            ->send();
                                        return;
                                    } else {
                                        $set('cliente_id', null);
                                        $set('cliente_inactivo_encontrado', [
                                            'id' => $clienteExistente->id,
                                            'tipo_doc' => $clienteExistente->tipo_doc,
                                            'num_doc' => $clienteExistente->num_doc,
                                            'nombre' => $clienteExistente->nombre_razon,
                                            'direccion' => $clienteExistente->direccion,
                                        ]);
                                        $set('cliente_encontrado', [
                                            'tipo_doc' => $clienteExistente->tipo_doc,
                                            'num_doc' => $clienteExistente->num_doc,
                                            'nombre' => $clienteExistente->nombre_razon,
                                            'direccion' => $clienteExistente->direccion,
                                        ]);

                                        Notification::make()
                                            ->title('Cliente Inactivo')
                                            ->warning()
                                            ->body("Presione 'Reactivar' para continuar")
                                            ->persistent()
                                            ->send();
                                        return;
                                    }
                                }

                                // No existe - Consultar API
                                if ($tipoComprobante === 'factura') {
                                    // Consultar SUNAT para RUC
                                    Notification::make()
                                        ->title('Consultando SUNAT...')
                                        ->info()
                                        ->body("Buscando RUC {$documentoConsulta}")
                                        ->send();

                                    try {
                                        $apisNetService = app(ApisNetPeService::class);
                                        $response = $apisNetService->consultarRuc($documentoConsulta);

                                        if (isset($response['message']) && $response['message'] === 'not found') {
                                            $set('mostrar_formulario_cliente', true);
                                            $set('nuevo_cliente_tipo_doc', 'RUC');
                                            $set('nuevo_cliente_num_doc', $documentoConsulta);

                                            Notification::make()
                                                ->title('RUC No Encontrado en SUNAT')
                                                ->warning()
                                                ->body('Complete los datos manualmente.')
                                                ->send();
                                            return;
                                        }

                                        if (isset($response['razon_social']) || isset($response['razonSocial']) || isset($response['data']['razon_social'])) {
                                            $data = $response['data'] ?? $response;
                                            $razonSocial = $data['razon_social'] ?? $data['razonSocial'] ?? '';
                                            $direccion = $data['direccion'] ?? '-';

                                            if ($razonSocial) {
                                                $nuevoCliente = Cliente::create([
                                                    'tipo_doc' => 'RUC',
                                                    'num_doc' => $documentoConsulta,
                                                    'nombre_razon' => $razonSocial,
                                                    'direccion' => $direccion,
                                                    'estado' => 'activo',
                                                ]);

                                                $set('cliente_id', $nuevoCliente->id);
                                                $set('cliente_encontrado', [
                                                    'tipo_doc' => 'RUC',
                                                    'num_doc' => $documentoConsulta,
                                                    'nombre' => $razonSocial,
                                                    'direccion' => $direccion,
                                                ]);
                                                $set('mostrar_formulario_cliente', false);

                                                Notification::make()
                                                    ->title('Cliente Registrado desde SUNAT')
                                                    ->success()
                                                    ->body($razonSocial)
                                                    ->duration(5000)
                                                    ->send();
                                            }
                                        }
                                    } catch (\Exception $e) {
                                        Notification::make()
                                            ->title('Error al Consultar SUNAT')
                                            ->danger()
                                            ->body($e->getMessage())
                                            ->send();

                                        $set('mostrar_formulario_cliente', true);
                                        $set('nuevo_cliente_tipo_doc', 'RUC');
                                        $set('nuevo_cliente_num_doc', $documentoConsulta);
                                    }
                                } elseif ($tipoComprobante === 'boleta') {
                                    // Consultar RENIEC para DNI
                                    Notification::make()
                                        ->title('Consultando RENIEC...')
                                        ->info()
                                        ->body("Buscando DNI {$documentoConsulta}")
                                        ->send();

                                    try {
                                        $apisNetService = app(ApisNetPeService::class);
                                        $response = $apisNetService->consultarDni($documentoConsulta);

                                        if (isset($response['message']) && $response['message'] === 'not found') {
                                            $set('mostrar_formulario_cliente', true);
                                            $set('nuevo_cliente_tipo_doc', 'DNI');
                                            $set('nuevo_cliente_num_doc', $documentoConsulta);

                                            Notification::make()
                                                ->title('DNI No Encontrado en RENIEC')
                                                ->warning()
                                                ->body('Complete los datos manualmente.')
                                                ->send();
                                            return;
                                        }

                                        // Buscar datos de la persona - probar diferentes formatos de respuesta
                                        $data = $response['data'] ?? $response;

                                        // Intentar diferentes variaciones de nombres de campos (APIs Perú)
                                        $nombres = $data['nombres'] ?? $data['name'] ?? $data['primer_nombre'] ?? $data['nombre'] ?? '';
                                        $apellidoPaterno = $data['apellido_paterno'] ?? $data['apellidoPaterno'] ?? $data['paternal_surname'] ?? $data['apellidoP'] ?? '';
                                        $apellidoMaterno = $data['apellido_materno'] ?? $data['apellidoMaterno'] ?? $data['maternal_surname'] ?? $data['apellidoM'] ?? '';

                                        // Si viene el nombre completo en un solo campo
                                        $nombreCompletoDirecto = $data['nombre_completo'] ?? $data['nombreCompleto'] ?? $data['full_name'] ?? '';

                                        // Construir nombre completo
                                        if (!empty($nombreCompletoDirecto)) {
                                            $nombreCompleto = trim($nombreCompletoDirecto);
                                        } else {
                                            $nombreCompleto = trim("{$apellidoPaterno} {$apellidoMaterno} {$nombres}");
                                        }

                                        if (!empty($nombreCompleto) && strlen($nombreCompleto) > 3) {
                                            $nuevoCliente = Cliente::create([
                                                'tipo_doc' => 'DNI',
                                                'num_doc' => $documentoConsulta,
                                                'nombre_razon' => $nombreCompleto,
                                                'direccion' => '-',
                                                'estado' => 'activo',
                                            ]);

                                            $set('cliente_id', $nuevoCliente->id);
                                            $set('cliente_encontrado', [
                                                'tipo_doc' => 'DNI',
                                                'num_doc' => $documentoConsulta,
                                                'nombre' => $nombreCompleto,
                                                'direccion' => '-',
                                            ]);
                                            $set('mostrar_formulario_cliente', false);

                                            Notification::make()
                                                ->title('Cliente Registrado desde RENIEC')
                                                ->success()
                                                ->body($nombreCompleto)
                                                ->duration(5000)
                                                ->send();
                                        } else {
                                            // No se pudo obtener el nombre
                                            $set('mostrar_formulario_cliente', true);
                                            $set('nuevo_cliente_tipo_doc', 'DNI');
                                            $set('nuevo_cliente_num_doc', $documentoConsulta);

                                            Notification::make()
                                                ->title('Datos Incompletos')
                                                ->warning()
                                                ->body('Complete los datos manualmente.')
                                                ->send();
                                        }
                                    } catch (\Exception $e) {
                                        Notification::make()
                                            ->title('Error al Consultar RENIEC')
                                            ->danger()
                                            ->body($e->getMessage())
                                            ->send();

                                        $set('mostrar_formulario_cliente', true);
                                        $set('nuevo_cliente_tipo_doc', 'DNI');
                                        $set('nuevo_cliente_num_doc', $documentoConsulta);
                                    }
                                }
                            })
                            ->tooltip(fn (callable $get) => $get('tipo_comprobante') === 'factura' ? 'Consultar RUC en SUNAT' : 'Consultar DNI en RENIEC')
                    ),

                // Campos de control (no se guardan en BD)
                Hidden::make('mostrar_formulario_cliente')
                    ->default(false)
                    ->dehydrated(false),

                Hidden::make('cliente_encontrado')
                    ->dehydrated(false),

                Hidden::make('cliente_inactivo_encontrado')
                    ->dehydrated(false),

                // Campo oculto para guardar la última búsqueda del usuario
                Hidden::make('ultima_busqueda_cliente')
                    ->dehydrated(false),

                // Campo eliminado: "Cliente Encontrado" - redundante ya que el Select muestra la info completa

                // Nombre rápido para TICKET (no se persiste en clientes)
                TextInput::make('cliente_ticket_nombre')
                    ->label('Nombre para ticket')
                  //  ->placeholder('Escriba el nombre que saldrá en el ticket')
                    ->visible(fn (callable $get) => $get('tipo_comprobante') === 'ticket')
                    ->columnSpan(1),

                // Formulario para crear nuevo cliente (aparece solo si no se encontró)
                Select::make('nuevo_cliente_tipo_doc')
                    ->label('Tipo de Documento')
                    ->options([
                        'DNI' => 'DNI',
                        'RUC' => 'RUC',
                    ])
                    ->dehydrated(false)
                    ->visible(fn (callable $get) => $get('mostrar_formulario_cliente') === true && $get('tipo_comprobante') !== 'ticket')
                    ->live(),

                TextInput::make('nuevo_cliente_num_doc')
                    ->label('Número de Documento')
                    ->dehydrated(false)
                    ->visible(fn (callable $get) => $get('mostrar_formulario_cliente') === true && $get('tipo_comprobante') !== 'ticket')
                    ->maxLength(20)
                    ->suffixAction(
                        Action::make('consultarAPI')
                            ->label('Consultar API')
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

                                $tipoDoc = $get('nuevo_cliente_tipo_doc');
                                if (!$tipoDoc) {
                                    Notification::make()
                                        ->title('Error')
                                        ->danger()
                                        ->body('Debe seleccionar el tipo de documento primero')
                                        ->send();
                                    return;
                                }

                                // Validar formato
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

                                Notification::make()
                                    ->title('Consultando...')
                                    ->info()
                                    ->body('Consultando documento en SUNAT/RENIEC...')
                                    ->send();

                                try {
                                    $apisNetService = app(ApisNetPeService::class);

                                    if ($tipoDoc === 'DNI') {
                                        $response = $apisNetService->consultarDni($state);

                                        if (isset($response['message']) && $response['message'] === 'not found') {
                                            Notification::make()
                                                ->title('DNI No Encontrado')
                                                ->warning()
                                                ->body('No se encontraron datos. Ingrese manualmente.')
                                                ->send();
                                            return;
                                        }

                                        if (isset($response['full_name']) || isset($response['first_name']) || isset($response['nombres'])) {
                                            $data = $response['data'] ?? $response;

                                            if (isset($data['full_name'])) {
                                                $nombreCompleto = trim($data['full_name']);
                                            } else {
                                                $nombreCompleto = trim(
                                                    ($data['first_name'] ?? $data['nombres'] ?? '') . ' ' .
                                                    ($data['first_last_name'] ?? $data['apellidoPaterno'] ?? '') . ' ' .
                                                    ($data['second_last_name'] ?? $data['apellidoMaterno'] ?? '')
                                                );
                                            }

                                            if ($nombreCompleto) {
                                                $set('nuevo_cliente_nombre', $nombreCompleto);
                                                $set('nuevo_cliente_direccion', $data['direccion'] ?? '-');

                                                Notification::make()
                                                    ->title('Consulta Exitosa')
                                                    ->success()
                                                    ->body('Datos encontrados: ' . $nombreCompleto)
                                                    ->send();
                                            }
                                        }
                                    } elseif ($tipoDoc === 'RUC') {
                                        $response = $apisNetService->consultarRuc($state);

                                        if (isset($response['message']) && $response['message'] === 'not found') {
                                            Notification::make()
                                                ->title('RUC No Encontrado')
                                                ->warning()
                                                ->body('No se encontraron datos. Ingrese manualmente.')
                                                ->send();
                                            return;
                                        }

                                        if (isset($response['razon_social']) || isset($response['razonSocial']) || isset($response['data']['razon_social'])) {
                                            $data = $response['data'] ?? $response;
                                            $razonSocial = $data['razon_social'] ?? $data['razonSocial'] ?? '';

                                            if ($razonSocial) {
                                                $set('nuevo_cliente_nombre', $razonSocial);
                                                $set('nuevo_cliente_direccion', $data['direccion'] ?? '-');

                                                Notification::make()
                                                    ->title('Consulta Exitosa')
                                                    ->success()
                                                    ->body('Empresa encontrada: ' . $razonSocial)
                                                    ->send();
                                            }
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
                            ->tooltip('Consultar en SUNAT/RENIEC')
                    ),

                TextInput::make('nuevo_cliente_nombre')
                    ->label('Nombre / Razón Social')
                    ->placeholder('Ingrese el nombre completo...')
                    ->maxLength(255)
                    ->dehydrated(false)
                    ->visible(fn (callable $get) => $get('mostrar_formulario_cliente') === true && $get('tipo_comprobante') !== 'ticket'),

                TextInput::make('nuevo_cliente_direccion')
                    ->label('Dirección (Opcional)')
                    ->placeholder('Ingrese la dirección...')
                    ->default('-')
                    ->maxLength(500)
                    ->dehydrated(false)
                    ->visible(fn (callable $get) => $get('mostrar_formulario_cliente') === true && $get('tipo_comprobante') !== 'ticket')
                    ->hintActions([
                        Action::make('guardarNuevoCliente')
                            ->label('✓ Guardar Cliente')
                            ->color('success')
                            ->action(function ($set, $get) {
                                $tipoDoc = $get('nuevo_cliente_tipo_doc');
                                $numDoc = $get('nuevo_cliente_num_doc');
                                $nombre = $get('nuevo_cliente_nombre');
                                $direccion = $get('nuevo_cliente_direccion') ?? '-';
                                $tipoComprobante = $get('tipo_comprobante');

                                // Validaciones
                                if ($tipoComprobante !== 'ticket') {
                                    if (!$tipoDoc || !$numDoc) {
                                        Notification::make()
                                            ->title('Datos Incompletos')
                                            ->danger()
                                            ->body('Debe completar el tipo y número de documento')
                                            ->send();
                                        return;
                                    }
                                }

                                if (!$nombre) {
                                    Notification::make()
                                        ->title('Datos Incompletos')
                                        ->danger()
                                        ->body('Debe ingresar el nombre del cliente')
                                        ->send();
                                    return;
                                }

                                try {
                                    // VALIDAR que no exista el documento (ni activo ni inactivo)
                                    if ($numDoc) {
                                        $clienteExistente = Cliente::where('num_doc', $numDoc)->first();

                                        if ($clienteExistente) {
                                            if ($clienteExistente->estado === 'inactivo') {
                                                Notification::make()
                                                    ->title('Cliente ya existe (INACTIVO)')
                                                    ->warning()
                                                    ->body("El documento {$numDoc} pertenece a un cliente inactivo: {$clienteExistente->nombre_razon}. Busque el documento nuevamente y use el botón 'Reactivar'.")
                                                    ->persistent()
                                                    ->send();
                                                return;
                                            } else {
                                                Notification::make()
                                                    ->title('Cliente ya existe')
                                                    ->danger()
                                                    ->body("El documento {$numDoc} ya está registrado: {$clienteExistente->nombre_razon}")
                                                    ->send();
                                                return;
                                            }
                                        }
                                    }

                                    // Crear el cliente en la base de datos
                                    $nuevoCliente = Cliente::create([
                                        'tipo_doc' => $tipoDoc ?? 'SIN_DOC',
                                        'num_doc' => $numDoc ?? '',
                                        'nombre_razon' => $nombre,
                                        'direccion' => $direccion,
                                        'tipo_cliente' => $tipoDoc === 'RUC' ? 'juridica' : 'natural',
                                        'estado' => 'activo',
                                        'fecha_registro' => now(),
                                    ]);

                                    // Asignar el cliente recién creado
                                    $set('cliente_id', $nuevoCliente->id);
                                    $set('cliente_encontrado', [
                                        'tipo_doc' => $nuevoCliente->tipo_doc,
                                        'num_doc' => $nuevoCliente->num_doc,
                                        'nombre' => $nuevoCliente->nombre_razon,
                                        'direccion' => $nuevoCliente->direccion,
                                    ]);

                                    // Limpiar formulario de nuevo cliente
                                    $set('mostrar_formulario_cliente', false);
                                    $set('nuevo_cliente_tipo_doc', null);
                                    $set('nuevo_cliente_num_doc', null);
                                    $set('nuevo_cliente_nombre', null);
                                    $set('nuevo_cliente_direccion', null);

                                    Notification::make()
                                        ->title('Cliente Registrado')
                                        ->success()
                                        ->body("Cliente {$nombre} registrado correctamente")
                                        ->send();

                                } catch (\Exception $e) {
                                    Notification::make()
                                        ->title('Error al Guardar')
                                        ->danger()
                                        ->body('Error: ' . $e->getMessage())
                                        ->send();
                                }
                            }),

                        Action::make('cancelarNuevoCliente')
                            ->label('✗ Cancelar')
                            ->color('danger')
                            ->action(function ($set) {
                                $set('mostrar_formulario_cliente', false);
                                $set('nuevo_cliente_tipo_doc', null);
                                $set('nuevo_cliente_num_doc', null);
                                $set('nuevo_cliente_nombre', null);
                                $set('nuevo_cliente_direccion', null);
                                $set('buscar_cliente', null);
                            }),
                    ]),

                // === DETALLES DE LA VENTA (PRODUCTOS) ===
                // Motivo de Anulación (solo lectura) - mostrado antes de los productos
                Textarea::make('motivo_anulacion')
                    ->label('Motivo de Anulación')
                    ->rows(1)
                    ->disabled()
                    ->dehydrated(false)
                    ->visible(fn (callable $get) => !empty($get('motivo_anulacion')))
                    ->extraAttributes(['style' => 'color:#b91c1c; width:100%; max-width:100%; font-weight:300; font-size:12px; line-height:1.1; border-left:4px solid #ef4444; padding:4px 6px; background:#fff7f6; margin-bottom:6px; max-height:36px; overflow:hidden;'])
                    ->columnSpan(2),

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
                    ->addActionLabel('+ Agregar Producto')
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
                      //  'transferencia' => 'Transferencia Bancaria',
                    ])
                    ->required()
                    ->live()
                    ->default('efectivo')
                    ->disabled(fn (callable $get) => self::shouldDisableFields() || !$get('caja_id')) // Deshabilitado si hay caja anterior o no hay caja
                    ->helperText(fn (callable $get) => !$get('caja_id') ? 'Primero debe seleccionar una caja' : ''),

                TextInput::make('cod_operacion')
                    ->label('Código de Operación / Transacción')
                    ->maxLength(100)
                    ->visible(fn (callable $get) => in_array($get('metodo_pago'), ['tarjeta','yape','plin']))
                    ->required(fn (callable $get) => in_array($get('metodo_pago'), ['tarjeta','yape','plin']))
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
