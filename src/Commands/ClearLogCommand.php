<?php

declare(strict_types=1);

namespace JiordiViera\LaravelLogCleaner\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use InvalidArgumentException;

class ClearLogCommand extends Command
{
	private const LOG_PATH = 'logs/laravel.log';
	private const MESSAGE_NO_LOG_FILE = 'No log file found at %s';
	private const MESSAGE_CLEARED_ALL = 'Log file cleared successfully';
	private const MESSAGE_CLEARED_OLD = 'Logs older than %d days have been removed';

	protected $signature = 'log:clear {--days= : Number of days of logs to keep}';
	protected $description = 'Clear the content of the log files';

	public function handle(): int
	{
		try {
			$logPath = $this->getLogPath();
			$days = (int) $this->option('days');

			if (!File::exists($logPath)) {
				$this->warn(sprintf(self::MESSAGE_NO_LOG_FILE, $logPath));
				return self::FAILURE;
			}
			if ($days < 0) {
				$this->error('Days must be a positive integer');
				return self::FAILURE;
			}
			if (!$days) {
				$this->clearAllLogs($logPath);
			} else {
				$this->clearOldLogs($logPath, $days);
			}

			return self::SUCCESS;
		} catch (\Exception $e) {
			$this->error($e->getMessage());
			return self::FAILURE;
		}
	}

	private function getLogPath(): string
	{
		return storage_path(self::LOG_PATH);
	}

	private function clearAllLogs(string $logPath)
	{
		File::put($logPath, '');
		$this->info(self::MESSAGE_CLEARED_ALL);
	}

	private function clearOldLogs(string $logPath, int $days): void
	{
		if ($days < 0) {
			throw new InvalidArgumentException('Days must be a positive integer');
		}

		$cutoffDate = Carbon::now()->subDays($days)->startOfDay();
		$content = File::get($logPath);
		$lines = explode(PHP_EOL, $content);
		$newLines = $this->filterOldLogs($lines, $cutoffDate);

		File::put($logPath, implode(PHP_EOL, $newLines));
		$this->info(sprintf(self::MESSAGE_CLEARED_OLD, $days));
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
