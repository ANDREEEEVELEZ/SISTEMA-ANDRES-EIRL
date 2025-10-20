<?php

namespace App\Filament\Resources\Clientes\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\DatePicker;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;

class ClientesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns(
                [
                    TextColumn::make('tipo_doc')
                        ->label('Tipo Documento')
                        ->formatStateUsing(fn(string $state): string => ucfirst($state)),
                    TextColumn::make('tipo_cliente')
                        ->label('Tipo Cliente')
                        ->formatStateUsing(fn(string $state): string => match($state) {
                            'natural' => '👤 Persona Natural',
                            'natural_con_negocio' => '🏪 Natural con Negocio',
                            'juridica' => '🏢 Persona Jurídica',
                            default => ucfirst($state)
                        })
                        ->badge()
                        ->color(fn (string $state): string => match ($state) {
                            'natural' => 'info',
                            'natural_con_negocio' => 'warning',
                            'juridica' => 'success',
                            default => 'gray',
                        }),
                    TextColumn::make('num_doc')
                        ->label('N° Documento')
                        ->searchable(),
                    TextColumn::make('nombre_razon')
                        ->label('Nombre o Razón Social')
                        ->searchable(),
                    TextColumn::make('fecha_registro')
                        ->label('Fecha Registro')
                        ->date('d/m/Y')
                        ->sortable(),
                    TextColumn::make('estado')
                        ->label('Estado')
                        ->formatStateUsing(fn(string $state): string => ucfirst($state))
                        ->badge()
                        ->color(fn (string $state): string => match ($state) {
                            'activo' => 'success',
                            'inactivo' => 'danger',
                            default => 'gray',
                        }),
                ]
            )

            ->filters([
                // Filtro por Estado
                SelectFilter::make('estado')
                    ->label('Estado')
                    ->options([
                        'activo' => 'Activos',
                        'inactivo' => 'Inactivos',
                    ])
                    ->placeholder('Todos los estados'),

                // Filtro por Tipo de Documento
                SelectFilter::make('tipo_doc')
                    ->label('Tipo de Documento')
                    ->options([
                        'DNI' => 'DNI',
                        'RUC' => 'RUC',
                    ])
                    ->placeholder('Todos los tipos'),

                // Filtro por Tipo de Cliente
                SelectFilter::make('tipo_cliente')
                    ->label('Tipo de Cliente')
                    ->options([
                        'natural' => '👤 Persona Natural',
                        'natural_con_negocio' => '🏪 Natural con Negocio',
                        'juridica' => '🏢 Persona Jurídica',
                    ])
                    ->placeholder('Todos los tipos'),

                // Filtro por Rango de Fechas de Registro
                Filter::make('fecha_registro')
                    ->form([
                        DatePicker::make('fecha_desde')
                            ->label('Fecha Desde')
                            ->placeholder('Seleccione fecha desde'),
                        DatePicker::make('fecha_hasta')
                            ->label('Fecha Hasta')
                            ->placeholder('Seleccione fecha hasta'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['fecha_desde'],
                                fn (Builder $query, $date): Builder => $query->whereDate('fecha_registro', '>=', $date),
                            )
                            ->when(
                                $data['fecha_hasta'],
                                fn (Builder $query, $date): Builder => $query->whereDate('fecha_registro', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];

                        if ($data['fecha_desde'] ?? null) {
                            $indicators[] = 'Desde: ' . \Carbon\Carbon::parse($data['fecha_desde'])->format('d/m/Y');
                        }

                        if ($data['fecha_hasta'] ?? null) {
                            $indicators[] = 'Hasta: ' . \Carbon\Carbon::parse($data['fecha_hasta'])->format('d/m/Y');
                        }

                        return $indicators;
                    }),
            ])
            ->actions([
                EditAction::make(),

                // Acción INACTIVAR - Solo aparece cuando el cliente está ACTIVO
                Action::make('inactivar')
                    ->label('Inactivar')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Inactivar Cliente')
                    ->modalDescription('¿Está seguro de que desea inactivar este cliente? No podrá realizar nuevas ventas con este cliente.')
                    ->modalSubmitActionLabel('Inactivar')
                    ->visible(fn ($record) => $record->estado === 'activo')
                    ->action(function ($record) {
                        $record->update(['estado' => 'inactivo']);

                        Notification::make()
                            ->title('Cliente inactivado exitosamente')
                            ->success()
                            ->send();
                    }),

                // Acción ACTIVAR - Solo aparece cuando el cliente está INACTIVO
                Action::make('activar')
                    ->label('Activar')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Activar Cliente')
                    ->modalDescription('¿Está seguro de que desea activar este cliente? Podrá realizar nuevamente ventas con este cliente.')
                    ->modalSubmitActionLabel('Activar')
                    ->visible(fn ($record) => $record->estado === 'inactivo')
                    ->action(function ($record) {
                        $record->update(['estado' => 'activo']);

                        Notification::make()
                            ->title('Cliente activado exitosamente')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    \Filament\Actions\BulkAction::make('activarMasivo')
                        ->label('Activar Seleccionados')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Activar Clientes')
                        ->modalDescription('¿Está seguro de que desea activar los clientes seleccionados?')
                        ->modalSubmitActionLabel('Activar')
                        ->action(function ($records) {
                            $count = $records->count();
                            foreach ($records as $record) {
                                $record->update(['estado' => 'activo']);
                            }

                            Notification::make()
                                ->title($count . ' cliente(s) activado(s) exitosamente')
                                ->success()
                                ->send();
                        }),

                    \Filament\Actions\BulkAction::make('inactivarMasivo')
                        ->label('Inactivar Seleccionados')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Inactivar Clientes')
                        ->modalDescription('¿Está seguro de que desea inactivar los clientes seleccionados? No podrán realizar nuevas ventas con estos clientes.')
                        ->modalSubmitActionLabel('Inactivar')
                        ->action(function ($records) {
                            $count = $records->count();
                            foreach ($records as $record) {
                                $record->update(['estado' => 'inactivo']);
                            }

                            Notification::make()
                                ->title($count . ' cliente(s) inactivado(s) exitosamente')
                                ->success()
                                ->send();
                        }),
                ]),
            ]);

    }
}
