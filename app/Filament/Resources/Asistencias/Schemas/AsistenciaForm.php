<?php

namespace App\Filament\Resources\Asistencias\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class AsistenciaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('empleado_id')
                    ->label('Empleado')
                    ->relationship('empleado', 'nombres')
                    ->getOptionLabelFromRecordUsing(fn ($record) => 
                        "{$record->nombres} {$record->apellidos} - DNI: {$record->dni}"
                    )
                    ->searchable(['nombres', 'apellidos', 'dni'])
                    ->required()
                    ->preload()
                    ->helperText('Seleccione el empleado para registrar su asistencia'),
                
                DatePicker::make('fecha')
                    ->label('Fecha')
                    ->default(now())
                    ->required()
                    ->native(false)
                    ->displayFormat('d/m/Y')
                    ->maxDate(now())
                    ->helperText('Fecha de la asistencia'),
                
                TimePicker::make('hora_entrada')
                    ->label('Hora de Entrada')
                    ->required()
                    ->seconds(false)
                    ->default(now()->format('H:i'))
                    ->helperText('Hora en que el empleado ingresó'),
                
                TimePicker::make('hora_salida')
                    ->label('Hora de Salida')
                    ->seconds(false)
                    ->helperText('Hora en que el empleado salió (dejar vacío si aún no sale)'),
                
                Select::make('estado')
                    ->label('Estado de Asistencia')
                    ->options([
                        'presente' => 'Presente',
                        'tardanza' => 'Tardanza',
                        'falta' => 'Falta',
                        //'permiso' => 'Permiso',
                        //'licencia' => 'Licencia',
                    ])
                    ->default('presente')
                    ->required()
                    ->helperText('Estado de la asistencia del empleado'),
                
                Textarea::make('observacion')
                    ->label('Observaciones')
                    ->rows(3)
                    ->placeholder('Ingrese cualquier observación relevante sobre la asistencia...')
                    ->helperText('Opcional: Comentarios adicionales sobre la asistencia'),
            ]);
    }
}
