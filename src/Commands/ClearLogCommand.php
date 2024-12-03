<?php

declare(strict_types=1);

namespace JiordiViera\LaravelLogCleaner\Commands;

use Carbon\Carbon;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use InvalidArgumentException;

class ClearLogCommand extends Command
{
    const LOG_DIRECTORY = 'logs';
    const MESSAGE_NO_LOG_FILE = 'No log files found in %s';
    const MESSAGE_CLEARED_ALL = 'All log files cleared successfully';
    const MESSAGE_CLEARED_OLD = 'Logs older than %d days have been removed from %s';
    const MESSAGE_INVALID_DAYS = 'Days must be a positive integer';

    protected $signature = 'log:clear {--days= : Number of days of logs to keep}';
    protected $description = 'Clear the content of the log files';

    public function handle(): int
    {
        try {
            $logDir = $this->getLogDirectory();
            $days = $this->validateDaysOption();

            $logFiles = $this->getLogFiles($logDir);

            if (empty($logFiles)) {
                $this->warn(sprintf(self::MESSAGE_NO_LOG_FILE, $logDir));
                return self::FAILURE;
            }

            foreach ($logFiles as $logFile) {
                if ($days === 0) {
                    $this->clearAllLogs($logFile);
                } else {
                    $this->clearOldLogs($logFile, $days);
                }
            }
            return self::SUCCESS;
        } catch (InvalidArgumentException $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        } catch (Exception $e) {
            $this->error('An error occurred: ' . $e->getMessage());
            return self::FAILURE;
        }
    }

    private function getLogDirectory(): string
    {
        return storage_path(self::LOG_DIRECTORY);
    }

    private function validateDaysOption(): int
    {
        $days = (int)$this->option('days');
        if ($days < 0) {
            throw new InvalidArgumentException(self::MESSAGE_INVALID_DAYS);
        }
        return $days;
    }

    private function getLogFiles(string $logDir): array
    {
        return array_filter(File::files($logDir), function ($file) {
            return $file->getExtension() === 'log';
        });
    }

    private function clearAllLogs($file)
    {
        File::put($file->getPathname(), '');
        $this->info(self::MESSAGE_CLEARED_ALL);
    }

    private function clearOldLogs($file, int $days)
    {
        $cutoffDate = Carbon::now()->subDays($days)->startOfDay();
        $content = File::get($file->getPathname());

        $lines = explode(PHP_EOL, $content);
        $newLines = $this->filterOldLogs($lines, $cutoffDate);

        File::put($file->getPathname(), implode(PHP_EOL, $newLines));
        $this->info(sprintf(self::MESSAGE_CLEARED_OLD, $days, $file->getFilename()));
    }

    private function filterOldLogs(array $lines, Carbon $cutoffDate): array
    {
        return array_filter($lines, function ($line) use ($cutoffDate) {
            if (preg_match('/^\[(\d{4}-\d{2}-\d{2})/', $line, $matches)) {
                $logDate = Carbon::createFromFormat('Y-m-d', $matches[1])->startOfDay();
                return $logDate->greaterThanOrEqualTo($cutoffDate);
            }
            return true;
        });
    }
}