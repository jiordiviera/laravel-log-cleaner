<?php

declare(strict_types=1);

namespace JiordiViera\LaravelLogCleaner\Exceptions;

class NoLogFilesException extends LogCleanerException
{
    public static function create(string $directory): self
    {
        return new self(sprintf('No log files found in %s', $directory));
    }
}
