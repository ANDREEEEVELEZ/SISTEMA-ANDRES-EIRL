<?php

namespace App\Filament\Resources\Asistencias\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AsistenciasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('empleado.nombre_completo')
                    ->label('Empleado')
                    ->searchable(['nombres', 'apellidos'])
                    ->sortable(),
                TextColumn::make('fecha')
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('hora_entrada')
                    ->label('Hora Entrada')
                    ->time('H:i')
                    ->sortable(),
                TextColumn::make('hora_salida')
                    ->label('Hora Salida')
                    ->time('H:i')
                    ->sortable(),
                TextColumn::make('estado')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'presente' => 'success',
                        'tardanza' => 'warning',
                        'ausente' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'presente' => 'Trabajado',
                        'tardanza' => 'Tardanza',
                        'ausente' => 'Ausencia',
                        default => ucfirst($state),
                    }),
                TextColumn::make('metodo_registro')
                    ->label('Método')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'facial' => 'Facial',
                        'manual_dni' => 'Manual',
                        default => $state,
                    })
                    ->toggleable(),
                TextColumn::make('observacion')
                    ->label('Observación')
                    ->searchable()
                    ->limit(30)
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('fecha', 'desc');
    }
}
