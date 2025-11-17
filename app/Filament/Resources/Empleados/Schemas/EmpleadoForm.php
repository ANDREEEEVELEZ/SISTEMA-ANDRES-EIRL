<?php

namespace App\Filament\Resources\Empleados\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ViewField;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;

class EmpleadoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                // === DATOS PERSONALES ===
                TextInput::make('nombres')
                    ->label('Nombres')
                    ->required()
                    ->maxLength(100)
                    ->placeholder('Ingrese solo letras')
                    ->regex('/^[a-zA-ZÀ-ÿñÑ\s]+$/u')
                    ->validationMessages([
                        'regex' => 'Los nombres solo pueden contener letras y espacios.',
                    ])
                    ->extraAttributes([
                        'oninput' => 'this.value = this.value.replace(/[^a-zA-ZÀ-ÿÑñáéíóúÁÉÍÓÚüÜ\s]/g, "")',
                        'onkeypress' => 'return (event.charCode >= 65 && event.charCode <= 90) || (event.charCode >= 97 && event.charCode <= 122) || event.charCode === 32 || event.charCode >= 192',
                    ]),
                
                TextInput::make('apellidos')
                    ->label('Apellidos')
                    ->required()
                    ->maxLength(100)
                    ->placeholder('Ingrese solo letras')
                    ->regex('/^[a-zA-ZÀ-ÿñÑ\s]+$/u')
                    ->validationMessages([
                        'regex' => 'Los apellidos solo pueden contener letras y espacios.',
                    ])
                    ->extraAttributes([
                        'oninput' => 'this.value = this.value.replace(/[^a-zA-ZÀ-ÿÑñáéíóúÁÉÍÓÚüÜ\s]/g, "")',
                        'onkeypress' => 'return (event.charCode >= 65 && event.charCode <= 90) || (event.charCode >= 97 && event.charCode <= 122) || event.charCode === 32 || event.charCode >= 192',
                    ]),
                
                TextInput::make('dni')
                    ->label('DNI')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(8)
                    ->minLength(8)
                    ->length(8)
                    ->placeholder('Ingrese 8 dígitos')
                    ->regex('/^[0-9]{8}$/')
                    ->validationMessages([
                        'regex' => 'El DNI debe contener exactamente 8 dígitos numéricos.',
                        'min' => 'El DNI debe tener exactamente 8 dígitos.',
                        'max' => 'El DNI debe tener exactamente 8 dígitos.',
                    ])
                    ->extraAttributes([
                        'oninput' => 'this.value = this.value.replace(/[^0-9]/g, "").substring(0, 8)',
                        'onkeypress' => 'return event.charCode >= 48 && event.charCode <= 57',
                        'inputmode' => 'numeric',
                        'pattern' => '[0-9]{8}',
                    ]),
                
                TextInput::make('telefono')
                    ->label('Teléfono')
                    ->tel()
                    ->maxLength(9)
                    ->placeholder('Ingrese máximo 9 dígitos')
                    ->regex('/^[0-9]{1,9}$/')
                    ->validationMessages([
                        'regex' => 'El teléfono debe contener solo números (máximo 9 dígitos).',
                        'max' => 'El teléfono no puede tener más de 9 dígitos.',
                    ])
                    ->extraAttributes([
                        'oninput' => 'this.value = this.value.replace(/[^0-9]/g, "").substring(0, 9)',
                        'onkeypress' => 'return event.charCode >= 48 && event.charCode <= 57',
                        'onpaste' => 'return false',
                        'inputmode' => 'numeric',
                        'pattern' => '[0-9]{1,9}',
                    ]),
                
                TextInput::make('direccion')
                    ->label('Dirección')
                    ->maxLength(255)
                    ->placeholder('Ingrese la dirección completa'),
                
                DatePicker::make('fecha_nacimiento')
                    ->label('Fecha de Nacimiento')
                    ->required()
                    ->native(false)
                    ->displayFormat('d/m/Y')
                    ->placeholder('Seleccione fecha de nacimiento'),
                
                TextInput::make('correo_empleado')
                    ->label('Correo Electrónico')
                    ->email()
                    ->required()
                    ->maxLength(100)
                    ->placeholder('ejemplo@correo.com')
                    ->validationAttribute('correo electrónico')
                    ->rules([
                        function ($record): \Closure {
                            return function (string $attribute, $value, \Closure $fail) use ($record) {
                                // Verificar si el correo ya existe en users, ignorando el usuario actual del empleado
                                $query = \App\Models\User::where('email', $value);
                                
                                if ($record && $record->user_id) {
                                    $query->where('id', '!=', $record->user_id);
                                }
                                
                                if ($query->exists()) {
                                    $fail('Este correo electrónico ya está registrado en el sistema.');
                                }
                            };
                        },
                    ]),
                
                TextInput::make('distrito')
                    ->label('Distrito')
                    ->maxLength(50)
                    ->placeholder('Ingrese el distrito'),
                
                DatePicker::make('fecha_incorporacion')
                    ->label('Fecha de Incorporación')
                    ->required()
                    ->native(false)
                    ->displayFormat('d/m/Y')
                    ->default(now()),
                
                TextInput::make('estado_empleado')
                    ->label('Estado')
                    ->required()
                    ->default('activo'),

                \Filament\Forms\Components\Select::make('rol')
                    ->label('Rol del Empleado')
                    ->options(function () {
                        return \Spatie\Permission\Models\Role::all()->pluck('name', 'name');
                    })
                    ->required()
                    ->default('vendedor')
                    ->searchable()
                    ->helperText('Seleccione el rol que tendrá el empleado en el sistema')
                    ->placeholder('Seleccionar rol'),
                
                // === REGISTRO FACIAL (Solo Super Admin) ===
                ViewField::make('foto_actual')
                    ->label('Foto Facial Actual')
                    ->view('filament.forms.components.current-face-photo')
                    ->visible(true), // Temporalmente visible para todos
                
                ViewField::make('face_registration')
                    ->label('')
                    ->view('filament.forms.components.face-registration-component')
                    ->visible(true), // Temporalmente visible para todos
                
                Hidden::make('face_descriptors'),
                Hidden::make('foto_facial_path'),
                Hidden::make('captured_face_image'),
            ]);
    }
}

