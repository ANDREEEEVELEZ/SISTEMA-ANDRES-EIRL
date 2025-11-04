<?php

namespace App\Filament\Widgets;

use Filament\Widgets\TableWidget as BaseWidget;
use Filament\Tables;
use Filament\Tables\Table;
use App\Models\Producto;
use Carbon\Carbon;

class ProductosMasVendidosWidget extends BaseWidget
{
    protected static ?int $sort = 10; // Aparece después, en mitad de fila

    protected int | string | array $columnSpan = [
        'md' => 2,
        'xl' => 1,
    ];

    public function getTableHeading(): ?string
    {
        return 'Top 5 Productos - Mes Actual';
    }

    public function table(Table $table): Table
    {
        $inicio = Carbon::now()->startOfMonth()->toDateString();
        $fin = Carbon::now()->endOfMonth()->toDateString();

    $query = Producto::selectRaw('productos.id as id, productos.nombre_producto as nombre_producto, categorias.NombreCategoria as categoria, SUM(detalle_ventas.cantidad_venta) as total_vendido, SUM(detalle_ventas.cantidad_venta * detalle_ventas.precio_unitario) as monto_vendido')
            ->join('detalle_ventas', 'productos.id', '=', 'detalle_ventas.producto_id')
            ->join('ventas', 'ventas.id', '=', 'detalle_ventas.venta_id')
            ->leftJoin('categorias', 'productos.categoria_id', '=', 'categorias.id')
            ->whereBetween('ventas.fecha_venta', [$inicio, $fin])
            ->where('ventas.estado_venta', '!=', 'anulada')
        ->groupBy('productos.id', 'productos.nombre_producto', 'categorias.NombreCategoria')
            ->orderByDesc('total_vendido')
            ->limit(5);

        return $table
            ->query($query)
            ->columns([
                Tables\Columns\TextColumn::make('nombre_producto')
                    ->label('Producto')
                    ->sortable()
                    ->limit(30)
                    ->tooltip(fn ($record) => $record->nombre_producto),

                Tables\Columns\TextColumn::make('categoria')
                    ->label('Categoría')
                    ->badge()
                    ->color('info')
                    ->default('Sin categoría'),

                Tables\Columns\TextColumn::make('total_vendido')
                    ->label('Cantidad Vendida')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => (int) $state),

                Tables\Columns\TextColumn::make('monto_vendido')
                    ->label('Monto Vendido')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => 'S/ ' . number_format((float) $state, 2)),


            ])
            ->paginated(false)
            ->striped()
            ->emptyStateHeading('Sin ventas en el mes')
            ->emptyStateDescription('No se encontraron ventas para el mes actual');
    }
}
