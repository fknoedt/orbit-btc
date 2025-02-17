<?php

namespace App\Clients;

use App\Exceptions\AdapterException;
use App\Exceptions\ExternalApiException;
use App\Models\Request;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Adapters are clients that implement ExternalApiAdapterInterface
 * Clients are simpler and don't have to follow the interface
 * Both should extend this class and use the centralized methods
 */
abstract class BaseClient
{
    /** default request cache time to live in seconds */
    private const int REQUEST_CACHE_TTL = 20;
    protected const string CLIENT_ADAPTER_SUFFIX = 'ApiClientAdapter';
    protected const string CLIENT_SUFFIX = 'Client';

    protected static int $dataSourceId;
    protected static string $url;

    protected static string $currency;
    protected static string $systemDateFormat;

    public function __construct()
    {
        self::$currency = strtoupper(config('btc.currency') ?? 'usd');
        self::$systemDateFormat = config('btc.date_format');
    }


    /**
     * Get the clean Client Name from the class name without the suffixes
     */
    public function getClientName(): string
    {
        $className = explode(DIRECTORY_SEPARATOR, get_class($this));
        $class = str_replace(self::CLIENT_ADAPTER_SUFFIX, '', end($className));

        return str_replace(self::CLIENT_SUFFIX, '', $class);
    }

    public function getDataSourceId(): int
    {
        if (! static::$dataSourceId) {
            throw new AdapterException(
                get_class($this) . '::$dataSourceId not defined'
            );
        }

        return static::$dataSourceId;
    }

    public function getUrl(): string
    {
        if (! static::$url) {
            throw new AdapterException(
                get_class($this) . '::$url not defined'
            );
        }

        return static::$url;
    }

    /**
     * Remove protocol and host from URL
     */
    public function cleanUrl(string $url = null): ?string
    {
        if (! $url) {
            return null;
        }

        $urlParts = parse_url($url);

        return $urlParts['path'] . (! empty($urlParts['query']) ? '?' . $urlParts['query'] : '');
    }

    /**
     * @throws AdapterException
     * @throws ExternalApiException
     * @throws \Illuminate\Http\Client\ConnectionException
     * @throws \Illuminate\Http\Client\RequestException
     */
    public function request(string $method, string $endpoint, array $args = [], array $headers = []): array
    {
        $headers['Accept'] = 'application/json';
        $request = Http::withHeaders($headers);

        if (! method_exists($request, $method)) {
            throw new AdapterException('Invalid request method: ' . $method);
        }

        $url = $this->getUrl() . $endpoint;

        $cacheKey = md5(
            __METHOD__ .
            $url .
            $method .
            implode('', $args) .
            implode('', array_keys($args))
        );

        if ($data = Cache::get($cacheKey)) {
            return $data;
        }

        /** @var Response $response */
        $response = $request->$method(
            $url,
            $args
        );

        $body = $response->getBody()->getContents();

        $this->logRequest(
            __METHOD__,
            $args,
            $response->getStatusCode(),
            $body,
            $method,
            $url,
            $response->transferStats->getTransferTime()
        );

        if ($response->failed()) {
            if ($response->clientError()) {
                throw new AdapterException(
                    sprintf(
                        "Error in %s request to %s: %s",
                        strtoupper($method),
                        $url,
                        $body
                    ),
                    $response->getStatusCode(),
                    $response->toException()
                );
            } else {
                throw new ExternalApiException(
                    $response->getBody()->getContents(),
                    $response->getStatusCode(),
                    $response->toException()
                );
            }
        }

        $data = json_decode($body, true);

        Cache::put($cacheKey, $data, self::REQUEST_CACHE_TTL);

        return $data;
    }

    public function logRequest(
        string $classMethod,
        array $args,
        ?string $statusCode = null,
        ?string $response = null,
        ?string $httpMethod = null,
        ?string $url = null,
        ?float $elapsedTime = null,
    ): void
    {
        // remove namespace
        $classMethod = substr($classMethod, strrpos($classMethod, '\\') + 1);

        $data = [
            'class_method' => $classMethod,
            'data_source_id' => $this->getDataSourceId(),
            'args' => \http_build_query($args),
            'cron' => app()->runningInConsole(),
            'raw_response' => $response,
            'url' => $this->cleanUrl($url),
            'elapsed_time' => $elapsedTime,
        ];

        if ($httpMethod) {
            $data['http_method'] = $httpMethod;
        }
        if ($statusCode) {
            $data['http_status_code'] = $statusCode;
        }

        Request::create($data);
    }
}
