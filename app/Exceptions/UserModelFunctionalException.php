<?php

namespace App\Exceptions;

/**
 * Should be used for UserModel Metrics calculation expected errors,
 * so they won't be reported and will show raw $message to the user
 */
class UserModelFunctionalException extends \Exception implements FunctionalExceptionInterface
{
}
