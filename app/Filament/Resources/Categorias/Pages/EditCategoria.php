<?php

namespace App\Filament\Resources\Categorias\Pages;

use App\Filament\Resources\Categorias\CategoriaResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCategoria extends EditRecord
{
    protected static string $resource = CategoriaResource::class;

    protected function getHeaderActions(): array
    {
        $actions = [];
        $actions[] = \Filament\Actions\Action::make('mover_productos')
            ->label('Mover Productos')
            ->icon('heroicon-o-arrows-right-left')
            ->color('warning')
            ->visible(fn () => $this->record->productos()->count() > 0)
            ->modalHeading('Mover todos los productos de esta categoría')
            ->modalDescription('Seleccione la categoría destino. Todos los productos de esta categoría serán movidos a la categoría seleccionada.')
            ->form([
                \Filament\Forms\Components\Select::make('nueva_categoria_id')
                    ->label('Categoría Destino')
                    ->options(fn () => \App\Models\Categoria::where('estado', true)
                        ->where('id', '!=', $this->record->id)
                        ->pluck('NombreCategoria', 'id'))
                    ->required()
                    ->searchable()
                    ->native(false)
                    ->helperText('Solo se muestran categorías activas.'),
            ])
            ->action(function (array $data) {
                $productos = $this->record->productos;
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
                $this->refreshFormData(['productos']);
            });
        // No se permite eliminación por políticas de seguridad
        return $actions;
    }
}
