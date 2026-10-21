<?php

declare(strict_types=1);

namespace JiordiViera\LaravelLogCleaner;

use Illuminate\Support\Facades\Config;
use JiordiViera\LaravelLogCleaner\Exceptions\FileLockException;
use RuntimeException;

/**
 * Coordinates cooperative per-file flock locks for one cleaner instance.
 */
final class FileLockManager
{
    /** @var array<string, resource> */
    private array $lockHandles = [];

    public function acquire(string $filePath): void
    {
        $lockFile = $filePath.'.lock';
        $timeout = Config::get('log-cleaner.locking.timeout', 30);
        $handle = fopen($lockFile, 'c');
        if ($handle === false) {
            throw new RuntimeException('Unable to open log lock file');
        }

        $startTime = microtime(true);
        do {
            if (flock($handle, LOCK_EX | LOCK_NB)) {
                $this->lockHandles[$filePath] = $handle;

                return;
            }

            if (microtime(true) - $startTime >= $timeout) {
                fclose($handle);
                throw FileLockException::timeout($filePath, $timeout);
            }

            usleep(100000);
        } while (true);
    }

    public function release(string $filePath): void
    {
        if (! isset($this->lockHandles[$filePath])) {
            return;
        }

        $handle = $this->lockHandles[$filePath];
        unset($this->lockHandles[$filePath]);
        flock($handle, LOCK_UN);
        fclose($handle);
    }
}
