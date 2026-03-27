<?php

declare(strict_types=1);

namespace JiordiViera\LaravelLogCleaner\Exceptions;

class InvalidLogLevelException extends LogCleanerException
{
    /**
     * @param array<int, string> $validLevels
     */
    public static function create(string $level, array $validLevels): self
    {
        return new self(sprintf(
            'Invalid log level "%s". Must be one of: %s',
            $level,
            implode(', ', $validLevels)
        ));
    }
}
