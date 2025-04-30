<?php

namespace App\Http\Controllers;

use App\Adapters\AdapterFactory;
use App\Adapters\ExternalApiAdapterInterface;
use Carbon\Carbon;
use Illuminate\Support\Number;

class AdapterController extends Controller
{
    private string $externalApi;
    private ExternalApiAdapterInterface $adapter;

    public function __construct()
    {
        $this->externalApi = request()->get('externalApi') ?? AdapterFactory::DEFAULT_ADAPTER;
        $this->adapter = AdapterFactory::getAdapter($this->externalApi);
    }

    public function getCurrentPrice()
    {
        $humanReadable = request()->get('hr') ?? false;
        $value = $humanReadable ?
            Number::currency($this->adapter->getCurrentPrice()):
            $this->adapter->getCurrentPrice();

        return response()->json(["value" => $value]);
    }

    public function getCurrentPriceFull()
    {
        $currency = config('btc.currency');

        return response()->json(["btc/{$currency}" => $this->adapter->getCurrentDailyPrice()]);
    }

    public function getDailyPriceInterval(
        string $startDate,
        string $endDate,
        string $externalApi = AdapterFactory::DEFAULT_ADAPTER
    ) {
        $start = Carbon::createFromFormat(config('btc.date_format'), $startDate);
        $end = Carbon::createFromFormat(config('btc.date_format'), $endDate);

        return response()->json($this->adapter->getDailyPriceInterval($start, $end));
    }

    public function getBtcPriceByDays(string $days)
    {
        $days = explode(',', $days);

        return response()->json($this->adapter->getBtcPriceByDays($days));
    }
}
