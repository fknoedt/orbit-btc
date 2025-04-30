<?php

use App\Http\Controllers\DailyPriceController;
use App\Http\Controllers\MetricController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdapterController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

/*Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});*/

// wrapper general endpoints
Route::middleware(['can:viewAny,App\Models\DailyPrice', 'auth.api-client'])
    ->controller(AdapterController::class)->group(function () {

        Route::get('/current-price', 'getCurrentPrice')
            ->name('api.currentPrice');

        Route::get('/current-price/full', 'getCurrentPriceFull')
            ->name('api.currentPriceFull');

        // Routes within the main group but outside the subgroup
        Route::get('/price-history/{startDate}/{endDate}', 'getDailyPriceInterval')
            ->name('api.priceHistory');

        Route::get('/price-by-days/{days}', 'getBtcPriceByDays')
            ->name('api.priceHistoryByDays');
});

Route::get('/recommended-fee', [MetricController::class, 'getRecommendedFee'])
    ->middleware('can:viewAny,App\Models\Metric')
    ->name('api.recommendedFee');

Route::get('/btc-dominance', [MetricController::class, 'getBtcDominance'])
    ->middleware('can:viewAny,App\Models\Metric')
    ->name('api.btcDominance');


Route::middleware('auth.api-client')->group(function () {
    Route::post('/update-daily-prices', [DailyPriceController::class, 'updateDailyPrices'])
        ->name('api.updateDailyPrices');
});
