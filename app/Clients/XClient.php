<?php

namespace App\Clients;

use App\Exceptions\AdapterException;
use App\Exceptions\ExternalApiException;
use Carbon\Carbon;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;

class XClient extends BaseClient
{
    const int TOP_POST_HOURS_BACK = 24;
    const int TOP_POST_MAX_FETCH = 10;

    private string $version = '2';

    protected string $bearerToken;

    public function __construct()
    {
        parent::__construct();
        if (! self::$url = config('btc.apis.x.url')) {
            throw new \RuntimeException('X url is not defined');
        }

        self::$url .= '/' . $this->version . '/';

        if (! $this->bearerToken = config('btc.apis.x.token')) {
            throw new \RuntimeException('X token is not defined');
        }

        self::$dataSourceId = config('data.data_source.x_id');
    }

    /**
     * @throws AdapterException
     * @throws ExternalApiException
     * @throws ConnectionException
     * @throws RequestException
     */
    public function getTopPost(): array
    {
        \Log::info("Sending request at " . now());
        $startTime = Carbon::now('UTC')->subHours(self::TOP_POST_HOURS_BACK)->toIso8601String();
        $response = $this->request(
            'get',
            'tweets/search/recent',
            [
                'query' => 'bitcoin -is:retweet',
                'start_time' => $startTime,
                'tweet.fields' => 'public_metrics',
                'sort_order' => 'relevancy',
                'max_results' => self::TOP_POST_MAX_FETCH,
            ],
            $this->getHeader(),
            true
        );

        dd('TODO: go from here when X API is good enough');

        /**
         * X API is too restricted and buggy: it doesn't seem to fetch more than 10 records at once, doesn't order by relevancy and returns 429 (too many requests) when it's not true
         * {"data":[{"text":"Embrace the inevitable or get ran over. \n\n#Bitcoin https://t.co/TkZBtWJGRQ","id":"1901363756594090009","public_metrics":{"retweet_count":1,"reply_count":5,"like_count":25,"quote_count":0,"bookmark_count":0,"impression_count":539},"edit_history_tweet_ids":["1901363756594090009"]},{"text":"BITCOIN, HBAR, ONDO...DIAMOND HANDS... #Hedera https://t.co/onOQ9uIX35 https://t.co/HsGAWbKfNk","id":"1901405618805805445","public_metrics":{"retweet_count":0,"reply_count":0,"like_count":2,"quote_count":0,"bookmark_count":0,"impression_count":72},"edit_history_tweet_ids":["1901405618805805445"]},{"text":"@bagio_carlos Make sure you join my ongoing mentorship program so you don't miss investment and trade ideas on #Bitcoin, #Altcoin and many more. DM Hello \uD83D\uDC4B let's get you started.\n\uD83D\uDC47\uD83D\uDCE5\uD83D\uDC47\n\nhttps://t.co/cIlQSqGHNF","id":"1901441064101204166","public_metrics":{"retweet_count":0,"reply_count":0,"like_count":0,"quote_count":0,"bookmark_count":0,"impression_count":1},"edit_history_tweet_ids":["1901441064101204166"]},{"text":"@C_Z_Code Make sure you join my ongoing mentorship program so you don't miss investment and trade ideas on #Bitcoin, #Altcoin and many more. DM Hello \uD83D\uDC4B let's get you started.\n\uD83D\uDC47\uD83D\uDCE5\uD83D\uDC47\n\nhttps://t.co/cIlQSqGHNF","id":"1901439973686931707","public_metrics":{"retweet_count":0,"reply_count":0,"like_count":0,"quote_count":0,"bookmark_count":0,"impression_count":4},"edit_history_tweet_ids":["1901439973686931707"]},{"text":"https://t.co/C00hKggbXN's $30k vs Bitget's $444k in $PI giveaways? Easy choice! #PiListBitget #PiPayment","id":"1901326765424820374","public_metrics":{"retweet_count":0,"reply_count":1,"like_count":1,"quote_count":0,"bookmark_count":0,"impression_count":8},"edit_history_tweet_ids":["1901326765424820374"]},{"text":"A Hyperliquid whale shorted $300M of BTC with an $86.6k liquidation price. CBB rallied a group with 8-figures to spike BTC’s price, but it fell short of triggering the liquidation. Story continues. #bitcoin https://t.co/G86ssCfMpU","id":"1901354412360204388","public_metrics":{"retweet_count":0,"reply_count":1,"like_count":3,"quote_count":0,"bookmark_count":0,"impression_count":314},"edit_history_tweet_ids":["1901354412360204388"]},{"text":"@POTUS #bitcoin","id":"1901419541504069697","public_metrics":{"retweet_count":0,"reply_count":0,"like_count":13,"quote_count":0,"bookmark_count":0,"impression_count":391},"edit_history_tweet_ids":["1901419541504069697"]},{"text":"@Bitcoin No #SHATS no vibez","id":"1901431133537185940","public_metrics":{"retweet_count":0,"reply_count":0,"like_count":7,"quote_count":0,"bookmark_count":0,"impression_count":28},"edit_history_tweet_ids":["1901431133537185940"]},{"text":"Ordinals and Runes are the future of Bitcoin. \uD83E\uDDE1  \nThey bring new creativity and utility to the blockchain.  \nReady to join? Start saving Bitcoin now!  \n#BitcoinEcosystem #Crypto #Runes \n$DOG\n$MIM\n$PUPS\n$RSIC\n$BILLY https://t.co/9VBu7b5buz","id":"1901441975641162061","public_metrics":{"retweet_count":0,"reply_count":0,"like_count":0,"quote_count":0,"bookmark_count":0,"impression_count":5},"edit_history_tweet_ids":["1901441975641162061"]},{"text":"@Jinx_Yxurself Make sure you join my ongoing mentorship program so you don't miss investment and trade ideas on #Bitcoin, #Altcoin and many more. DM Hello \uD83D\uDC4B let's get you started.\n\uD83D\uDC47\uD83D\uDCE5\uD83D\uDC47\n\nhttps://t.co/cIlQSqGHNF","id":"1901440235138900037","public_metrics":{"retweet_count":0,"reply_count":0,"like_count":0,"quote_count":0,"bookmark_count":0,"impression_count":3},"edit_history_tweet_ids":["1901440235138900037"]}],"meta":{"newest_id":"1901441975641162061","oldest_id":"1901326765424820374","result_count":10,"next_token":"b26v89c19zqg8o3fsbw6z2792jfle8vg1z94zpxrrszgd"}}
         */

        return [];
    }

    private function getHeader(): array
    {
        return [
            'Authorization' => 'Bearer ' . $this->bearerToken,
        ];
    }
}
