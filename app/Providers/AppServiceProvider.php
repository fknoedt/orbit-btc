<?php

namespace App\Providers;

use App\Clients\XClient;
use Filament\Support\Facades\FilamentView;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $xClient = new XClient();
        $topTweet = $xClient->getTopPost();

        FilamentView::registerRenderHook(
            'panels::topbar.start', // Inject at the start of the topbar (after the logo)
            fn (): string =>
                Blade::render('<div class="flex-1 flex justify-center"><x-header-middle-container /></div>', [
                    'topTweet' => $topTweet,
                ]),
        );
    }
}
