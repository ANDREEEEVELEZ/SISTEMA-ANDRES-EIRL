<?php

namespace App\Filament\Resources\Categorias\RelationManagers;

use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions\BulkActionGroup;
use App\Models\Categoria;
use Illuminate\Database\Eloquent\Collection;

class ProductosRelationManager extends RelationManager
{
    protected static string $relationship = 'productos';

    protected static ?string $title = 'Productos Asociados';

    protected static ?string $label = 'Producto';

    protected static ?string $pluralLabel = 'Productos';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\TextInput::make('nombre_producto')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('nombre_producto')
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('nombre_producto')
                    ->label('Nombre del Producto')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('stock_total')
                    ->label('Stock')
                    ->sortable()
                    ->badge()
                    ->color(fn ($record) => match (true) {
                        $record->stock_total <= 0 => 'danger',
                        $record->stock_total <= $record->stock_minimo => 'warning',
                        default => 'success',
                    }),
                Tables\Columns\TextColumn::make('unidad_medida')
                    ->label('Unidad')
                    ->sortable(),
                Tables\Columns\IconColumn::make('estado')
                    ->label('Estado')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                //
            ])
            ->actions([
                //
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    \Filament\Actions\BulkAction::make('mover_seleccionados')
                        ->label('Mover a Otra Categoría')
                        ->icon('heroicon-o-arrows-right-left')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->modalHeading('Mover Productos')
                        ->modalDescription('¿Está seguro de que desea mover los productos seleccionados a otra categoría?')
                        ->form([
                            Forms\Components\Select::make('nueva_categoria_id')
                                ->label('Categoría Destino')
                                ->options(fn () => Categoria::where('estado', true)
                                    ->where('id', '!=', $this->ownerRecord->id)
                                    ->pluck('NombreCategoria', 'id'))
                                ->required()
                                ->searchable()
                                ->native(false)
                                ->helperText('Seleccione la categoría a la que desea mover los productos'),
                        ])
                        ->action(function (Collection $records, array $data) {
                            $count = $records->count();

                            foreach ($records as $record) {
                                $record->update([
                                    'categoria_id' => $data['nueva_categoria_id']
                                ]);
                            }

                            \Filament\Notifications\Notification::make()
                                ->title('Productos movidos exitosamente')
                                ->body($count . ' producto(s) fueron movidos a la nueva categoría')
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ])
            ->emptyStateHeading('No hay productos en esta categoría')
            ->emptyStateDescription('Esta categoría aún no tiene productos asociados.')
            ->emptyStateIcon('heroicon-o-cube');
    }
}
