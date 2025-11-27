<?php

namespace App\Filament\Resources\Cajas\Pages;

use App\Filament\Resources\Cajas\CajaResource;
use App\Models\Caja;
use App\Services\CajaService;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;

class CreateCaja extends CreateRecord
{
    protected static string $resource = CajaResource::class;

    protected function beforeFill(): void
    {
        // Verificar si hay una caja del día anterior sin cerrar
        $cajaAnterior = CajaService::getCajaAbiertaDiaAnterior();

        if ($cajaAnterior) {
            Notification::make()
                ->title('ADVERTENCIA CRÍTICA: Caja del día anterior sin cerrar')
                ->danger()
                ->body("Hay una caja abierta desde el {$cajaAnterior->fecha_apertura->format('d/m/Y H:i')}. DEBE cerrarla MANUALMENTE antes de crear una nueva caja. El sistema NO la cerrará automáticamente.")
                ->persistent()
                ->send();

            // Redireccionar a editar la caja anterior
            $this->redirect(CajaResource::getUrl('edit', ['record' => $cajaAnterior]));
            return;
        }

        // Verificar si hay una caja del día actual
        if (CajaService::tieneCajaAbiertaHoy()) {
            $query = Caja::where('estado', 'abierta')->whereDate('fecha_apertura', today())->orderByDesc('fecha_apertura');
            $esSuperAdmin = \Illuminate\Support\Facades\Auth::check() && optional(\Illuminate\Support\Facades\Auth::user())->hasRole('super_admin');

            // Buscar si el usuario actual ya tiene una caja abierta hoy
            $cajaPropia = (clone $query)->where('user_id', \Illuminate\Support\Facades\Auth::id())->first();

            // Buscar la caja abierta más reciente (cualquiera)
            $cajaHoy = (clone $query)->first();

            if ($cajaPropia) {
                // El usuario ya tiene su propia caja abierta hoy -> mostrar aviso y no permitir crear otra
                Notification::make()
                    ->title('Ya tiene una caja abierta hoy')
                    ->warning()
                    ->body("Usted ya abrió una caja hoy a las {$cajaPropia->fecha_apertura->format('H:i')}. No puede abrir otra caja el mismo día.")
                    ->persistent()
                    ->send();
            } else {
                // El usuario NO tiene caja propia abierta hoy
                if ($esSuperAdmin) {
                    // Para super_admin: permitir crear SU propia caja aunque exista otra abierta por otro usuario.
                    // Mostramos una notificación informativa, pero NO redirigimos ni bloqueamos.
                    if ($cajaHoy) {
                        Notification::make()
                            ->title('Atención: otra caja abierta')
                            ->warning()
                            ->body("Hay otra caja abierta hoy (usuario #{$cajaHoy->user_id}) desde las {$cajaHoy->fecha_apertura->format('H:i')}. Como super_admin puede crear su propia caja si lo desea.")
                            ->persistent()
                            ->send();
                    }
                } else {
                    // No es super_admin: comportamiento previene crear otra caja
                    if ($cajaHoy) {
                        Notification::make()
                            ->title('Ya existe una caja abierta hoy')
                            ->warning()
                            ->body("Hay una caja abierta desde las {$cajaHoy->fecha_apertura->format('H:i')}. No puede crear otra caja el mismo día.")
                            ->persistent()
                            ->send();
                    }
                }
            }
        }
    }

    protected function beforeCreate(): void
    {
        // Verificar caja del día anterior
        if (CajaService::tieneCajaAbiertaDiaAnterior()) {
            Notification::make()
                ->title(' ERROR: Caja del día anterior sin cerrar')
                ->danger()
                ->body('Debe cerrar MANUALMENTE la caja del día anterior antes de crear una nueva. El sistema no permitirá continuar hasta que la cierre.')
                ->persistent()
                ->send();

            $this->halt();
        }

        // Verificar caja del día actual: permitir que super_admin cree su propia caja
        if (CajaService::tieneCajaAbiertaHoy()) {
            $esSuperAdmin = \Illuminate\Support\Facades\Auth::check() && optional(\Illuminate\Support\Facades\Auth::user())->hasRole('super_admin');

            // Si el usuario ya tiene una caja abierta hoy, impedir creación
            $cajaPropia = Caja::where('estado', 'abierta')
                ->whereDate('fecha_apertura', today())
                ->where('user_id', \Illuminate\Support\Facades\Auth::id())
                ->first();

            if ($cajaPropia) {
                Notification::make()
                    ->title(' ERROR: Ya tiene una caja abierta')
                    ->danger()
                    ->body('Usted ya tiene una caja abierta hoy. No puede crear otra.')
                    ->persistent()
                    ->send();

                $this->halt();
            }

            // Si NO es super_admin y NO tiene caja propia, bloquear la creación
            if (! $esSuperAdmin) {
                Notification::make()
                    ->title('No se puede crear la caja')
                    ->danger()
                    ->body('Ya existe una caja abierta hoy. Solo se puede tener una caja por día.')
                    ->persistent()
                    ->send();

                $this->halt();
            }

            // Si es super_admin y NO tiene caja propia -> permitimos crear (solo aviso mostrado en beforeFill)
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
