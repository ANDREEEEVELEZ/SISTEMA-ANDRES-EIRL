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
                            // Si cambia a RUC, limpiar el tipo_cliente para que seleccione
                            $set('tipo_cliente', null);
                        }
                    })
                    ->required(),

                Select::make('tipo_cliente')
                    ->label('Tipo de Cliente')
                    ->options([
                        'natural' => 'Persona Natural',
                        'natural_con_negocio' => 'Persona Natural con Negocio (RUC 10)',
                        'juridica' => 'Persona Jurídica/Empresa (RUC 20)',
                    ])
                    ->prefixIcon('heroicon-o-user-group')
                    ->visible(fn (callable $get) => $get('tipo_doc') === 'RUC') // Solo visible con RUC
                    ->disabled(fn (callable $get) => $get('tipo_doc') === 'DNI') // Deshabilitado con DNI
                    ->default(fn (callable $get) => $get('tipo_doc') === 'DNI' ? 'natural' : null)
                    ->required()
                    ->helperText(fn (callable $get) =>
                        $get('tipo_doc') === 'DNI'
                            ? 'Con DNI siempre es Persona Natural'
                            : 'Seleccione el tipo de cliente con RUC'
                    )
                    ->live(),

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

                                                Notification::make()
                                                    ->title('✅ Datos encontrados')
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
                                        // Para RUC
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

                                        // Verificar diferentes estructuras de respuesta posibles
                                        if (isset($response['business_name']) || isset($response['razonSocial']) || isset($response['nombre'])) {
                                            $data = $response['data'] ?? $response;

                                            // Priorizar business_name si existe (Decolecta API)
                                            $razonSocial = $data['business_name'] ?? $data['razonSocial'] ?? $data['nombre'] ?? '';
                                            $direccion = $data['address'] ?? $data['direccion'] ?? '';

                                            if ($razonSocial) {
                                                $set('nombre_razon', $razonSocial);
                                                if ($direccion) {
                                                    $set('direccion', $direccion);
                                                }

                                                Notification::make()
                                                    ->title('✅ Datos encontrados')
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
                    ->prefixIcon('heroicon-o-calendar')
                    ->default(now()),

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
                    ->numeric()
                    ->length(9),
                TextInput::make('direccion')
                    ->label('Dirección')
                    ->prefixIcon('heroicon-o-map-pin'),
            ]);
    }
}
