<?php

namespace App\Http\Controllers;

use App\Adapters\AdapterFactory;
use App\Services\ExternalApiClientInterface;
use Carbon\Carbon;

class AdapterController extends Controller
{
    private string $externalApi;
    private ExternalApiClientInterface $adapter;

    public function __construct()
    {
        $this->externalApi = request()->get('externalApi') ?? AdapterFactory::DEFAULT_ADAPTER;
        $this->adapter = AdapterFactory::getAdapter($this->externalApi);
    }

    public function getCurrentBtcPrice()
    {
        $currency = config('btc.currency');

        return response()->json(["btc/{$currency}" => $this->adapter->getCurrentBtcPrice()]);
    }

    public function getBtcPriceInterval(
        string $startDate,
        string $endDate,
        string $externalApi = AdapterFactory::DEFAULT_ADAPTER
    ) {
        $start = Carbon::createFromFormat(config('btc.date_format'), $startDate);
        $end = Carbon::createFromFormat(config('btc.date_format'), $endDate);

        return response()->json($this->adapter->getBtcPriceInterval($start, $end));
    }

    public function getBtcPriceByDays(string $days)
    {
        $days = explode(',', $days);

        return response()->json($this->adapter->getBtcPriceByDays($days));
    }
}
