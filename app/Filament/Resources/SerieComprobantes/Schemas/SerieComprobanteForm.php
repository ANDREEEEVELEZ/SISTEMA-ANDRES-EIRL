<?php

namespace App\Filament\Resources\SerieComprobantes\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class SerieComprobanteForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('tipo')
                    ->options([
            'boleta' => 'Boleta',
            'factura' => 'Factura',
            'ticket' => 'Ticket',
            'nota de credito' => 'Nota de credito',
            'nota de debito' => 'Nota de debito',
        ])
                    ->required(),
                TextInput::make('serie')
                    ->required(),
                TextInput::make('ultimo_numero')
                    ->required()
                    ->numeric(),
            ]);
    }
}
