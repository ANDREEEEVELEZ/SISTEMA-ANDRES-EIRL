<?php

namespace App\Filament\Resources\ProduccionDiarias;

use App\Filament\Resources\ProduccionDiarias\Pages\CreateProduccionDiaria;
use App\Filament\Resources\ProduccionDiarias\Pages\EditProduccionDiaria;
use App\Filament\Resources\ProduccionDiarias\Pages\ListProduccionDiarias;
use App\Filament\Resources\ProduccionDiarias\Schemas\ProduccionDiariaForm;
use App\Filament\Resources\ProduccionDiarias\Tables\ProduccionDiariasTable;
use App\Models\ProduccionDiaria;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ProduccionDiariaResource extends Resource
{
    protected static ?string $model = ProduccionDiaria::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'CantidadDiaria';

    public static function form(Schema $schema): Schema
    {
        return ProduccionDiariaForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProduccionDiariasTable::configure($table);
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
            'index' => ListProduccionDiarias::route('/'),
            'create' => CreateProduccionDiaria::route('/create'),
            'edit' => EditProduccionDiaria::route('/{record}/edit'),
        ];
    }
}
