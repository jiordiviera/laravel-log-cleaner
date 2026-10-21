<?php

declare(strict_types=1);

namespace JiordiViera\LaravelLogCleaner;

use RuntimeException;

/**
 * Checked writes and atomic replacements for log files.
 */
final class LogFileOperations
{
    public function replaceContent(string $filePath, string $content): void
    {
        clearstatcache(true, $filePath);
        $originalStat = stat($filePath);
        if ($originalStat === false) {
            throw new RuntimeException('Unable to stat log file before cleaning');
        }

        $tempFile = tempnam(dirname($filePath), basename($filePath).'.tmp.');
        if ($tempFile === false) {
            throw new RuntimeException('Unable to create temporary log file');
        }

        $handle = null;
        try {
            $handle = @fopen($tempFile, 'wb');
            if ($handle === false) {
                throw new RuntimeException('Unable to open temporary log file');
            }

            $this->writeAll($handle, $content);
            $flushed = fflush($handle);
            $closed = fclose($handle);
            $handle = null;
            if (! $flushed || ! $closed) {
                throw new RuntimeException('Failed to flush temporary log file');
            }

            $this->assertUnchanged($filePath, $originalStat);
            if (! rename($tempFile, $filePath)) {
                throw new RuntimeException('Failed to replace original file with cleaned version');
            }
        } finally {
            if (is_resource($handle)) {
                fclose($handle);
            }
            if (is_file($tempFile)) {
                @unlink($tempFile);
            }
        }
    }


    /** @param resource $handle */
    public function writeAll($handle, string $content): void
    {
        $length = strlen($content);
        $written = 0;

        while ($written < $length) {
            $count = @fwrite($handle, substr($content, $written));
            if ($count === false || $count === 0) {
                throw new RuntimeException('Failed to write complete cleaned log content');
            }
            $written += $count;
        }
    }

    /** @param resource $handle */
    public function writeCompressed($handle, string $content): void
    {
        $length = strlen($content);
        $written = 0;

        while ($written < $length) {
            $count = @gzwrite($handle, substr($content, $written));
            if ($count === false || $count === 0) {
                throw new RuntimeException('Failed to write complete compressed log content');
            }
            $written += $count;
        }
    }


    /**
     * Detect writes or rotations observed during the streaming pass.
     * This is an optimistic guard, not a replacement for writer coordination:
     * an append between the final stat and rename is still possible.
     *
     * @param array<string|int, mixed> $originalStat
     */
    public function assertUnchanged(string $filePath, array $originalStat): void
    {
        clearstatcache(true, $filePath);
        $currentStat = stat($filePath);

        if (
            $currentStat === false
            || $currentStat['dev'] !== $originalStat['dev']
            || $currentStat['ino'] !== $originalStat['ino']
            || $currentStat['size'] !== $originalStat['size']
            || $currentStat['mtime'] !== $originalStat['mtime']
            || $currentStat['ctime'] !== $originalStat['ctime']
        ) {
            throw new RuntimeException('Log file changed during cleanup; original preserved. Retry when log writers are stopped.');
        }
    }


}
