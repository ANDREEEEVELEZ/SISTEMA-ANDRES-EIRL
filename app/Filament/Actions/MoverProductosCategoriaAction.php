<?php

namespace App\Filament\Actions;

use App\Models\Categoria;
use Filament\Actions\Action;
use Filament\Forms;
use Illuminate\Database\Eloquent\Model;

class MoverProductosCategoriaAction extends Action
{
    public static function make(string $name = 'mover_productos')
    {
        return parent::make($name)
            ->label('Mover Productos')
            ->icon('heroicon-o-arrows-right-left')
            ->color('warning')
            ->modalHeading('Mover todos los productos de esta categoría')
            ->modalDescription('Seleccione la categoría destino. Todos los productos de esta categoría serán movidos a la categoría seleccionada.')
            ->form([
                Forms\Components\Select::make('nueva_categoria_id')
                    ->label('Categoría Destino')
                    ->options(fn ($livewire) => Categoria::where('estado', true)
                        ->where('id', '!=', $livewire->record->id)
                        ->pluck('NombreCategoria', 'id'))
                    ->required()
                    ->searchable()
                    ->native(false)
                    ->helperText('Solo se muestran categorías activas.'),
            ])
            ->action(function (array $data, Model $record) {
                $productos = $record->productos;
                $count = $productos->count();
                foreach ($productos as $producto) {
                    $producto->categoria_id = $data['nueva_categoria_id'];
                    $producto->save();
                }
                \Filament\Notifications\Notification::make()
                    ->title('Productos movidos exitosamente')
                    ->body($count . ' producto(s) fueron movidos a la nueva categoría.')
                    ->success()
                    ->send();
            });
    }
}
