<?php

declare(strict_types=1);

namespace JiordiViera\LaravelLogCleaner;

use Illuminate\Support\Facades\Config;
use RuntimeException;

/**
 * Creates checked gzip archives for removed log entries.
 */
final class LogCompressionManager
{
    public function __construct(private LogFileOperations $fileOperations = new LogFileOperations)
    {
    }

    public function compressOldLogs(string $filePath, array $linesToCompress): ?string
    {
        if (empty($linesToCompress)) {
            return null;
        }

        $compressedPath = $filePath.'.old.'.date('Y-m-d-H-i-s').'.gz';
        $compressionLevel = Config::get('log-cleaner.compression.level', 9);
        $handle = gzopen($compressedPath, 'w'.$compressionLevel);

        if (! $handle) {
            throw new RuntimeException('Failed to create compressed file: '.$compressedPath);
        }

        try {
            foreach ($linesToCompress as $line) {
                $this->fileOperations->writeCompressed($handle, $line);
            }

            $closed = gzclose($handle);
            $handle = null;
            if (! $closed) {
                throw new RuntimeException('Failed to close compressed log archive');
            }
        } catch (\Throwable $e) {
            if ($handle !== null) {
                gzclose($handle);
            }
            @unlink($compressedPath);
            throw $e;
        }

        return $compressedPath;
    }
}
