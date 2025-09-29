<?php

namespace App\Filament\Resources\Comprobantes\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class ComprobanteForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('venta_id')
                    ->relationship('venta', 'id')
                    ->required(),
                Select::make('serie_comprobante_id')
                    ->relationship('serieComprobante', 'id')
                    ->required(),
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
                TextInput::make('correlativo')
                    ->required()
                    ->numeric(),
                DateTimePicker::make('fecha_emision')
                    ->required(),
                TextInput::make('sub_total')
                    ->required()
                    ->numeric(),
                TextInput::make('igv')
                    ->required()
                    ->numeric(),
                TextInput::make('total')
                    ->required()
                    ->numeric(),
                Select::make('estado')
                    ->options(['emitido' => 'Emitido', 'anulado' => 'Anulado', 'rechazado' => 'Rechazado'])
                    ->required(),
                TextInput::make('motivo_anulacion'),
                TextInput::make('hash_sunat'),
                TextInput::make('codigo_sunat'),
                Textarea::make('xml_firmado')
                    ->columnSpanFull(),
                Textarea::make('cdr_respuesta')
                    ->columnSpanFull(),
            ]);
    }
}
