<?php

namespace App\Filament\Resources\Ventas\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class VentasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('cliente.id')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('caja.id')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('subtotal_venta')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('igv')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('descuento_total')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('total_venta')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('fecha_venta')
                    ->date()
                    ->sortable(),
                TextColumn::make('hora_venta')
                    ->time()
                    ->sortable(),
                TextColumn::make('estado_venta'),
                TextColumn::make('metodo_pago')
                    ->searchable(),
                TextColumn::make('cod_operacion')
                    ->searchable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
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
            ]);
    }
}
