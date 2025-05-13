<?php

namespace App\Exceptions;

/**
 * Should be used for Metrics calculation expected errors,
 * so they won't be reported and will show raw $message to the user
 */
class MetricsFunctionalException extends \Exception implements FunctionalExceptionInterface
{
}
