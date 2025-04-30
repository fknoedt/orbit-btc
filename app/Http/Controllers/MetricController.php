<?php

namespace App\Http\Controllers;

use App\Clients\MempoolClient;
use App\Exceptions\ExternalApiException;
use App\Services\Btc3rdPartyService;
use Illuminate\Support\Number;

class MetricController extends Controller
{
    public function getRecommendedFee()
    {
        $client = new MempoolClient();

        $recommendedFees = $client->request(
            'get',
            "fees/recommended"
        );

        $humanReadable = request()->get('hr') ?? false;
        $value = $humanReadable ?
            $recommendedFees['fastestFee'] . ' sats/vB':
            $recommendedFees['fastestFee'];

        return response()->json(["value" => $value]);
    }

    public function getBtcDominance()
    {
        // get BTC change in the last day
        $service = new Btc3rdPartyService('CoinMarketCap');
        $stats = $service->getCurrentPriceStats();

        if (empty($stats['market_cap_dominance'])) {
            throw new ExternalApiException(
                __METHOD__ .
                ': `market_cap_dominance` is empty: ' .
                json_encode($stats)
            );
        }

        $humanReadable = request()->get('hr') ?? false;
        $value = $humanReadable ?
            Number::percentage($stats['market_cap_dominance'], 2):
            $stats['market_cap_dominance'];

        return response()->json(["value" => $value]);
    }
}
