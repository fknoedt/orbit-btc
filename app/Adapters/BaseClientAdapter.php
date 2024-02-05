<?php

namespace App\Adapters;

use App\Exceptions\AdapterException;

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
}