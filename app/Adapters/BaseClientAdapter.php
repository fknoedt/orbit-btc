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

    public function logRequest(
        string $classMethod,
        array $args,
        ?string $response = null,
        ?string $httpMethod = null
    ): void
    {
        $data = [
            'class_method' => $classMethod,
            'data_source_id' => $this->getDataSourceId(),
            'args' => \http_build_query($args),
            'cron' => app()->runningInConsole(),
            'raw_response' => $response,
        ];

        if ($httpMethod) {
            $data['http_method'] = $httpMethod;
        }

        Request::create($data);
    }
}
