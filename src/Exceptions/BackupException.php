<?php

declare(strict_types=1);

namespace JiordiViera\LaravelLogCleaner\Exceptions;

class BackupException extends LogCleanerException
{
    public static function create(string $file): self
    {
        return new self(sprintf('Failed to create backup for: %s', $file));
    }
}
