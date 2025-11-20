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
            ->modalHeading('Mover productos a otra categoría')
            ->modalDescription('Seleccione los productos que desea mover y la categoría destino.')
            ->modalWidth('2xl')
            ->form([
                \Filament\Forms\Components\CheckboxList::make('productos_ids')
                    ->label('Productos a mover')
                    ->options(fn () => $this->record->productos()
                        ->orderBy('nombre_producto')
                        ->get()
                        ->mapWithKeys(function ($producto) {
                            $stockInfo = " (Stock: {$producto->stock_total})";
                            return [$producto->id => $producto->nombre_producto . $stockInfo];
                        }))
                    ->required()
                    ->searchable()
                    ->bulkToggleable()
                    ->columns(1)
                    ->helperText('Seleccione uno o más productos para mover. Puede usar "Seleccionar todo" para mover todos los productos.')
                    ->default(fn () => $this->record->productos()->pluck('id')->toArray()),
                
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
                $productosIds = $data['productos_ids'];
                $count = count($productosIds);
                
                $categoriaAnterior = $this->record->NombreCategoria;
                $categoriaNueva = \App\Models\Categoria::find($data['nueva_categoria_id'])->NombreCategoria ?? 'Desconocida';
                
                foreach ($productosIds as $productoId) {
                    $producto = \App\Models\Producto::find($productoId);
                    if ($producto) {
                        $producto->categoria_id = $data['nueva_categoria_id'];
                        $producto->save();
                    }
                }
                
                \Filament\Notifications\Notification::make()
                    ->title('Productos movidos exitosamente')
                    ->body("{$count} producto(s) fueron movidos de '{$categoriaAnterior}' a '{$categoriaNueva}'.")
                    ->success()
                    ->send();
                    
                $this->refreshFormData(['productos']);
            });
        // No se permite eliminación por políticas de seguridad
        return $actions;
    }
}
