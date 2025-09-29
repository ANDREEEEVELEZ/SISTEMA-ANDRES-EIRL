<?php

namespace App\Filament\Resources\Asistencias;

use App\Filament\Resources\Asistencias\Pages\CreateAsistencia;
use App\Filament\Resources\Asistencias\Pages\EditAsistencia;
use App\Filament\Resources\Asistencias\Pages\ListAsistencias;
use App\Filament\Resources\Asistencias\Schemas\AsistenciaForm;
use App\Filament\Resources\Asistencias\Tables\AsistenciasTable;
use App\Models\Asistencia;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class AsistenciaResource extends Resource
{
    protected static ?string $model = Asistencia::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'fecha';

    public static function form(Schema $schema): Schema
    {
        return AsistenciaForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AsistenciasTable::configure($table);
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
            'index' => ListAsistencias::route('/'),
            'create' => CreateAsistencia::route('/create'),
            'edit' => EditAsistencia::route('/{record}/edit'),
        ];
    }
}
