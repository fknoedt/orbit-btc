<?php

namespace App\Adapters;

use App\Exceptions\AdapterException;
use App\Models\Request;

class BaseClientAdapter
{
    protected const CLIENT_NAME_SUFIX = 'ApiClientAdapter';

    public function getClientName(): string
    {
        $className = explode(DIRECTORY_SEPARATOR, get_class($this));
        $class = str_replace(self::CLIENT_NAME_SUFIX, '', end($className));

        return $class;
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
