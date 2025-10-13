<?php

namespace App\Services;

use App\Models\Caja;
use Carbon\Carbon;

class CajaService
{
    /**
     * Verificar si hay una caja abierta del día anterior
     */
    public static function tieneCajaAbiertaDiaAnterior(): bool
    {
        $cajaAbierta = Caja::where('estado', 'abierta')->first();

        if (!$cajaAbierta) {
            return false;
        }

        $fechaApertura = Carbon::parse($cajaAbierta->fecha_apertura);
        $hoy = Carbon::now();

        // Verificar si la caja fue abierta en un día diferente al actual
        return !$fechaApertura->isSameDay($hoy);
    }

    /**
     * Obtener la caja abierta del día anterior
     */
    public static function getCajaAbiertaDiaAnterior(): ?Caja
    {
        $cajaAbierta = Caja::where('estado', 'abierta')->first();

        if (!$cajaAbierta) {
            return null;
        }

        $fechaApertura = Carbon::parse($cajaAbierta->fecha_apertura);
        $hoy = Carbon::now();

        // Retornar la caja solo si fue abierta en un día diferente al actual
        return !$fechaApertura->isSameDay($hoy) ? $cajaAbierta : null;
    }

    /**
     * Verificar si hay una caja abierta del día actual
     */
    public static function tieneCajaAbiertaHoy(): bool
    {
        $cajaAbierta = Caja::where('estado', 'abierta')->first();

        if (!$cajaAbierta) {
            return false;
        }

        $fechaApertura = Carbon::parse($cajaAbierta->fecha_apertura);
        $hoy = Carbon::now();

        // Verificar si la caja fue abierta hoy
        return $fechaApertura->isSameDay($hoy);
    }

    /**
     * Obtener mensaje de advertencia para caja del día anterior
     */
    public static function getMensajeAdvertenciaCajaAnterior(): string
    {
        $cajaAnterior = self::getCajaAbiertaDiaAnterior();

        if (!$cajaAnterior) {
            return '';
        }

        return "ADVERTENCIA: Hay una caja abierta desde el {$cajaAnterior->fecha_apertura->format('d/m/Y H:i')}. " .
               "Debe cerrarla manualmente antes de continuar con las operaciones del sistema.";
    }
}
