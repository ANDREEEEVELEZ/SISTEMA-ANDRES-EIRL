<?php

namespace App\Filament\Resources\Empleados\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\DatePicker;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;

class EmpleadosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('foto_facial_path')
                    ->label('Foto')
                    ->circular()
                    ->defaultImageUrl(url('/images/default-avatar.png'))
                    ->getStateUsing(function ($record) {
                        if ($record->foto_facial_path && Storage::disk('public')->exists($record->foto_facial_path)) {
                            return asset('storage/' . $record->foto_facial_path);
                        }
                        return url('/images/default-avatar.png');
                    })
                    ->size(50)
                    ->tooltip(fn ($record) => $record->face_descriptors ? 'Rostro registrado' : 'Sin rostro'),
                
                IconColumn::make('face_descriptors')
                    ->label('Facial')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->tooltip(fn ($record) => $record->face_descriptors ? '✅ Puede usar reconocimiento facial' : '❌ No registrado'),
                
                TextColumn::make('user.name')
                    ->label('Usuario')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('nombres')
                    ->label('Nombres')
                    ->searchable(),
                TextColumn::make('apellidos')
                    ->label('Apellidos')
                    ->searchable(),
                TextColumn::make('dni')
                    ->label('DNI')
                    ->searchable(),
                TextColumn::make('telefono')
                    ->label('Teléfono')
                    ->searchable(),
                TextColumn::make('correo_empleado')
                    ->label('Correo')
                    ->searchable(),
                TextColumn::make('fecha_incorporacion')
                    ->label('Fecha Incorporación')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('estado_empleado')
                    ->label('Estado')
                    ->formatStateUsing(fn(string $state): string => ucfirst($state))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'activo' => 'success',
                        'inactivo' => 'danger',
                        default => 'gray',
                    }),
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
                // Filtro por Estado
                SelectFilter::make('estado_empleado')
                    ->label('Estado')
                    ->options([
                        'activo' => 'Activos',
                        'inactivo' => 'Inactivos',
                    ])
                    ->placeholder('Todos los estados'),

                // Filtro por Rango de Fechas de Incorporación
                Filter::make('fecha_incorporacion')
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
                                fn (Builder $query, $date): Builder => $query->whereDate('fecha_incorporacion', '>=', $date),
                            )
                            ->when(
                                $data['fecha_hasta'],
                                fn (Builder $query, $date): Builder => $query->whereDate('fecha_incorporacion', '<=', $date),
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

                // Acción INACTIVAR - Solo aparece cuando el empleado está ACTIVO
                Action::make('inactivar')
                    ->label('Inactivar')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Inactivar Empleado')
                    ->modalDescription('¿Está seguro de que desea inactivar este empleado? Se bloqueará su acceso al sistema.')
                    ->modalSubmitActionLabel('Inactivar')
                    ->visible(fn ($record) => $record->estado_empleado === 'activo')
                    ->action(function ($record) {
                        // Inactivar empleado
                        $record->update(['estado_empleado' => 'inactivo']);

                        // Si tiene usuario asociado, también inactivarlo (bloquear acceso)
                        if ($record->user) {
                            $record->user->update(['estado' => 'inactivo']);
                        }

                        Notification::make()
                            ->title('Empleado inactivado exitosamente')
                            ->body('El acceso al sistema ha sido bloqueado.')
                            ->success()
                            ->send();
                    }),

                // Acción ACTIVAR - Solo aparece cuando el empleado está INACTIVO
                Action::make('activar')
                    ->label('Activar')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Activar Empleado')
                    ->modalDescription('¿Está seguro de que desea activar este empleado? Se habilitará su acceso al sistema.')
                    ->modalSubmitActionLabel('Activar')
                    ->visible(fn ($record) => $record->estado_empleado === 'inactivo')
                    ->action(function ($record) {
                        // Activar empleado
                        $record->update(['estado_empleado' => 'activo']);

                        // Si tiene usuario asociado, también activarlo (habilitar acceso)
                        if ($record->user) {
                            $record->user->update(['estado' => 'activo']);
                        }

                        Notification::make()
                            ->title('Empleado activado exitosamente')
                            ->body('El acceso al sistema ha sido habilitado.')
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
                        ->modalHeading('Activar Empleados')
                        ->modalDescription('¿Está seguro de que desea activar los empleados seleccionados? Se habilitará su acceso al sistema.')
                        ->modalSubmitActionLabel('Activar')
                        ->action(function ($records) {
                            $count = $records->count();
                            foreach ($records as $record) {
                                $record->update(['estado_empleado' => 'activo']);
                                
                                // Activar usuario asociado si existe
                                if ($record->user) {
                                    $record->user->update(['estado' => 'activo']);
                                }
                            }

                            Notification::make()
                                ->title($count . ' empleado(s) activado(s) exitosamente')
                                ->body('El acceso al sistema ha sido habilitado.')
                                ->success()
                                ->send();
                        }),

                    \Filament\Actions\BulkAction::make('inactivarMasivo')
                        ->label('Inactivar Seleccionados')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Inactivar Empleados')
                        ->modalDescription('¿Está seguro de que desea inactivar los empleados seleccionados? Se bloqueará su acceso al sistema.')
                        ->modalSubmitActionLabel('Inactivar')
                        ->action(function ($records) {
                            $count = $records->count();
                            foreach ($records as $record) {
                                $record->update(['estado_empleado' => 'inactivo']);
                                
                                // Inactivar usuario asociado si existe
                                if ($record->user) {
                                    $record->user->update(['estado' => 'inactivo']);
                                }
                            }

                            Notification::make()
                                ->title($count . ' empleado(s) inactivado(s) exitosamente')
                                ->body('El acceso al sistema ha sido bloqueado.')
                                ->success()
                                ->send();
                        }),

                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
