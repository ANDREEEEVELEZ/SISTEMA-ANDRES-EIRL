<?php

namespace App\Filament\Resources\Cajas\Pages;

use App\Filament\Resources\Cajas\CajaResource;
use App\Models\Arqueo;
use Filament\Resources\Pages\Page;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Actions\Action;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ArqueosIndex extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $title = 'Reportes de Arqueo';

    // Asociar este Page al resource para evitar acceder a Page::$resource sin inicializar
    protected static string $resource = CajaResource::class;

    protected string $view = 'filament.resources.cajas.pages.arqueos-index';

    protected function getTableQuery(): Builder
    {
        $query = Arqueo::query()->with(['caja', 'user']);

        $user = Auth::user();
        if ($user && ! $user->hasRole('super_admin')) {
            $query->where('user_id', $user->id);
        }

        return $query;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => $this->getTableQuery())
            ->columns([
              //  TextColumn::make('id')->label('ID')->sortable()->size('sm'),
               // TextColumn::make('caja.id')->label('Caja')->sortable()->size('sm'),
                TextColumn::make('user.name')->label('Usuario')->sortable()->searchable()->size('sm'),
                TextColumn::make('fecha_inicio')->label('Inicio')->dateTime('d/m/Y H:i')->sortable()->size('sm'),
                TextColumn::make('fecha_fin')->label('Fin')->dateTime('d/m/Y H:i')->sortable()->size('sm'),
                TextColumn::make('saldo_teorico')->label('Saldo teórico')->money('PEN')->size('sm'),
                TextColumn::make('efectivo_contado')->label('Efectivo contado')->money('PEN')->size('sm'),
                TextColumn::make('diferencia')->label('Diferencia')->money('PEN')->size('sm'),
                IconColumn::make('estado')
                    ->label('Confirmado')
                    ->getStateUsing(fn ($record) => (
                        is_object($record->estado) ? ($record->estado->value ?? '') : ($record->estado ?? '')
                    ) === 'confirmado')
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
              //  TextColumn::make('created_at')->label('Creado')->dateTime('d/m/Y H:i')->size('sm'),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Action::make('ver_pdf')
                    ->label('PDF')
                    ->icon('heroicon-o-document-text')
                    ->url(fn (Arqueo $record): string => route('reportes.arqueo', ['id' => $record->id]))
                    ->openUrlInNewTab(),
                Action::make('editar')
                    ->label('Editar')
                    ->icon('heroicon-o-pencil')
                    ->url(fn (Arqueo $record): string => CajaResource::getUrl('arqueo-caja') . '?arqueo_id=' . $record->id),
            ])
            ->headerActions([
                Action::make('registrar_arqueo')
                    ->label('Registrar Arqueo')
                    ->icon('heroicon-o-plus')
                    ->color('primary')
                    ->url(CajaResource::getUrl('arqueo-caja')),
            ])
            ->emptyStateHeading('No hay reportes de arqueo')
            ->emptyStateDescription('Aquí aparecerán los arqueos realizados.');
    }
}
