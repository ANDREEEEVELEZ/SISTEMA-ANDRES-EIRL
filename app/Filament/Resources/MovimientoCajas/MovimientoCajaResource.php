<?php

namespace App\Filament\Resources\MovimientoCajas;

use App\Filament\Resources\MovimientoCajas\Pages\CreateMovimientoCaja;
use App\Filament\Resources\MovimientoCajas\Pages\EditMovimientoCaja;
use App\Filament\Resources\MovimientoCajas\Pages\ListMovimientoCajas;
use App\Filament\Resources\MovimientoCajas\Schemas\MovimientoCajaForm;
use App\Filament\Resources\MovimientoCajas\Tables\MovimientoCajasTable;
use App\Models\MovimientoCaja;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class MovimientoCajaResource extends Resource
{
    protected static ?string $model = MovimientoCaja::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'TIPO';

    public static function form(Schema $schema): Schema
    {
        return MovimientoCajaForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MovimientoCajasTable::configure($table);
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
            'index' => ListMovimientoCajas::route('/'),
            'create' => CreateMovimientoCaja::route('/create'),
            'edit' => EditMovimientoCaja::route('/{record}/edit'),
        ];
    }
}
