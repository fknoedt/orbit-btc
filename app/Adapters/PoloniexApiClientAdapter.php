<?php

namespace App\Adapters;

use App\Services\ExternalApiClientInterface;
use Carbon\Carbon;
use ccxt\coinbase;
use ccxt\Exchange;

/**
 * @see https://github.com/ccxt/ccxt
 */
class PoloniexApiClientAdapter extends BaseClientAdapter implements ExternalApiClientInterface
{
    private Exchange $client;
    private string $currency;
    private const ADAPTER_NAME = 'CCXT';
    private const DEFAULT_EXCHANGE = 'poloniex';
    protected static int $dataSourceId = 0; // TODO: add it

    public function __construct()
    {
        $this->currency = config('btc.currency') ?? 'usd';
        $this->client = new \ccxt\poloniex();
    }


        /*
//        $zaif     = new \ccxt\zaif     (array (
//        'apiKey' => 'YOUR_PUBLIC_API_KEY',
//        'secret' => 'YOUR_SECRET_PRIVATE_KEY',
//        ));
//        $hitbtc   = new \ccxt\hitbtc   (array (
//        'apiKey' => 'YOUR_PUBLIC_API_KEY',
//        'secret' => 'YOUR_SECRET_PRIVATE_KEY',
//        ));
//
//        $exchange_id = 'binance';
//        $exchange_class = "\\ccxt\\$exchange_id";
//        $exchange = new $exchange_class (array (
//        'apiKey' => 'YOUR_API_KEY',
//        'secret' => 'YOUR_SECRET',
//        ));

        $poloniex_markets = $poloniex->load_markets();

        dump($poloniex_markets);
        dump($bittrex->load_markets());

        dump($poloniex->fetch_order_book($poloniex->symbols[0]));
        dump($bittrex->fetch_trades('BTC/USD'));
        //var_dump ($zaif->fetch_ticker ('BTC/JPY'));

        //var_dump ($zaif->fetch_balance ());

        // sell 1 BTC/JPY for market price, you pay ¥ and receive ฿ immediately
        //var_dump ($zaif->id, $zaif->create_market_sell_order ('BTC/JPY', 1));

        // buy BTC/JPY, you receive ฿1 for ¥285000 when the order closes
        //var_dump ($zaif->id, $zaif->create_limit_buy_order ('BTC/JPY', 1, 285000));

        // set a custom user-defined id to your order
        //$hitbtc->create_order ('BTC/USD', 'limit', 'buy', 1, 3000, array ('clientOrderId' => '123'));
        */

    public function getCurrentBtcPrice(array $options = []): array
    {
        return $this->client->fetch_ticker('BTC/USDD');
    }

    public function getBtcPriceInterval(Carbon $startDate, Carbon $endDate): array
    {
        //$startDate = (new Carbon('2013-04-27'));
        //$timestampTo = (new Carbon('2013-05-01'));

        $currency = config('btc.currency');
        $dateFormat = config('btc.date_format');
        $btcData = [];

        while ($startDate->addDay() < $endDate) {

        }

        dd($btcData);

        foreach ($marketChart as $info => $data) {
            dump($info, count($data));
        }
    }

    public function getBtcPriceByDays(array $days): array
    {
        throw new \BadMethodCallException(__METHOD__ . ' is not implemented');
    }
}
