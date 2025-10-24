<?php

namespace App\Filament\Resources\Cajas\Widgets;

use App\Models\Caja;
use App\Models\MovimientoCaja;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class MovimientosCajaTable extends TableWidget
{
   // protected int | string | array $columnSpan = 'full';
    protected int | string | array $columnSpan = 1;

    protected static ?string $heading = 'Movimientos extra de Caja del Día';

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => $this->getTableQuery())
            ->columns([
                BadgeColumn::make('tipo')
                    ->label('Tipo')
                    ->formatStateUsing(fn (string $state): string => $state === 'ingreso' ? '🟢 Ingreso' : '🔴 Egreso')
                    ->colors([
                        'success' => 'ingreso',
                        'danger' => 'egreso',
                    ]),

                TextColumn::make('monto')
                    ->label('Monto')
                    ->money('PEN')
                    ->weight('bold')
                    ->sortable(),

                TextColumn::make('descripcion')
                    ->label('Descripción')
                    ->wrap()
                    ->searchable(),

                TextColumn::make('created_at')
                    ->label('Fecha/Hora')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->size('sm'),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('No hay movimientos registrados hoy')
            ->emptyStateDescription('Los movimientos de ingreso y egreso aparecerán aquí')
            ->emptyStateIcon('heroicon-o-banknotes')
            ->poll('10s'); // Actualizar cada 10 segundos
    }

    protected function getTableQuery(): Builder
    {
        $cajaAbierta = Caja::where('estado', 'abierta')
            ->whereDate('fecha_apertura', today())
            ->first();

        if (!$cajaAbierta) {
            return MovimientoCaja::query()->whereRaw('1 = 0'); // Query vacío
        }

        return MovimientoCaja::query()
            ->where('caja_id', $cajaAbierta->id);
    }

    public function isTableVisible(): bool
    {
        return Caja::where('estado', 'abierta')
            ->whereDate('fecha_apertura', today())
            ->exists();
    }
}
