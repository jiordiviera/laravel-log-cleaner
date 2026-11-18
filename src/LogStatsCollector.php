<?php

declare(strict_types=1);

namespace JiordiViera\LaravelLogCleaner;

use Carbon\Carbon;
use Illuminate\Support\Facades\Config;
use JiordiViera\LaravelLogCleaner\Exceptions\NoLogFilesException;

/**
 * Read-only health report for the log directory: sizes, record counts per level,
 * date range, and the backups/archives left by previous cleanups.
 *
 * Files are streamed line by line, so large logs are never loaded into memory.
 */
final class LogStatsCollector
{
    private LogRecordFilter $recordFilter;

    public function __construct(?string $pattern = null)
    {
        $pattern ??= Config::get('log-cleaner.pattern');

        $this->recordFilter = new LogRecordFilter(is_string($pattern) && $pattern !== '' ? $pattern : null);
    }

    /**
     * @return array{
     *     directory: string,
     *     files: array<int, array{file: string, size_bytes: int, records: int, levels: array<string, int>, oldest: string|null, newest: string|null, modified_at: string}>,
     *     archives: array{count: int, size_bytes: int},
     *     totals: array{files: int, size_bytes: int, records: int, levels: array<string, int>, oldest: string|null, newest: string|null}
     * }
     */
    public function collect(?string $file = null): array
    {
        $directory = storage_path(LogCleaner::LOG_DIRECTORY);
        $paths = $this->logFiles($directory, $file);

        if ($paths === []) {
            throw NoLogFilesException::create($directory);
        }

        $files = array_map(fn (string $path) => $this->analyse($path), $paths);

        $levels = array_fill_keys(LogCleaner::LOG_LEVELS, 0);
        foreach ($files as $stats) {
            foreach ($stats['levels'] as $level => $count) {
                $levels[$level] += $count;
            }
        }

        $oldest = array_filter(array_column($files, 'oldest'));
        $newest = array_filter(array_column($files, 'newest'));

        return [
            'directory' => $directory,
            'files' => $files,
            'archives' => $this->archives($paths),
            'totals' => [
                'files' => count($files),
                'size_bytes' => array_sum(array_column($files, 'size_bytes')),
                'records' => array_sum(array_column($files, 'records')),
                'levels' => $levels,
                'oldest' => $oldest === [] ? null : min($oldest),
                'newest' => $newest === [] ? null : max($newest),
            ],
        ];
    }

    /**
     * @return array<int, string>
     */
    private function logFiles(string $directory, ?string $file): array
    {
        if (! is_dir($directory)) {
            return [];
        }

        $paths = $file !== null
            ? array_filter([$directory.DIRECTORY_SEPARATOR.$file], 'is_file')
            : (glob($directory.DIRECTORY_SEPARATOR.'*.log') ?: []);

        sort($paths);

        return array_values($paths);
    }

    /**
     * @return array{file: string, size_bytes: int, records: int, levels: array<string, int>, oldest: string|null, newest: string|null, modified_at: string}
     */
    private function analyse(string $path): array
    {
        $levels = array_fill_keys(LogCleaner::LOG_LEVELS, 0);
        $records = 0;
        $oldest = null;
        $newest = null;

        $handle = @fopen($path, 'rb');
        if ($handle !== false) {
            try {
                while (($line = fgets($handle)) !== false) {
                    if (! $this->recordFilter->isRecordStart($line)) {
                        continue;
                    }

                    $records++;

                    $level = $this->recordFilter->detectLevel($line);
                    if ($level !== null) {
                        $levels[$level]++;
                    }

                    $date = $this->recordFilter->extractDate($line);
                    if ($date instanceof Carbon) {
                        $day = $date->toDateString();
                        $oldest = $oldest === null || $day < $oldest ? $day : $oldest;
                        $newest = $newest === null || $day > $newest ? $day : $newest;
                    }
                }
            } finally {
                fclose($handle);
            }
        }

        clearstatcache(true, $path);

        return [
            'file' => basename($path),
            'size_bytes' => (int) filesize($path),
            'records' => $records,
            'levels' => $levels,
            'oldest' => $oldest,
            'newest' => $newest,
            'modified_at' => Carbon::createFromTimestamp((int) filemtime($path))->toDateTimeString(),
        ];
    }

    /**
     * Backups (`*.backup.*`) and compressed archives (`*.old.*.gz`) next to the analysed logs.
     *
     * @param array<int, string> $paths
     *
     * @return array{count: int, size_bytes: int}
     */
    private function archives(array $paths): array
    {
        $found = [];

        foreach ($paths as $path) {
            foreach (array_merge(glob($path.'.backup.*') ?: [], glob($path.'.old.*.gz') ?: []) as $archive) {
                $found[$archive] = (int) filesize($archive);
            }
        }

        return ['count' => count($found), 'size_bytes' => array_sum($found)];
    }
}
