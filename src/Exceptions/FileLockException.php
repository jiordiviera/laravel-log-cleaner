<?php

declare(strict_types=1);

namespace JiordiViera\LaravelLogCleaner\Exceptions;

class FileLockException extends LogCleanerException
{
    public static function locked(string $file): self
    {
        return new self(sprintf('Log file is currently locked: %s', $file));
    }

    public static function timeout(string $file, int $timeout): self
    {
        return new self(sprintf('Timeout waiting for lock on file: %s (%ds)', $file, $timeout));
    }
}
