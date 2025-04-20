<?php

namespace App\Http\Controllers;

use App\Exceptions\DailyPriceStatsException;
use App\Services\DailyStatsService;
use Illuminate\Http\Request;

class DailyPriceController extends Controller
{
    /**
     * @throws DailyPriceStatsException
     */
    public function updateDailyPrices(Request $request): \Illuminate\Http\JsonResponse
    {
        $dailyPrices = $request->input('daily_prices');
        $pricesUpdated = (new DailyStatsService())->fillStats($dailyPrices);

        return response()->json(['daily-prices-updated' => $pricesUpdated]);
    }
}
