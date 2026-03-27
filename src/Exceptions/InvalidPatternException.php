<?php

declare(strict_types=1);

namespace JiordiViera\LaravelLogCleaner\Exceptions;

class InvalidPatternException extends LogCleanerException
{
    public static function create(string $pattern): self
    {
        return new self(sprintf('Invalid regex pattern provided: %s', $pattern));
    }
}
