<?php

namespace App\Filament\Resources\PrecioProductos;

use App\Filament\Resources\PrecioProductos\Pages\CreatePrecioProducto;
use App\Filament\Resources\PrecioProductos\Pages\EditPrecioProducto;
use App\Filament\Resources\PrecioProductos\Pages\ListPrecioProductos;
use App\Filament\Resources\PrecioProductos\Schemas\PrecioProductoForm;
use App\Filament\Resources\PrecioProductos\Tables\PrecioProductosTable;
use App\Models\PrecioProducto;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class PrecioProductoResource extends Resource
{
    protected static ?string $model = PrecioProducto::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'cantidadminima';

    public static function form(Schema $schema): Schema
    {
        return PrecioProductoForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PrecioProductosTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPrecioProductos::route('/'),
            'create' => CreatePrecioProducto::route('/create'),
            'edit' => EditPrecioProducto::route('/{record}/edit'),
        ];
    }
}
