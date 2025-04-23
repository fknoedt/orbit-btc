<?php

namespace App\Clients;

use App\Exceptions\AdapterException;
use Symfony\Component\Mime\Exception\RuntimeException;

/**
 * This class is using the web auth token and charts "live-api" 🤷‍♂️
 * To authenticate, you need to login to the web interface and copy the Authorization: Bearer token to your .env
 * @todo test out and evaluate paying for the plan
 * @see https://cryptoquant.com/pricing
 */
class CurlCryptoQuantClient extends BaseClient
{
    private string $version = 'v4';

    private ?string $authToken;

    /** 7/14/10 (initial CMC data) but CQ still doesn't go before 2022 */
    private ?string $initialTimestamp = '1279065600000';
    private ?string $finalTimestamp;

    /**
     * daily_prices column name => CQ chart endpoint
     */
    public const array METRICS_TO_ENDPOINT = [
        'average_fee' => '61adc7976bc0e955292d7316', // 61adc7ef6bc0e955292d7318
        'exchanges_reserve' => '6262224b8e0f29233db45e73',
    ];

    public function __construct()
    {
        parent::__construct();
        self::$dataSourceId = config('data.data_source.cryptoquant_id');
        if (! $url = config('btc.apis.cryptoquant.url')) {
            throw new \RuntimeException('could not load config: data.data_source.cryptoquant_id');
        }
        self::$url = $url . '/live/' . $this->version . '/charts/';
        $this->authToken = config('btc.apis.cryptoquant.auth_token');
        if (! $this->authToken) {
            throw new \RuntimeException('could not load config: btc.apis.cryptoquant.auth_token');
        }

        $this->finalTimestamp = (string) floor(microtime(true) * 1000);
    }

    /**
     * This method was necessary as when using Laravel's HTTP Request, CQ would detect a difference and show HTML
     * @throws AdapterException
     */
    public function curlRequest(string $endpoint, string $method = 'GET'): array
    {
        $args = [
            'window' => 'DAY',
            'from' => $this->initialTimestamp,
            'to' => $this->finalTimestamp,
            'limit' => 70000
        ];
        $url = $this->getUrl() . $endpoint . '?' . http_build_query($args);

        $headers = [
            'Authorization: Bearer ' . $this->authToken,
            'accept: application/json, text/plain, */*',
            'accept-language: en-US,en;q=0.9',
            'origin: https://cryptoquant.com',
            'referer: https://cryptoquant.com',
            'user-agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36',
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        // curl_setopt($ch, CURLOPT_VERBOSE, true);

        $output = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($httpCode >= 300) {
            throw new RuntimeException("Invalid ({$httpCode}) response from " . $this->getUrl());
        }

        $caller = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS,2);
        if (! is_array($caller) || empty($caller[1])) {
            report(new RuntimeException('Could not detect caller through backtrace in ' . __METHOD__));
            $callerMethod = __METHOD__;
        } else {
            $callerClass = $caller[1]['class'];
            $callerMethod = substr($callerClass, strrpos("\\{$callerClass}", '\\') - 1) . ':' .
                $caller[1]['function'];
        }

        $this->logRequest(
            $callerMethod,
            $args,
            $httpCode,
            substr($output, 0, 100),
            $method,
            $this->getUrl() . $endpoint,
            curl_getinfo($ch, CURLINFO_TOTAL_TIME)
        );

        curl_close($ch);

        return json_decode($output, true);
    }

    /**
     * Override parent's method to inject authToken
     * @throws \App\Exceptions\AdapterException
     * @throws \App\Exceptions\ExternalApiException
     * @throws \Illuminate\Http\Client\ConnectionException
     * @throws \Illuminate\Http\Client\RequestException
     */
    public function request(string $method, string $endpoint, array $args = [], array $headers = []): array
    {
        throw new \BadMethodCallException(__METHOD__ . ' not working. Use curlRequest()');
        $headers['Authorization'] = 'Bearer ' . $this->authToken;
        $headers['accept-encoding'] = 'gzip, deflate, zstd';
        $headers['accept-language'] = 'en-US,en;q=0.9';
        $headers['origin'] = 'https://cryptoquant.com';
        $headers['referer'] = 'https://cryptoquant.com';
        $headers['user-agent'] = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36';

        $args = [
            'window' => 'DAY',
            'from' => 1279065600000, // initial BTC consistent data: 7/14/10
            'to' => floor(microtime(true) * 1000),
            'limit' => 70000,
        ];
        return parent::request($method, $endpoint, $args, $headers);
    }
}
