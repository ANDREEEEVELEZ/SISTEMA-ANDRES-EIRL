<?php

namespace App\Filament\Resources\MovimientoInventarios;

use App\Filament\Resources\MovimientoInventarios\Pages\CreateMovimientoInventario;
use App\Filament\Resources\MovimientoInventarios\Pages\EditMovimientoInventario;
use App\Filament\Resources\MovimientoInventarios\Pages\ListMovimientoInventarios;
use App\Filament\Resources\MovimientoInventarios\Schemas\MovimientoInventarioForm;
use App\Filament\Resources\MovimientoInventarios\Tables\MovimientoInventariosTable;
use App\Models\MovimientoInventario;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class MovimientoInventarioResource extends Resource
{
    protected static ?string $model = MovimientoInventario::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'tipomovimiento';

    public static function form(Schema $schema): Schema
    {
        return MovimientoInventarioForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MovimientoInventariosTable::configure($table);
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
            'index' => ListMovimientoInventarios::route('/'),
            'create' => CreateMovimientoInventario::route('/create'),
            'edit' => EditMovimientoInventario::route('/{record}/edit'),
        ];
    }
}
