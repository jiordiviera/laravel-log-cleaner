<?php

declare(strict_types=1);

namespace JiordiViera\LaravelLogCleaner\Commands;

use Carbon\Carbon;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use InvalidArgumentException;
use SplFileObject;
use RuntimeException;

class ClearLogCommand extends Command
{
    const MESSAGE_CLEARED_ALL = 'All log files cleared successfully';
    const MESSAGE_CLEARED_OLD = 'Logs older than %d days have been removed from %s';
    const MESSAGE_INVALID_DAYS = 'Days must be a positive integer';
    const MESSAGE_DRY_RUN = '[DRY RUN] Would remove %d lines from %s';
    const MESSAGE_DRY_RUN_SPACE = '[DRY RUN] Estimated space to free: %s';

    protected $signature = 'log:clear {--days= : Number of days of logs to keep} {--backup : Create backup before cleaning} {--pattern= : Custom date pattern for logs} {--memory-efficient : Use memory-efficient processing for large files} {--compress : Compress old logs instead of deleting them} {--level= : Filter by log level (ERROR, WARNING, INFO, DEBUG)} {--dry-run : Show what would be deleted without actually deleting} {--file= : Specific log file to clean (e.g., laravel.log)}';
    protected $description = 'Clear the content of the log files';

    public function handle(): int
    {
        try {
            $logCleaner = app('log-cleaner');
            $days = $this->validateDaysOption();
            $file = $this->option('file');

            if ($this->option('dry-run')) {
                $results = $logCleaner->dryRun(
                    days: $days,
                    file: $file,
                    level: $this->option('level'),
                    pattern: $this->option('pattern')
                );
                
                foreach ($results as $result) {
                    $this->info(sprintf(self::MESSAGE_DRY_RUN, $result['removed_lines'], $result['file']));
                    $this->info(sprintf(self::MESSAGE_DRY_RUN_SPACE, $result['estimated_space']));
                }
                
                return self::SUCCESS;
            }

            $logCleaner->clear(
                days: $days,
                backup: $this->option('backup'),
                compress: $this->option('compress'),
                level: $this->option('level'),
                pattern: $this->option('pattern'),
                memoryEfficient: $this->option('memory-efficient'),
                file: $file
            );

            $this->info($days === 0 ? self::MESSAGE_CLEARED_ALL : sprintf(self::MESSAGE_CLEARED_OLD, $days, $file ?: 'all log files'));
            return self::SUCCESS;
        } catch (InvalidArgumentException $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        } catch (RuntimeException $e) {
            if (str_contains($e->getMessage(), 'No log files found')) {
                $this->warn($e->getMessage());
                return self::FAILURE;
            }
            $this->error('An error occurred: ' . $e->getMessage());
            return self::FAILURE;
        } catch (Exception $e) {
            $this->error('An error occurred: ' . $e->getMessage());
            return self::FAILURE;
        }
    }

    private function validateDaysOption(): int
    {
        $days = (int)$this->option('days');
        if ($days < 0) {
            throw new InvalidArgumentException(self::MESSAGE_INVALID_DAYS);
        }
        return $days;
    }
}