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
                    ->formatStateUsing(fn($state) => $state ? (strlen($state) > 50 ? substr($state, 0, 50) . '...' : $state) : '')
                    ->tooltip(fn ($record) => $record->descripcion)
                    ->extraAttributes(['style' => 'max-width:160px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;'])
                    ->size('sm'),

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
        $esSuperAdmin = \Illuminate\Support\Facades\Auth::check() && optional(\Illuminate\Support\Facades\Auth::user())->hasRole('super_admin');

        // Si super_admin seleccionó una caja en sesión, usarla
        $cajaAbierta = null;
        if ($esSuperAdmin && session('admin_selected_caja_id')) {
            $cajaAbierta = Caja::find(session('admin_selected_caja_id'));
            if (! $cajaAbierta || $cajaAbierta->estado !== 'abierta') {
                // La caja seleccionada ya no es válida -> limpiar y continuar con fallbacks
                session()->forget('admin_selected_caja_id');
                $cajaAbierta = null;
            }
        }

        if (! $cajaAbierta) {
            // Preferir caja propia del super_admin del día actual
            if ($esSuperAdmin) {
                $cajaAbierta = Caja::where('estado', 'abierta')
                    ->whereDate('fecha_apertura', today())
                    ->where('user_id', \Illuminate\Support\Facades\Auth::id())
                    ->orderByDesc('fecha_apertura')
                    ->first();
            }
        }

        if (! $cajaAbierta) {
            $query = Caja::where('estado', 'abierta')
                ->whereDate('fecha_apertura', today())
                ->orderByDesc('fecha_apertura');

            if (! $esSuperAdmin) {
                $query->where('user_id', \Illuminate\Support\Facades\Auth::id());
            }

            $cajaAbierta = $query->first();
        }

        if (!$cajaAbierta) {
            return MovimientoCaja::query()->whereRaw('1 = 0'); // Query vacío
        }

        return MovimientoCaja::query()
            ->where('caja_id', $cajaAbierta->id);
    }

    public function isTableVisible(): bool
    {
        $query = Caja::where('estado', 'abierta')
            ->whereDate('fecha_apertura', today());

        $esSuperAdmin = \Illuminate\Support\Facades\Auth::check() && optional(\Illuminate\Support\Facades\Auth::user())->hasRole('super_admin');
        if (! $esSuperAdmin) {
            $query->where('user_id', \Illuminate\Support\Facades\Auth::id());
        }

        return $query->exists();
    }
}
