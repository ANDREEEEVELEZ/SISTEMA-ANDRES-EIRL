<?php

namespace App\Filament\Resources\Asistencias\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AsistenciasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->selectCurrentPageOnly()
            ->deselectAllRecordsWhenFiltered(false)
            ->checkIfRecordIsSelectableUsing(fn (): bool => false)
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
                    }),
                TextColumn::make('observacion')
                    ->label('Observación')
                    ->searchable()
                    ->limit(30),
                TextColumn::make('razon_manual')
                    ->label('Motivo Registro Manual')
                    ->searchable()
                    ->limit(30)
                    ->placeholder('N/A'),
                TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                \Filament\Tables\Filters\Filter::make('rango_fecha')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('fecha_inicio')
                            ->label('Desde')
                            ->native(false)
                            ->displayFormat('d/m/Y'),
                        \Filament\Forms\Components\DatePicker::make('fecha_fin')
                            ->label('Hasta')
                            ->native(false)
                            ->displayFormat('d/m/Y'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        try {
                            if (! empty($data['fecha_inicio'])) {
                                $fInicio = $data['fecha_inicio'];
                                if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $fInicio)) {
                                    $fInicio = \Carbon\Carbon::createFromFormat('d/m/Y', $fInicio)->format('Y-m-d');
                                } else {
                                    $fInicio = \Carbon\Carbon::parse($fInicio)->format('Y-m-d');
                                }
                                $query->whereDate('fecha', '>=', $fInicio);
                            }
                            if (! empty($data['fecha_fin'])) {
                                $fFin = $data['fecha_fin'];
                                if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $fFin)) {
                                    $fFin = \Carbon\Carbon::createFromFormat('d/m/Y', $fFin)->format('Y-m-d');
                                } else {
                                    $fFin = \Carbon\Carbon::parse($fFin)->format('Y-m-d');
                                }
                                $query->whereDate('fecha', '<=', $fFin);
                            }
                        } catch (\Exception $e) {
                            // En caso de parseo fallido, no aplicar filtro para evitar ocultar datos
                        }
                        return $query;
                    })
                    ->indicateUsing(function (array $data): ?string {
                        if (empty($data['fecha_inicio']) && empty($data['fecha_fin'])) {
                            return null;
                        }
                        $inicio = $data['fecha_inicio'] ? (\Carbon\Carbon::parse($data['fecha_inicio'])->format('d/m/Y')) : '—';
                        $fin = $data['fecha_fin'] ? (\Carbon\Carbon::parse($data['fecha_fin'])->format('d/m/Y')) : '—';
                        return sprintf('Desde %s hasta %s', $inicio, $fin);
                    }),

                SelectFilter::make('estado')
                    ->label('Estado')
                    ->options([
                        'presente' => 'Trabajado',
                        'tardanza' => 'Tardanza',
                        'ausente' => 'Ausencia',
                    ])
                    ->query(function (Builder $query, $value) {
                        if (empty($value)) {
                            return $query;
                        }
                        return $query->where('estado', $value);
                    }),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                // Sin acciones de eliminación masiva
            ])
            ->defaultSort('created_at', 'desc');
    }
}
