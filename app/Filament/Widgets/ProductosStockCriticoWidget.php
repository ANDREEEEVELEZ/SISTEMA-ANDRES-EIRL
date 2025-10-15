<?php

namespace App\Filament\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use App\Models\Producto;

class ProductosStockCriticoWidget extends BaseWidget
{
    protected static ?int $sort = 10; // Después de todos los widgets existentes
    protected int | string | array $columnSpan = [
        'md' => 1,
        'xl' => 1,
    ];

    public function getTableHeading(): ?string
    {
        return 'Productos con Stock Crítico';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Producto::query()
                    ->whereColumn('stock_total', '<=', 'stock_minimo')
                    ->where('stock_total', '>=', 0)
                    ->orderBy('stock_total', 'asc')
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('nombre_producto')
                    ->label('Producto')
                    ->sortable()
                    ->limit(50)
                    ->tooltip(function ($record) {
                        return $record->nombre_producto;
                    }),

                Tables\Columns\TextColumn::make('stock_total')
                    ->label('Stock')
                    ->sortable()
                    ->badge()
                    ->color(function ($record) {
                        if ($record->stock_total == 0) return 'danger';
                        if ($record->stock_total == 1) return 'danger';
                        if ($record->stock_total <= 3) return 'warning';
                        return 'success';
                    })
                    ->formatStateUsing(function ($state, $record) {
                        if ($record->stock_total == 0) return '¡AGOTADO!';
                        if ($record->stock_total == 1) return '¡CRÍTICO! ' . $state . ' und.';
                        if ($record->stock_total <= 3) return '¡BAJO! ' . $state . ' und.';
                        return $state . ' und.';
                    })
                    ->icon(function ($record) {
                        if ($record->stock_total == 0) return 'heroicon-m-exclamation-triangle';
                        if ($record->stock_total <= 2) return 'heroicon-m-exclamation-circle';
                        return null;
                    }),
            ])
            ->defaultSort('stock_total', 'asc')
            ->paginated(false)
            ->striped()
            ->emptyStateHeading('¡Excelente!')
            ->emptyStateDescription('No hay productos con stock crítico')
            ->emptyStateIcon('heroicon-o-check-circle');
    }
}
