<?php

declare(strict_types=1);

namespace JiordiViera\LaravelLogCleaner\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LogCleaned
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public int $days,
        public bool $backup,
        public bool $compress,
        public ?string $level,
        public ?string $pattern,
        public bool $memoryEfficient,
        public ?string $file,
        public bool $dryRun = false,
        /** @var array<int, array{file: string, lines_removed: int, bytes_freed: int, backup_path: string|null, compressed_path: string|null}> */
        public array $results = []
    ) {}
}
