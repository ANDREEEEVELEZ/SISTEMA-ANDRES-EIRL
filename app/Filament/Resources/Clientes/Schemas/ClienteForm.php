<?php

namespace App\Filament\Resources\Clientes\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use App\Services\ApisNetPeService;

class ClienteForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('tipo_doc')
                    ->label('Tipo de Documento')
                    ->options(['DNI' => 'DNI', 'RUC' => 'RUC'])
                    ->prefixIcon('heroicon-o-identification')
                    ->live() // Hace que el campo sea reactivo
                    ->afterStateUpdated(function ($state, callable $set) {
                        // Si selecciona DNI, tipo_cliente es automáticamente 'natural'
                        if ($state === 'DNI') {
                            $set('tipo_cliente', 'natural');
                        } else {
                            // Si cambia a RUC, limpiar el tipo_cliente para que se determine automáticamente
                            $set('tipo_cliente', null);
                        }
                    })
                    ->required(),

                // Campo oculto que se asigna automáticamente según el tipo de documento
                Select::make('tipo_cliente')
                    ->label('Tipo de Cliente')
                    ->options([
                        'natural' => 'Persona Natural',
                        'natural_con_negocio' => 'Persona Natural con Negocio (RUC 10)',
                        'juridica' => 'Persona Jurídica (RUC 20)',
                    ])
                    ->hidden() // Oculto completamente para el usuario
                    ->default(fn (callable $get) => $get('tipo_doc') === 'DNI' ? 'natural' : null)
                    ->dehydrated(), // Asegurar que se guarde aunque esté oculto

                TextInput::make('num_doc')
                    ->label('Número de Documento')
                    ->prefixIcon('heroicon-o-hashtag')
                    ->required()
                    ->live()
                    ->maxLength(fn (callable $get) => $get('tipo_doc') ? ($get('tipo_doc') === 'DNI' ? 8 : 11) : 11)
                    ->minLength(fn (callable $get) => $get('tipo_doc') ? ($get('tipo_doc') === 'DNI' ? 8 : 11) : 8)
                    ->length(fn (callable $get) => $get('tipo_doc') ? ($get('tipo_doc') === 'DNI' ? 8 : 11) : null)
                    ->placeholder(fn (callable $get) => $get('tipo_doc') ? ($get('tipo_doc') === 'DNI' ? 'Ingrese 8 dígitos' : 'Ingrese 11 dígitos') : 'Seleccione tipo de documento primero')
                    ->regex('/^[0-9]+$/')
                    ->validationMessages([
                        'regex' => 'El documento debe contener solo números.',
                    ])
                    ->extraAttributes([
                        'oninput' => 'this.value = this.value.replace(/[^0-9]/g, "").substring(0, this.getAttribute("maxlength"))',
                        'onkeypress' => 'return event.charCode >= 48 && event.charCode <= 57',
                        'inputmode' => 'numeric',
                    ])
                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                        // Auto-determinar tipo_cliente basado en el RUC
                        if ($get('tipo_doc') === 'RUC' && strlen($state) >= 2) {
                            $prefijo = substr($state, 0, 2);
                            if ($prefijo === '10') {
                                $set('tipo_cliente', 'natural_con_negocio');
                            } elseif ($prefijo === '20') {
                                $set('tipo_cliente', 'juridica');
                            }
                        }
                    })
                    ->suffixAction(
                        Action::make('buscarDocumento')
                            ->icon('heroicon-o-magnifying-glass')
                            ->label('Buscar')
                            ->action(function (callable $set, callable $get, $state) {
                                $tipoDoc = $get('tipo_doc');
                                $numDoc = $state;

                                if (!$tipoDoc || !$numDoc) {
                                    Notification::make()
                                        ->title('Error')
                                        ->body('Debe seleccionar el tipo de documento e ingresar el número.')
                                        ->danger()
                                        ->send();
                                    return;
                                }

                                // Validar longitud
                                $longitudEsperada = $tipoDoc === 'DNI' ? 8 : 11;
                                if (strlen($numDoc) !== $longitudEsperada) {
                                    Notification::make()
                                        ->title('Error')
                                        ->body("El {$tipoDoc} debe tener exactamente {$longitudEsperada} dígitos.")
                                        ->danger()
                                        ->send();
                                    return;
                                }

                                // Consultar API directamente
                                try {
                                    $apiService = app(ApisNetPeService::class);
                                    $data = null;

                                    if ($tipoDoc === 'DNI') {
                                        $response = $apiService->consultarDni($numDoc);

                                        // Verificar si hay mensaje de error
                                        if (isset($response['message'])) {
                                            if ($response['message'] === 'not found') {
                                                Notification::make()
                                                    ->title('DNI No Encontrado')
                                                    ->body('No se encontraron datos para el DNI: ' . $numDoc)
                                                    ->warning()
                                                    ->send();
                                                return;
                                            } else {
                                                Notification::make()
                                                    ->title('Error de API')
                                                    ->body('Error: ' . $response['message'])
                                                    ->danger()
                                                    ->send();
                                                return;
                                            }
                                        }

                                        if (isset($response['error'])) {
                                            Notification::make()
                                                ->title('Error de Consulta')
                                                ->body('Error al consultar DNI: ' . $response['error'])
                                                ->danger()
                                                ->send();
                                            return;
                                        }

                                        // Verificar diferentes estructuras de respuesta posibles (como en VentaForm)
                                        if (isset($response['full_name']) || isset($response['first_name']) || isset($response['nombres']) || isset($response['nombre_completo']) || isset($response['nombreCompleto'])) {
                                            $data = $response['data'] ?? $response;

                                            // Intentar diferentes variaciones de nombres de campos
                                            $nombres = $data['nombres'] ?? $data['name'] ?? $data['first_name'] ?? $data['primer_nombre'] ?? $data['nombre'] ?? '';
                                            $apellidoPaterno = $data['apellido_paterno'] ?? $data['apellidoPaterno'] ?? $data['first_last_name'] ?? $data['paternal_surname'] ?? $data['apellidoP'] ?? '';
                                            $apellidoMaterno = $data['apellido_materno'] ?? $data['apellidoMaterno'] ?? $data['second_last_name'] ?? $data['maternal_surname'] ?? $data['apellidoM'] ?? '';

                                            // Si viene el nombre completo en un solo campo
                                            $nombreCompletoDirecto = $data['full_name'] ?? $data['nombre_completo'] ?? $data['nombreCompleto'] ?? '';

                                            // Construir nombre completo
                                            if (!empty($nombreCompletoDirecto)) {
                                                $nombreCompleto = trim($nombreCompletoDirecto);
                                            } else {
                                                $nombreCompleto = trim("{$apellidoPaterno} {$apellidoMaterno} {$nombres}");
                                            }

                                            if ($nombreCompleto && strlen($nombreCompleto) > 3) {
                                                $set('nombre_razon', $nombreCompleto);
                                                Notification::make()
                                                    ->title('Datos encontrados')
                                                    ->body('Información completada: ' . $nombreCompleto)
                                                    ->success()
                                                    ->send();
                                            } else {
                                                Notification::make()
                                                    ->title('Datos Incompletos')
                                                    ->body('La API devolvió datos pero están incompletos')
                                                    ->warning()
                                                    ->send();
                                            }
                                        } else {
                                            Notification::make()
                                                ->title('DNI No Encontrado')
                                                ->body('No se encontraron datos para el DNI: ' . $numDoc)
                                                ->warning()
                                                ->send();
                                        }

                                    } else {
                                        // Para RUC - Auto-determinar tipo_cliente basado en el prefijo
                                        $prefijo = substr($numDoc, 0, 2);
                                        if ($prefijo === '10') {
                                            $set('tipo_cliente', 'natural_con_negocio');
                                        } elseif ($prefijo === '20') {
                                            $set('tipo_cliente', 'juridica');
                                        }

                                        $response = $apiService->consultarRuc($numDoc);

                                        // Verificar si hay mensaje de error
                                        if (isset($response['message'])) {
                                            if ($response['message'] === 'not found') {
                                                Notification::make()
                                                    ->title('RUC No Encontrado')
                                                    ->body('No se encontraron datos para el RUC: ' . $numDoc)
                                                    ->warning()
                                                    ->send();
                                                return;
                                            } else {
                                                Notification::make()
                                                    ->title('Error de API')
                                                    ->body('Error: ' . $response['message'])
                                                    ->danger()
                                                    ->send();
                                                return;
                                            }
                                        }

                                        if (isset($response['error'])) {
                                            Notification::make()
                                                ->title('Error de Consulta')
                                                ->body('Error al consultar RUC: ' . $response['error'])
                                                ->danger()
                                                ->send();
                                            return;
                                        }

                                        // Verificar diferentes estructuras de respuesta posibles (igual que en VentaForm)
                                        if (isset($response['razon_social']) || isset($response['razonSocial']) || isset($response['data']['razon_social']) || isset($response['business_name']) || isset($response['nombre'])) {
                                            $data = $response['data'] ?? $response;

                                            // Priorizar razon_social (como en Ventas), luego business_name
                                            $razonSocial = $data['razon_social'] ?? $data['razonSocial'] ?? $data['business_name'] ?? $data['nombre'] ?? '';
                                            $direccion = $data['direccion'] ?? $data['address'] ?? '';

                                            if ($razonSocial) {
                                                $set('nombre_razon', $razonSocial);
                                                if ($direccion) {
                                                    $set('direccion', $direccion);
                                                }

                                                Notification::make()
                                                    ->title('Datos encontrados')
                                                    ->body('Información completada: ' . $razonSocial)
                                                    ->success()
                                                    ->send();
                                            } else {
                                                Notification::make()
                                                    ->title('Datos Incompletos')
                                                    ->body('La API devolvió datos pero están incompletos')
                                                    ->warning()
                                                    ->send();
                                            }
                                        } else {
                                            Notification::make()
                                                ->title('RUC No Encontrado')
                                                ->body('No se encontraron datos para el RUC: ' . $numDoc)
                                                ->warning()
                                                ->send();
                                        }
                                    }

                                } catch (\Exception $e) {
                                    Notification::make()
                                        ->title('Error')
                                        ->body('Ocurrió un error al consultar: ' . $e->getMessage())
                                        ->danger()
                                        ->send();
                                }
                            })
                    )
                    ->columnSpan(1),
                    //->helperText(fn (callable $get) => $get('tipo_doc') ? ($get('tipo_doc') === 'DNI' ? 'DNI debe tener exactamente 8 dígitos' : 'RUC debe tener exactamente 11 dígitos') : 'Primero seleccione el tipo de documento'),

                TextInput::make('nombre_razon')
                    ->label('Nombre o Razón Social')
                    ->prefixIcon('heroicon-o-user-circle')
                    ->required(),
                DatePicker::make('fecha_registro')
                    ->label('Fecha de Registro')
                    ->prefixIcon('heroicon-o-calendar')
                    ->default(now())
                    ->disabled()
                    ->dehydrated(),

                Select::make('estado')
                    ->label('Estado')
                    ->options(['activo' => 'Activo', 'inactivo' => 'Inactivo'])
                    ->prefixIcon('heroicon-o-check-circle')
                    ->default('activo')
                    ->required()
                    ->disabled()
                    ->dehydrated(),

                TextInput::make('telefono')
                    ->label('Teléfono')
                    ->prefixIcon('heroicon-o-phone')
                    ->tel()
                    ->maxLength(9)
                    ->minLength(9)
                    ->length(9)
                    ->placeholder('Ingrese 9 dígitos')
                    ->regex('/^[0-9]{9}$/')
                    ->validationMessages([
                        'regex' => 'El teléfono debe contener exactamente 9 dígitos numéricos.',
                        'min' => 'El teléfono debe tener exactamente 9 dígitos.',
                        'max' => 'El teléfono debe tener exactamente 9 dígitos.',
                    ])
                    ->extraAttributes([
                        'oninput' => 'this.value = this.value.replace(/[^0-9]/g, "").substring(0, 9)',
                        'onkeypress' => 'return event.charCode >= 48 && event.charCode <= 57',
                        'onpaste' => 'return false',
                        'inputmode' => 'numeric',
                        'pattern' => '[0-9]{9}',
                    ]),
                    //->helperText('Debe contener exactamente 9 dígitos numéricos'),
                TextInput::make('direccion')
                    ->label('Dirección')
                    ->prefixIcon('heroicon-o-map-pin'),
            ]);
    }
}
