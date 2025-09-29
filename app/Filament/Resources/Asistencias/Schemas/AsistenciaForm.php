<?php

namespace App\Filament\Resources\Asistencias\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Schemas\Schema;

class AsistenciaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('empleado_id')
                    ->relationship('empleado', 'id')
                    ->required(),
                DatePicker::make('fecha')
                    ->required(),
                TimePicker::make('hora_entrada')
                    ->required(),
                TimePicker::make('hora_salida')
                    ->required(),
                TextInput::make('observacion'),
                Placeholder::make('camara')
                    ->label('Verificación Facial')
                    ->content(view('components.camara-asistencia')),
            ]);
    }
}
