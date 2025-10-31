<?php

namespace App\Filament\Resources\Empleados\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ViewField;
use Filament\Schemas\Schema;

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
                    ->maxLength(100),
                
                TextInput::make('apellidos')
                    ->label('Apellidos')
                    ->required()
                    ->maxLength(100),
                
                TextInput::make('dni')
                    ->label('DNI')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(15)
                    ->regex('/^[0-9]+$/')
                    ->helperText('Solo números. Este campo es único en el sistema.'),
                
                TextInput::make('telefono')
                    ->label('Teléfono')
                    ->tel()
                    ->maxLength(20),
                
                TextInput::make('direccion')
                    ->label('Dirección')
                    ->maxLength(255),
                
                DatePicker::make('fecha_nacimiento')
                    ->label('Fecha de Nacimiento')
                    ->required()
                    ->native(false)
                    ->displayFormat('d/m/Y'),
                
                TextInput::make('correo_empleado')
                    ->label('Correo Electrónico')
                    ->email()
                    ->maxLength(100),
                
                TextInput::make('distrito')
                    ->label('Distrito')
                    ->maxLength(50),
                
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
                
                // === REGISTRO FACIAL (Solo Super Admin) ===
                ViewField::make('foto_actual')
                    ->label('Foto Facial Actual')
                    ->view('filament.forms.components.current-face-photo')
                    ->visible(fn ($record) => $record?->foto_facial_path !== null),
                
                ViewField::make('face_registration')
                    ->label('Registro de Rostro Facial')
                    ->view('filament.forms.components.face-registration-component')
                    ->visible(fn () => auth()->user()->hasRole('super_admin'))
                    ->helperText('Usa esta función para registrar el rostro del empleado y habilitar el reconocimiento facial.'),
                
                Hidden::make('face_descriptors'),
                Hidden::make('foto_facial_path'),
                Hidden::make('captured_face_image'),
            ]);
    }
}

