<?php

namespace App\Exceptions;

/**
 * Should be used for UserSignal Metrics calculation expected errors,
 * so they won't be reported and will show raw $message to the user
 */
class UserSignalFunctionalException extends \Exception implements FunctionalExceptionInterface
{
}
