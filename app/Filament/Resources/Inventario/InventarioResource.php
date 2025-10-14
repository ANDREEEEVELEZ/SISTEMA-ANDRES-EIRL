<?php

namespace App\Filament\Resources\Inventario;

use App\Filament\Resources\Inventario\Pages\CreateInventario;
use App\Filament\Resources\Inventario\Pages\EditInventario;
use App\Filament\Resources\Inventario\Pages\ListInventario;
use App\Filament\Resources\Inventario\Schemas\InventarioForm;
use App\Filament\Resources\Inventario\Tables\InventarioTable;
use App\Models\MovimientoInventario;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class InventarioResource extends Resource
{
    protected static ?string $model = MovimientoInventario::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $recordTitleAttribute = 'motivo_movimiento';

    protected static ?string $navigationLabel = 'Inventario';

    protected static ?string $breadcrumb = 'Inventario';

    protected static ?string $label = 'Inventario';

    protected static ?string $pluralLabel = 'Inventario';

    public static function form(Schema $schema): Schema
    {
        return InventarioForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return InventarioTable::configure($table);
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
            'index' => ListInventario::route('/'),
            'create' => CreateInventario::route('/create'),
            'edit' => EditInventario::route('/{record}/edit'),
        ];
    }
}
