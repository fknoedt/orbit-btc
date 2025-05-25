<?php

use App\Filament\Pages\AngelsAndPartnersPage;
use App\Filament\Pages\PerformancePage;
use App\Filament\Pages\SponsorshipAndPartnership;
use App\Http\Controllers\AdapterController;
use App\Http\Controllers\BtcRpcController;
use App\Http\Controllers\InvestorInquiryController;
use App\Http\Controllers\MetricController;
use App\Http\Controllers\SandboxController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::redirect('/', '/admin');

Route::redirect('/business-card', '/angels-and-partners?ref=bc');

if (config('app.env') === 'local') {
    Route::get('/sandbox', [SandboxController::class, 'index']);
}

Route::get('/angels-and-partners', [AngelsAndPartnersPage::class, 'render'])
    ->name('angels-and-partners');

Route::post('/investor-inquiry', [InvestorInquiryController::class, 'store'])->name('investor-inquiry.store');

Route::get('/admin/user-signal-score/{id}', PerformancePage::class)
    ->middleware('auth')
    ->name('user-signal-score-id');

// Duplicated API routes for web access
Route::middleware('auth')
    ->prefix('web-api') // Add a prefix to avoid conflicts with api.php
    ->group(function () {
        Route::get('/current-price', [AdapterController::class, 'getCurrentPrice'])
            ->middleware('can:viewAny,App\Models\DailyPrice')
            ->name('web-api.currentPrice');

        Route::get('/recommended-fee', [MetricController::class, 'getRecommendedFee'])
            ->middleware('can:viewAny,App\Models\Metric')
            ->name('web-api.recommendedFee');

        Route::get('/btc-dominance', [MetricController::class, 'getBtcDominance'])
            ->middleware('can:viewAny,App\Models\Metric')
            ->name('web-api.btcDominance');
    });


/**
 * @see https://developer.bitcoin.org/reference/rpc/index.html
 */
if (config('app.env') === 'local' && auth()->user() && auth()->user()->role_id === config('data.role_id.super_admin')) {
    Route::controller(BtcRpcController::class)->prefix('rpc')->group(function () {
        Route::get('/help', 'help');
        Route::get('/command/{command}', 'runCommand')->name('rpc-command.run');
        Route::get('/command/{command}/docs', 'commandDocs')->name('rpc-command.docs');
    });
}
