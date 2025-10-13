<?php

namespace App\Filament\Widgets;

use App\Models\Producto;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Filament\Tables\Columns\TextColumn;

class StockBajoWidget extends BaseWidget
{
    protected static ?int $sort = 2;
    
    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('⚠️ Alertas de Stock')
            ->description('Productos con stock bajo o agotados')
            ->query(
                Producto::query()
                    ->conAlertaStock()
                    ->where('estado', 'activo')
                    ->with(['categoria'])
                    ->orderByRaw('CASE WHEN stock_total <= 0 THEN 0 ELSE 1 END')
                    ->orderBy('stock_total', 'asc')
            )
            ->columns([
                TextColumn::make('nombre_producto')
                    ->label('Producto')
                    ->searchable()
                    ->weight('bold')
                    ->icon(fn ($record) => $record->estaAgotado() ? 'heroicon-o-exclamation-circle' : 'heroicon-o-exclamation-triangle')
                    ->iconColor(fn ($record) => $record->estaAgotado() ? 'danger' : 'warning'),
                
                TextColumn::make('categoria.NombreCategoria')
                    ->label('Categoría')
                    ->badge()
                    ->color('info'),
                
                TextColumn::make('stock_total')
                    ->label('Stock Actual')
                    ->numeric()
                    ->alignCenter()
                    ->badge()
                    ->color(fn ($record): string => $record->estaAgotado() ? 'danger' : 'warning')
                    ->formatStateUsing(function ($state, $record) {
                        if ($record->estaAgotado()) {
                            return '🔴 ' . $state . ' (AGOTADO)';
                        }
                        return '🟡 ' . $state . ' (BAJO)';
                    }),
                
                TextColumn::make('stock_minimo')
                    ->label('Stock Mínimo')
                    ->numeric()
                    ->alignCenter(),
                
                TextColumn::make('unidad_medida')
                    ->label('Unidad')
                    ->badge()
                    ->color('gray'),
            ])
            ->emptyStateHeading('✅ Sin alertas de stock')
            ->emptyStateDescription('Todos los productos tienen stock suficiente')
            ->emptyStateIcon('heroicon-o-check-circle')
            ->paginated([5, 10, 25]);
    }

    public static function canView(): bool
    {
        // Mostrar el widget solo si hay productos con alerta de stock
        return Producto::conAlertaStock()->where('estado', 'activo')->exists();
    }
}
