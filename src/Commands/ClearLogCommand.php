<?php
declare(strict_types=1);

namespace JiordiViera\LaravelLogCleaner\Commands;

use Illuminate\Support\Facades\File;
use Illuminate\Console\Command;

class ClearLogCommand extends Command
{
    protected $signature = "log:clear";
    protected $description = "Clear the content of the log files";

    public function handle(): void
    {
        $logPath = storage_path('logs/laravel.log');
        if (File::exists($logPath)) {
            File::put($logPath, '');
            $this->info('Log file cleared successfully');
        } else {
            $this->warn('No log file found at ' . $logPath);
        }
    }

}