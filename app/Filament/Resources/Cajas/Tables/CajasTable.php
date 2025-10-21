<?php

namespace App\Filament\Resources\Cajas\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CajasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('fecha_apertura', 'desc') // Mostrar registros más recientes primero
            ->columns([
                TextColumn::make('user.name')
                    ->label('Usuario')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('fecha_apertura')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('fecha_cierre')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('saldo_inicial')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('saldo_final')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('estado'),
                TextColumn::make('observacion')
                    ->searchable(),
                    /*
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),*/
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([

            ]);
    }
}
