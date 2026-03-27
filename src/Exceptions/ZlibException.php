<?php

declare(strict_types=1);

namespace JiordiViera\LaravelLogCleaner\Exceptions;

class ZlibException extends LogCleanerException
{
    public static function create(): self
    {
        return new self('The zlib extension is required for compression. Please install it to use the --compress option.');
    }
}
