<?php

declare(strict_types=1);

namespace JiordiViera\LaravelLogCleaner\Exceptions;

use InvalidArgumentException;

class InvalidDaysException extends InvalidArgumentException
{
    public static function create(): self
    {
        return new self('Days must be a positive integer');
    }
}
