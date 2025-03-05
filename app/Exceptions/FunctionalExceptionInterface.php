<?php

namespace App\Exceptions;

/**
 * Exceptions which implement this interface are considered expected, meaning they won't be reported
 * and $message is considered safe to the end user
 */
interface FunctionalExceptionInterface
{
}
