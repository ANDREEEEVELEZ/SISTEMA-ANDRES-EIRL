<?php

namespace App\Filament\Resources\Comprobantes;

use App\Filament\Resources\Comprobantes\Pages\CreateComprobante;
use App\Filament\Resources\Comprobantes\Pages\EditComprobante;
use App\Filament\Resources\Comprobantes\Pages\ListComprobantes;
use App\Filament\Resources\Comprobantes\Schemas\ComprobanteForm;
use App\Filament\Resources\Comprobantes\Tables\ComprobantesTable;
use App\Models\Comprobante;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ComprobanteResource extends Resource
{
    protected static ?string $model = Comprobante::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'tipocomprobante';

    public static function form(Schema $schema): Schema
    {
        return ComprobanteForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ComprobantesTable::configure($table);
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
            'index' => ListComprobantes::route('/'),
            'create' => CreateComprobante::route('/create'),
            'edit' => EditComprobante::route('/{record}/edit'),
        ];
    }
}
