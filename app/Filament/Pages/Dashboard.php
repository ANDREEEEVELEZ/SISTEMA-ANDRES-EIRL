<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;
use BackedEnum;
use Illuminate\Support\Facades\Auth;
use App\Filament\Widgets\ProductosMasVendidosWidget;
use App\Filament\Widgets\ProductosStockCriticoWidget;
use App\Filament\Widgets\EstadisticasPrincipalesWidget;
use App\Filament\Widgets\EstadisticasGeneralesWidget;
use App\Filament\Widgets\VentasTotalesWidget;
use App\Filament\Widgets\VentasPorCategoriaWidget;
use App\Filament\Widgets\VentasPorDiaSemanaWidget;
use App\Filament\Widgets\IngresosEgresosDonutWidget;
use App\Filament\Widgets\CategoriasStatsWidget;
use App\Filament\Resources\Asistencias\AsistenciaResource;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationLabel = 'Panel Administrativo';

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-home';

    public function getTitle(): string
    {
        return 'Panel Administrativo';
    }

    public function getWidgets(): array
    {
        $widgets = parent::getWidgets();

        // Convertir a array indexado
        $widgetsList = array_values($widgets);

        // Remover todos los widgets personalizados que vamos a reordenar manualmente
        $widgetsToRemove = [
            CategoriasStatsWidget::class,
            EstadisticasGeneralesWidget::class,
            EstadisticasPrincipalesWidget::class,
            VentasTotalesWidget::class,
            VentasPorCategoriaWidget::class,
            ProductosStockCriticoWidget::class,
            ProductosMasVendidosWidget::class,
        ];

        $widgetsList = array_values(array_filter($widgetsList, function ($widget) use ($widgetsToRemove) {
            return !in_array($widget, $widgetsToRemove, true);
        }));

        // Ahora agregamos los widgets en el orden deseado al inicio
        // EstadisticasGeneralesWidget primero (Total Clientes, Ventas del Mes, etc.)
        array_unshift(
            $widgetsList,
            EstadisticasGeneralesWidget::class,
            EstadisticasPrincipalesWidget::class,
            // TABLAS primero
            ProductosStockCriticoWidget::class,
            ProductosMasVendidosWidget::class,
            // Luego los GRÁFICOS
            VentasTotalesWidget::class,
            VentasPorCategoriaWidget::class,
            VentasPorDiaSemanaWidget::class,
            IngresosEgresosDonutWidget::class
        );

        // Eliminar duplicados manteniendo el orden (puede haber aparecido por autodetección)
        $widgetsList = array_values(array_unique($widgetsList));

        return $widgetsList;
    }

    public static function shouldRegisterNavigation(): bool
    {
        try {
            if (! Auth::check()) {
                return false;
            }

            return optional(Auth::user())->hasRole('super_admin');
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function mount(): void
    {

        if (Auth::check() && ! optional(Auth::user())->hasRole('super_admin')) {
            $url = AsistenciaResource::getUrl('index');
            $this->redirect($url ?: '/');
            return;
        }
    }
}
