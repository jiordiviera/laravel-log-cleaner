<?php

declare(strict_types=1);

namespace JiordiViera\LaravelLogCleaner\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use JiordiViera\LaravelLogCleaner\Exceptions\LogCleanerException;
use JiordiViera\LaravelLogCleaner\Exceptions\NoLogFilesException;
use JiordiViera\LaravelLogCleaner\LogStatsCollector;

class LogStatsCommand extends Command
{
    protected $signature = 'log:stats
                            {--file= : Analyse a single log file (e.g., laravel.log)}
                            {--pattern= : Custom date pattern for log records}
                            {--max-size= : Fail (exit code 1) when the analysed logs exceed this size in MB}
                            {--json : Output the report as JSON}';

    protected $description = 'Show size, records per level and date range of the log files (read-only)';

    public function handle(): int
    {
        $maxSize = $this->option('max-size') ?? Config::get('log-cleaner.health.max_size_mb');

        if ($maxSize !== null && (! is_numeric($maxSize) || (float) $maxSize <= 0)) {
            $this->error('The --max-size option must be a positive number of megabytes.');

            return self::FAILURE;
        }

        try {
            $file = $this->option('file');
            $pattern = $this->option('pattern');
            $report = (new LogStatsCollector(is_string($pattern) ? $pattern : null))
                ->collect(is_string($file) ? $file : null);
        } catch (NoLogFilesException $e) {
            $this->warn($e->getMessage());

            return self::FAILURE;
        } catch (LogCleanerException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $limitBytes = $maxSize === null ? null : (int) round((float) $maxSize * 1024 * 1024);
        $exceeded = $limitBytes !== null && $report['totals']['size_bytes'] > $limitBytes;

        if ($this->option('json')) {
            $this->line((string) json_encode($report + [
                'health' => ['max_size_bytes' => $limitBytes, 'exceeded' => $exceeded],
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return $exceeded ? self::FAILURE : self::SUCCESS;
        }

        $this->renderTable($report);

        if ($exceeded) {
            $this->newLine();
            $this->error(sprintf(
                'Log size %s exceeds the %s limit. Run "php artisan log:clear --days=N" to free space.',
                $this->formatBytes($report['totals']['size_bytes']),
                $this->formatBytes((int) $limitBytes)
            ));

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /**
     * @param array<string, mixed> $report
     */
    private function renderTable(array $report): void
    {
        $this->table(
            ['File', 'Size', 'Records', 'Errors+', 'Warnings', 'Oldest', 'Newest'],
            array_map(fn (array $f) => [
                $f['file'],
                $this->formatBytes($f['size_bytes']),
                number_format($f['records']),
                number_format($this->severeCount($f['levels'])),
                number_format($f['levels']['WARNING']),
                $f['oldest'] ?? '-',
                $f['newest'] ?? '-',
            ], $report['files'])
        );

        $totals = $report['totals'];
        $this->newLine();
        $this->info(sprintf(
            'Total: %d file(s), %s, %s record(s), from %s to %s',
            $totals['files'],
            $this->formatBytes($totals['size_bytes']),
            number_format($totals['records']),
            $totals['oldest'] ?? '-',
            $totals['newest'] ?? '-'
        ));

        $levels = array_filter($totals['levels']);
        if ($levels !== []) {
            $this->line('By level: '.implode(', ', array_map(
                fn (string $level, int $count) => $level.' '.number_format($count),
                array_keys($levels),
                $levels
            )));
        }

        if ($report['archives']['count'] > 0) {
            $this->line(sprintf(
                'Backups and archives: %d file(s), %s',
                $report['archives']['count'],
                $this->formatBytes($report['archives']['size_bytes'])
            ));
        }
    }

    /**
     * @param array<string, int> $levels
     */
    private function severeCount(array $levels): int
    {
        return $levels['EMERGENCY'] + $levels['ALERT'] + $levels['CRITICAL'] + $levels['ERROR'];
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = (int) floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        return round($bytes / (1 << (10 * $pow)), 2).' '.$units[$pow];
    }
}
