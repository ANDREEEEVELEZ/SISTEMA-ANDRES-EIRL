<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use App\Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Assets\Css;
use Filament\Support\Facades\FilamentAsset;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;




class AdminPanelProvider extends PanelProvider
{
    public function boot(): void
    {
        // Registrar archivo CSS compilado usando manifest.json para ser robusto a nombres con hash
        $themeUrl = $this->getThemeAssetUrl();

        FilamentAsset::register([
            Css::make('custom-theme', $themeUrl),
        ]);
    }

    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->passwordReset()
            //->brandLogo(asset('images/AndresEIRL.png'))
            ->brandLogoHeight('120px')
            ->colors([
                'primary' => '#1E4E8C',
            ])
            ->renderHook(
                'panels::head.end',

                fn (): string => '<link rel="stylesheet" href="' . $this->getThemeAssetUrl() . '?v=' . time() . '">'
            )
            /*->colors([
                'primary' => [
                    50 => '#fcf1f4',
                    100 => '#fae8ed',
                    200 => '#f7d1dd',
                    300 => '#f2adc2',
                    400 => '#ea7ea0',
                    500 => '#de557e',
                    600 => '#c93862',
                    700 => '#9b2c4d',
                    800 => '#822542',
                    900 => '#6e233a',
                    950 => '#3f101c',
                ],
            ])*/
            ->darkMode(false)

            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                //AccountWidget::class,
               // FilamentInfoWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
                \App\Http\Middleware\CheckUserActive::class,
            ])
            ->plugins([
                FilamentShieldPlugin::make(),
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }

    /**
     * Obtener la URL del asset del theme leyendo el manifest.json.
     * Devuelve fallback si no se encuentra.
     */
    private function getThemeAssetUrl(): string
    {
        try {
            $manifestPath = public_path('build/manifest.json');

            if (file_exists($manifestPath)) {
                $content = json_decode(file_get_contents($manifestPath), true);
                $key = 'resources/css/filament/dashboard/theme.css';

                if (isset($content[$key]) && isset($content[$key]['file'])) {
                    return asset('build/' . $content[$key]['file']);
                }
            }
        } catch (\Throwable $e) {
            // silencioso: usaremos fallback abajo
        }

        // Fallback estático (último conocido). Cambia si compilas y obtienes otro nombre.
        return asset('build/assets/theme-Q82TI-mq.css');
    }
}
