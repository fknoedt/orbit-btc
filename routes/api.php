<?php

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
Route::controller(AdapterController::class)->group(function () {
    Route::get(
        '/current-price',
        'getCurrentPrice'
    )->name('api.currentPrice');
    Route::get(
        '/current-price/full',
        'getCurrentPriceFull'
    )->name('api.currentPriceFull');
    Route::get(
        '/price-history/{startDate}/{endDate}',
        'getDailyPriceInterval'
    )->name('api.priceHistory');
    Route::get(
        '/price-by-days/{days}',
        'getBtcPriceByDays'
    )->name('api.priceHistory');
});
