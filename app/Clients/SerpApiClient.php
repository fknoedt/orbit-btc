<?php

namespace App\Clients;

class SerpApiClient extends BaseClient
{
    private string $key;

    public function __construct()
    {
        parent::__construct();
        self::$dataSourceId = config('data.data_source.serpapi_id');
        if (! self::$url = config('btc.apis.serpapi.url')) {
            throw new \RuntimeException('could not load config: btc.apis.serpapi.url');
        }
        if (! $this->key = config('btc.apis.serpapi.key')) {
            throw new \RuntimeException('could not load config: btc.apis.serpapi.key');
        }
    }

    /**
     * @see https://serpapi.com/google-trends-api
     * @throws \App\Exceptions\AdapterException
     * @throws \App\Exceptions\ExternalApiException
     * @throws \Illuminate\Http\Client\ConnectionException
     * @throws \Illuminate\Http\Client\RequestException
     */
    public function getGoogleTrendStats()
    {

        $args = [
            'engine' => 'google_trends',
            'q' => 'bitcoin,btc,$btc',
            'api_key' => $this->key,
            'date' => 'all'
        ];
        dd($this->request('get', '', $args));
    }
}
