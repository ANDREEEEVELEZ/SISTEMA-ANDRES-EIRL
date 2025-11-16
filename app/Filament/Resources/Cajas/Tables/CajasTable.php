<?php

namespace App\Filament\Resources\Cajas\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\DatePicker;
use Illuminate\Support\Carbon;

class CajasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->heading('Registros de Cajas')
            ->defaultSort('fecha_apertura', 'desc') // Mostrar registros más recientes primero
            ->columns([
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
                    ->formatStateUsing(fn($state) => $state ? (strlen($state) > 60 ? substr($state, 0, 60) . '...' : $state) : '')
                    ->tooltip(fn ($record) => $record->observacion)
                    ->extraAttributes(['style' => 'max-width:200px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;'])
                    ,
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
                Filter::make('fecha_apertura')
                    ->form([
                        DatePicker::make('fecha_inicio')
                            ->label('Fecha inicio'),
                        DatePicker::make('fecha_fin')
                            ->label('Fecha fin'),
                    ])
                    ->query(function ($query, array $data) {
                        if (!empty($data['fecha_inicio'])) {
                            $inicio = Carbon::parse($data['fecha_inicio'])->startOfDay();
                            $query->where('fecha_apertura', '>=', $inicio);
                        }

                        if (!empty($data['fecha_fin'])) {
                            $fin = Carbon::parse($data['fecha_fin'])->endOfDay();
                            $query->where('fecha_apertura', '<=', $fin);
                        }
                    })
                    ->label('Rango de fechas'),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([

            ]);
    }
}
