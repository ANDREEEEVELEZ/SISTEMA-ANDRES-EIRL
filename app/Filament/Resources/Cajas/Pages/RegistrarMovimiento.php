<?php

namespace App\Filament\Resources\Cajas\Pages;

use App\Filament\Resources\Cajas\CajaResource;
use App\Models\Caja;
use App\Models\MovimientoCaja;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ViewField;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;

class RegistrarMovimiento extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string $resource = CajaResource::class;

    protected string $view = 'filament.resources.cajas.pages.registrar-movimiento';

    protected static ?string $title = 'Registrar Movimiento de Caja';

    public ?array $data = [];

    public function mount(): void
    {
        // Verificar que hay una caja abierta
        if (!$this->tieneCajaAbierta()) {
            Notification::make()
                ->title('Sin caja abierta')
                ->body('No hay una caja abierta. Por favor, abra una caja primero.')
                ->warning()
                ->send();

            $this->redirect(CajaResource::getUrl('index'));
            return;
        }

        $this->form->fill();
    }

    protected function getFormSchema(): array
    {
        return [
            Select::make('tipo')
                ->label('Tipo de Movimiento')
                ->options([
                    'egreso' => '🔴 Egreso (Gasto)',
                    'ingreso' => '🟢 Ingreso',
                ])
                ->default('egreso')
                ->required()
                ->native(false),

            TextInput::make('monto')
                ->label('Monto (S/)')
                ->numeric()
                ->prefix('S/')
                ->minValue(0.01)
                ->step(0.01)
                ->required()
                ->placeholder('0.00'),

            Textarea::make('descripcion')
                ->label('Descripción')
                ->required()
                ->maxLength(100)
                ->placeholder('Ej: Compra de servilletas, Pago de luz, etc.')
                ->rows(3),

            ViewField::make('info_caja')
                ->label('Caja Actual')
                ->view('filament.forms.components.caja-info')
                ->viewData([
                    'info' => $this->getInfoCajaAbierta()
                ]),
        ];
    }

    public function create(): void
    {
        $data = $this->form->getState();

        $caja = $this->getCajaAbierta();

        if (!$caja) {
            Notification::make()
                ->title('Error')
                ->body('No hay una caja abierta')
                ->danger()
                ->send();
            return;
        }

        MovimientoCaja::create([
            'caja_id' => $caja->id,
            'tipo' => $data['tipo'],
            'monto' => $data['monto'],
            'descripcion' => $data['descripcion'],
            'fecha_movimiento' => now()->toDateString(),
        ]);

        $tipoTexto = $data['tipo'] === 'ingreso' ? 'Ingreso' : 'Egreso';

        Notification::make()
            ->title('Movimiento Registrado')
            ->body("{$tipoTexto} de S/ " . number_format($data['monto'], 2) . " registrado correctamente")
            ->success()
            ->send();

        // Redirigir a la página principal de cajas
        $this->redirect(CajaResource::getUrl('index'));
    }

    protected function tieneCajaAbierta(): bool
    {
        return Caja::where('estado', 'abierta')
            ->whereDate('fecha_apertura', today())
            ->exists();
    }

    protected function getCajaAbierta(): ?Caja
    {
        return Caja::where('estado', 'abierta')
            ->whereDate('fecha_apertura', today())
            ->first();
    }

    protected function getInfoCajaAbierta(): string
    {
        $caja = $this->getCajaAbierta();

        if (!$caja) {
            return ' No hay caja abierta';
        }

        return sprintf(
            ' Caja #%d - Abierta el %s - Saldo Inicial: S/ %s',
            $caja->id,
            $caja->fecha_apertura->format('d/m/Y H:i'),
            number_format((float) $caja->saldo_inicial, 2)
        );
    }

    protected function getFormStatePath(): ?string
    {
        return 'data';
    }
}
