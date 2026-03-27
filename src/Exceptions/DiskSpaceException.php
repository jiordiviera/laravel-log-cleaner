<?php

declare(strict_types=1);

namespace JiordiViera\LaravelLogCleaner\Exceptions;

class DiskSpaceException extends LogCleanerException
{
    public static function insufficient(float $required, float $available): self
    {
        return new self(sprintf(
            'Insufficient disk space. Required: %.2f MB, Available: %.2f MB',
            $required,
            $available
        ));
    }
}
