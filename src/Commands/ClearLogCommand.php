<?php

declare(strict_types=1);

namespace JiordiViera\LaravelLogCleaner\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ClearLogCommand extends Command
{
    protected $signature = "log:clear {--days= : Number of days of logs to keep} ";
    protected $description = "Clear the content of the log files";

    public function handle(): void
    {
        $logPath = storage_path('logs/laravel.log');
        $days = $this->option('days');

        if (!File::exists($logPath)) {
            $this->warn('No log file found at ' . $logPath);
            return;
        }

        if ($days === null) {
            $this->clearAllLogs($logPath);
        } else {
            $this->clearOldLogs($logPath, (int)$days);
        }
    }

    private function clearAllLogs(string $logPath): void 
    {
        File::put($logPath, '');
        $this->info('Log file cleared successfully');
    }

    private function clearOldLogs(string $logPath, int $days)
    {
        $cutoffDate = Carbon::now()->subDays($days)->startOfDay();
        $content = file_get_contents($logPath);
        $lines = explode(PHP_EOL, $content);
        $newLines = [];

        foreach ($lines as $line) {
            if (preg_match('/^\[(\d{4}-\d{2}-\d{2})/', $line, $matches)) {
                $logDate = Carbon::createFromFormat('Y-m-d', $matches[1])->startOfDay();
                if ($logDate->greaterThanOrEqualTo($cutoffDate)) {
                    $newLines[] = $line;
                }
            } else {
                $newLines[] = $line;
            }
        }

        file_put_contents($logPath, implode(PHP_EOL, $newLines));
        $this->info("Logs older than {$days} days have been removed");
    }
}
