<?php

declare(strict_types=1);

namespace JiordiViera\LaravelLogCleaner\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use JiordiViera\LaravelLogCleaner\Exceptions\BackupException;
use JiordiViera\LaravelLogCleaner\Exceptions\DiskSpaceException;
use JiordiViera\LaravelLogCleaner\Exceptions\FileLockException;
use JiordiViera\LaravelLogCleaner\Exceptions\InvalidDaysException;
use JiordiViera\LaravelLogCleaner\Exceptions\InvalidLogLevelException;
use JiordiViera\LaravelLogCleaner\Exceptions\InvalidPatternException;
use JiordiViera\LaravelLogCleaner\Exceptions\NoLogFilesException;
use JiordiViera\LaravelLogCleaner\Exceptions\PermissionException;
use JiordiViera\LaravelLogCleaner\Exceptions\ZlibException;
use JiordiViera\LaravelLogCleaner\LogCleaner;
use Throwable;

class ClearLogCommand extends Command
{
    protected $signature = 'log:clear 
                            {--days= : Number of days of logs to keep} 
                            {--backup : Create backup before cleaning} 
                            {--pattern= : Custom date pattern for logs} 
                            {--memory-efficient : Use memory-efficient processing for large files} 
                            {--compress : Compress old logs instead of deleting them} 
                            {--level= : Filter by log level (ERROR, WARNING, INFO, DEBUG)} 
                            {--dry-run : Show what would be deleted without actually deleting} 
                            {--file= : Specific log file to clean (e.g., laravel.log)}
                            {--no-lock : Disable file locking}
                            {--no-events : Disable event dispatching}';

    protected $description = 'Clear the content of the log files';

    public function handle(): int
    {
        try {
            $logCleaner = app('log-cleaner');
            $logCleaner->setOutput($this->output);

            $days = $this->validateDaysOption();
            $file = $this->option('file');
            $level = $this->option('level');
            $pattern = $this->option('pattern');

            if ($this->option('dry-run')) {
                return $this->handleDryRun(
                    $logCleaner,
                    $days,
                    is_string($file) ? $file : null,
                    is_string($level) ? $level : null,
                    is_string($pattern) ? $pattern : null
                );
            }

            return $this->handleClear(
                $logCleaner,
                $days,
                is_string($file) ? $file : null,
                is_string($level) ? $level : null,
                is_string($pattern) ? $pattern : null
            );
        } catch (InvalidDaysException|InvalidLogLevelException|InvalidPatternException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        } catch (NoLogFilesException $e) {
            $this->warn($e->getMessage());

            return self::FAILURE;
        } catch (FileLockException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        } catch (DiskSpaceException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        } catch (BackupException|PermissionException|ZlibException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        } catch (Throwable $e) {
            $this->error('An error occurred: '.$e->getMessage());

            return self::FAILURE;
        }
    }

    private function handleDryRun(LogCleaner $logCleaner, int $days, ?string $file, ?string $level, ?string $pattern): int
    {
        $this->info('🔍 Dry Run Mode - No changes will be made');
        $this->newLine();

        $results = $logCleaner->dryRun(
            days: $days,
            file: $file,
            level: $level,
            pattern: $pattern
        );

        $this->table(
            ['File', 'Lines to Remove', 'Space to Free'],
            array_map(fn ($r) => [$r['file'], number_format($r['removed_lines']), $r['estimated_space']], $results)
        );

        $totalLines = array_sum(array_column($results, 'removed_lines'));
        $this->newLine();
        $this->info(sprintf('Total: <fg=cyan>%s</> lines to be removed', number_format($totalLines)));

        return self::SUCCESS;
    }

    private function handleClear(LogCleaner $logCleaner, int $days, ?string $file, ?string $level, ?string $pattern): int
    {
        $this->info('🧹 Starting log cleanup...');
        $this->newLine();

        $logCleaner->clear(
            days: $days,
            backup: (bool) $this->option('backup'),
            compress: (bool) $this->option('compress'),
            level: $level,
            pattern: $pattern,
            memoryEfficient: (bool) $this->option('memory-efficient'),
            file: $file
        );

        $results = $logCleaner->getCleaningResults();

        $this->displayResults($results, $days, $file);

        return self::SUCCESS;
    }

    /**
     * @param array<int, array{file: string, lines_removed: int, bytes_freed: int, backup_path: string|null, compressed_path: string|null}> $results
     */
    private function displayResults(array $results, int $days, ?string $file): void
    {
        $tableRows = [];
        $totalLines = 0;
        $totalBytes = 0;

        foreach ($results as $result) {
            $tableRows[] = [
                basename($result['file']),
                number_format($result['lines_removed']),
                $this->formatBytes($result['bytes_freed']),
                $result['backup_path'] ? '✓' : '-',
                $result['compressed_path'] ? '✓' : '-',
            ];
            $totalLines += $result['lines_removed'];
            $totalBytes += $result['bytes_freed'];
        }

        $this->table(
            ['File', 'Lines Removed', 'Space Freed', 'Backup', 'Compressed'],
            $tableRows
        );

        $this->newLine();
        $this->info(sprintf(
            '✅ <fg=green>%s</> - <fg=cyan>%s</> lines removed, <fg=cyan>%s</> freed',
            $days === 0 ? 'All logs cleared' : sprintf('Logs older than %d days cleared', $days),
            number_format($totalLines),
            $this->formatBytes($totalBytes)
        ));

        $backupsCreated = count(array_filter($results, fn ($r) => $r['backup_path'] !== null));
        $compressedCreated = count(array_filter($results, fn ($r) => $r['compressed_path'] !== null));

        if ($backupsCreated > 0) {
            $this->info(sprintf('📦 %d backup(s) created', $backupsCreated));
        }

        if ($compressedCreated > 0) {
            $this->info(sprintf('🗜️ %d file(s) compressed', $compressedCreated));
        }
    }

    /**
     * @param int<0, max> $bytes
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));

        return round($bytes, 2).' '.$units[$pow];
    }

    private function validateDaysOption(): int
    {
        $daysOption = $this->option('days');

        if ($daysOption === null) {
            return Config::get('log-cleaner.days', 0);
        }

        $days = (int) $daysOption;

        if ($days < 0) {
            throw InvalidDaysException::create();
        }

        return $days;
    }
}
