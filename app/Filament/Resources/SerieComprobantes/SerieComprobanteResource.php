<?php

namespace App\Filament\Resources\SerieComprobantes;

use App\Filament\Resources\SerieComprobantes\Pages\CreateSerieComprobante;
use App\Filament\Resources\SerieComprobantes\Pages\EditSerieComprobante;
use App\Filament\Resources\SerieComprobantes\Pages\ListSerieComprobantes;
use App\Filament\Resources\SerieComprobantes\Schemas\SerieComprobanteForm;
use App\Filament\Resources\SerieComprobantes\Tables\SerieComprobantesTable;
use App\Models\SerieComprobante;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class SerieComprobanteResource extends Resource
{
    protected static ?string $model = SerieComprobante::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'tiposerie';

    public static function form(Schema $schema): Schema
    {
        return SerieComprobanteForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SerieComprobantesTable::configure($table);
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
            'index' => ListSerieComprobantes::route('/'),
            'create' => CreateSerieComprobante::route('/create'),
            'edit' => EditSerieComprobante::route('/{record}/edit'),
        ];
    }
}
