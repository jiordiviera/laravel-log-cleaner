<?php

declare(strict_types=1);

namespace JiordiViera\LaravelLogCleaner\Exceptions;

class PermissionException extends LogCleanerException
{
    public static function create(string $file): self
    {
        return new self(sprintf('Permission denied for file: %s', $file));
    }
}
