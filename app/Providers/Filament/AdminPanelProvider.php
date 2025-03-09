<?php

namespace App\Providers\Filament;

use App\Filament\Pages\UserModelScore;
use App\Services\WidgetService;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationItem;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Leandrocfe\FilamentApexCharts\FilamentApexChartsPlugin;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        $service = new WidgetService();
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->maxContentWidth('full')
            ->colors([
                'primary' => Color::Orange,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
                UserModelScore::class
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets($service->getUserWidgets())
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
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->navigationGroups([
                'Tools',
                'Settings',
                'User Management',
            ])
            ->navigationItems([
                NavigationItem::make('Sandbox')
                    ->url('/sandbox', shouldOpenInNewTab: true)
                    ->icon('heroicon-o-inbox')
                    ->group('Dev')
                    ->sort(5)
                ]
            )
            ->sidebarWidth('250')
            // ->brandLogo('images/btc-eagle-logo.png')
            ->brandName(config('app.name'))
            ->favicon(asset('images/orbit-btc.ico'))
            ->plugins([FilamentApexChartsPlugin::make()]);
    }
}
