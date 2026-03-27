<?php

declare(strict_types=1);

namespace JiordiViera\LaravelLogCleaner\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LogFileCleaned
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public string $file,
        public int $linesRemoved,
        public int $bytesFreed,
        public ?string $backupPath = null,
        public ?string $compressedPath = null
    ) {}
}
