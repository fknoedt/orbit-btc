<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Auth\Login;
use App\Filament\Pages\Auth\Register;
use App\Filament\Pages\CustomDashboard;
use App\Filament\Pages\PerformancePage;
use App\Http\Middleware\LogUserActivity;
use App\Http\Middleware\RedirectFirstLogin;
use App\Models\UserActivityLog;
use App\Services\WidgetService;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationItem;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Str;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Leandrocfe\FilamentApexCharts\FilamentApexChartsPlugin;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        $service = new WidgetService();
        return $panel
            ->default()
            ->id('app')
            ->path('app')
            ->login(Login::class)
            ->registration(Register::class)
            ->brandLogo(fn() => view('filament.components.header-left'))
            ->sidebarCollapsibleOnDesktop()
            ->viteTheme('resources/css/filament.css')
            ->maxContentWidth('full')
            ->colors([
                'primary' => Color::Orange,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                CustomDashboard::class,
                PerformancePage::class
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
                RedirectFirstLogin::class,
                LogUserActivity::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->navigationGroups([
                'Tools',
                'Settings',
                'User Admin',
            ])
            ->navigationItems([
                NavigationItem::make('Sandbox')
                    ->url('/sandbox', shouldOpenInNewTab: true)
                    ->icon('heroicon-o-inbox')
                    ->group('Dev')
                    ->sort(5)
                    ->visible(fn () => auth()->user()?->role_id === config('data.role_id.super_admin') && app()->environment('local')),
            ])
            ->sidebarWidth('250')
            ->brandName(config('app.name'))
            ->favicon(asset('images/orbit-btc.ico'))
            ->plugins([FilamentApexChartsPlugin::make()])
            ->renderHook(
                'panels::topbar.start',
                fn () => view('filament.topbar-widgets', ['widgets' => $service->getTopbarWidgets()])
            );
    }

    /**
     * Get the snake_case name of the resource from its class.
     *
     * @param  string  $resourceClass
     * @return string
     */
    private function getResourceName(string $resourceClass): string
    {
        $className = class_basename($resourceClass);
        return Str::snake(str_replace('Resource', '', $className));
    }
}
