<?php

namespace App\Filament\Resources\Comprobantes\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ComprobantesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('venta.id')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('serieComprobante.id')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('tipo'),
                TextColumn::make('serie')
                    ->searchable(),
                TextColumn::make('correlativo')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('fecha_emision')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('sub_total')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('igv')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('total')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('estado'),
                TextColumn::make('motivo_anulacion')
                    ->searchable(),
                TextColumn::make('hash_sunat')
                    ->searchable(),
                TextColumn::make('codigo_sunat')
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
