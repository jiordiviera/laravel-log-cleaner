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
    const LOG_DIRECTORY = 'logs';
    const MESSAGE_NO_LOG_FILE = 'No log files found in %s';
    const MESSAGE_CLEARED_ALL = 'All log files cleared successfully';
    const MESSAGE_CLEARED_OLD = 'Logs older than %d days have been removed from %s';
    const MESSAGE_INVALID_DAYS = 'Days must be a positive integer';
    const MESSAGE_PERMISSION_ERROR = 'Permission denied for file: %s';
    const MESSAGE_BACKUP_CREATED = 'Backup created: %s';
    const MESSAGE_COMPRESSED = 'Logs compressed to: %s';
    const MESSAGE_DRY_RUN = '[DRY RUN] Would remove %d lines from %s';
    const MEMORY_THRESHOLD = 50 * 1024 * 1024; // 50MB
    const LOG_LEVELS = ['EMERGENCY', 'ALERT', 'CRITICAL', 'ERROR', 'WARNING', 'NOTICE', 'INFO', 'DEBUG'];
    const DEFAULT_LOG_PATTERNS = [
        '/^\[(\d{4}-\d{2}-\d{2})/',
        '/^\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})/',
        '/^(\d{4}-\d{2}-\d{2})/',
    ];

    protected $signature = 'log:clear {--days= : Number of days of logs to keep} {--backup : Create backup before cleaning} {--pattern= : Custom date pattern for logs} {--memory-efficient : Use memory-efficient processing for large files} {--compress : Compress old logs instead of deleting them} {--level= : Filter by log level (ERROR, WARNING, INFO, DEBUG)} {--dry-run : Show what would be deleted without actually deleting}';
    protected $description = 'Clear the content of the log files';

    private array $compiledPatterns = [];

    public function handle(): int
    {
        try {
            $logDir = $this->getLogDirectory();
            $days = $this->validateDaysOption();
            $this->compilePatterns();
            $this->validateLogLevel();

            $logFiles = $this->getLogFiles($logDir);

            if (empty($logFiles)) {
                $this->warn(sprintf(self::MESSAGE_NO_LOG_FILE, $logDir));
                return self::FAILURE;
            }

            if (!$this->option('dry-run')) {
                $this->validatePermissions($logFiles);
            }

            foreach ($logFiles as $logFile) {
                if ($this->option('backup') && !$this->option('dry-run')) {
                    $this->createBackup($logFile);
                }

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
        $filePath = $file->getPathname();
        
        if ($this->shouldUseMemoryEfficientProcessing($filePath)) {
            $this->clearOldLogsMemoryEfficient($filePath, $cutoffDate, $days);
        } else {
            $this->clearOldLogsStandard($filePath, $cutoffDate, $days);
        }
        
        $this->info(sprintf(self::MESSAGE_CLEARED_OLD, $days, $file->getFilename()));
    }

    private function filterOldLogs(array $lines, Carbon $cutoffDate): array
    {
        return array_filter($lines, function ($line) use ($cutoffDate) {
            return $this->shouldKeepLine($line, $cutoffDate);
        });
    }

    private function shouldKeepLine(string $line, Carbon $cutoffDate): bool
    {
        // First check log level filter
        if (!$this->shouldKeepLineByLevel($line)) {
            return false;
        }

        // Then check date filter
        foreach ($this->compiledPatterns as $pattern => $format) {
            if (preg_match($pattern, $line, $matches)) {
                try {
                    $logDate = Carbon::createFromFormat($format, $matches[1])->startOfDay();
                    return $logDate->greaterThanOrEqualTo($cutoffDate);
                } catch (Exception $e) {
                    continue;
                }
            }
        }
        return true;
    }

    private function compilePatterns(): void
    {
        $customPattern = $this->option('pattern');
        
        if ($customPattern) {
            $this->compiledPatterns = [$customPattern => 'Y-m-d'];
        } else {
            $this->compiledPatterns = [
                '/^\[(\d{4}-\d{2}-\d{2})/' => 'Y-m-d',
                '/^\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})/' => 'Y-m-d H:i:s',
                '/^(\d{4}-\d{2}-\d{2})/' => 'Y-m-d',
            ];
        }
    }

    private function shouldUseMemoryEfficientProcessing(string $filePath): bool
    {
        return $this->option('memory-efficient') || filesize($filePath) > self::MEMORY_THRESHOLD;
    }

    private function clearOldLogsStandard(string $filePath, Carbon $cutoffDate, int $days): void
    {
        $content = File::get($filePath);
        $lines = explode(PHP_EOL, $content);
        $newLines = $this->filterOldLogs($lines, $cutoffDate);
        
        if ($this->option('dry-run')) {
            $removedCount = count($lines) - count($newLines);
            $this->info(sprintf(self::MESSAGE_DRY_RUN, $removedCount, basename($filePath)));
            return;
        }

        if ($this->option('compress')) {
            $linesToCompress = array_diff($lines, $newLines);
            $this->compressOldLogs($filePath, $linesToCompress);
        }

        File::put($filePath, implode(PHP_EOL, $newLines));
    }

    private function clearOldLogsMemoryEfficient(string $filePath, Carbon $cutoffDate, int $days): void
    {
        if ($this->option('dry-run')) {
            $this->dryRunMemoryEfficient($filePath, $cutoffDate);
            return;
        }

        $tempFile = $filePath . '.tmp';
        $compressHandle = null;
        
        if ($this->option('compress')) {
            $compressedPath = $filePath . '.old.' . date('Y-m-d-H-i-s') . '.gz';
            $compressHandle = gzopen($compressedPath, 'w9');
        }

        $inputHandle = fopen($filePath, 'r');
        $outputHandle = fopen($tempFile, 'w');
        
        if (!$inputHandle || !$outputHandle) {
            throw new RuntimeException('Unable to open file handles for processing');
        }
        
        $firstLine = true;
        
        while (($line = fgets($inputHandle)) !== false) {
            $line = rtrim($line, "\r\n");
            
            if ($this->shouldKeepLine($line, $cutoffDate)) {
                if (!$firstLine) {
                    fwrite($outputHandle, PHP_EOL);
                }
                fwrite($outputHandle, $line);
                $firstLine = false;
            } elseif ($compressHandle) {
                gzwrite($compressHandle, $line . PHP_EOL);
            }
        }
        
        fclose($inputHandle);
        fclose($outputHandle);
        
        if ($compressHandle) {
            gzclose($compressHandle);
            $this->info(sprintf(self::MESSAGE_COMPRESSED, basename($compressedPath)));
        }
        
        if (!rename($tempFile, $filePath)) {
            unlink($tempFile);
            throw new RuntimeException('Failed to replace original file with cleaned version');
        }
    }

    private function dryRunMemoryEfficient(string $filePath, Carbon $cutoffDate): void
    {
        $inputHandle = fopen($filePath, 'r');
        if (!$inputHandle) {
            throw new RuntimeException('Unable to open file for dry run analysis');
        }
        
        $removedCount = 0;
        
        while (($line = fgets($inputHandle)) !== false) {
            $line = rtrim($line, "\r\n");
            if (!$this->shouldKeepLine($line, $cutoffDate)) {
                $removedCount++;
            }
        }
        
        fclose($inputHandle);
        $this->info(sprintf(self::MESSAGE_DRY_RUN, $removedCount, basename($filePath)));
    }

    private function validatePermissions(array $logFiles): void
    {
        foreach ($logFiles as $file) {
            $filePath = $file->getPathname();
            
            if (!is_readable($filePath) || !is_writable($filePath)) {
                throw new RuntimeException(sprintf(self::MESSAGE_PERMISSION_ERROR, $filePath));
            }
        }
    }

    private function createBackup($file): void
    {
        $filePath = $file->getPathname();
        $backupPath = $filePath . '.backup.' . date('Y-m-d-H-i-s');
        
        if (!copy($filePath, $backupPath)) {
            throw new RuntimeException('Failed to create backup for: ' . $filePath);
        }
        
        $this->info(sprintf(self::MESSAGE_BACKUP_CREATED, $backupPath));
    }

    private function validateLogLevel(): void
    {
        $level = $this->option('level');
        if ($level && !in_array(strtoupper($level), self::LOG_LEVELS)) {
            throw new InvalidArgumentException('Invalid log level. Must be one of: ' . implode(', ', self::LOG_LEVELS));
        }
    }

    private function shouldKeepLineByLevel(string $line): bool
    {
        $filterLevel = $this->option('level');
        if (!$filterLevel) {
            return true;
        }

        foreach (self::LOG_LEVELS as $level) {
            if (preg_match('/\.' . $level . ':/', $line)) {
                return strtoupper($filterLevel) === $level;
            }
        }
        
        return true;
    }

    private function compressOldLogs(string $filePath, array $linesToCompress): void
    {
        if (empty($linesToCompress)) {
            return;
        }

        $compressedPath = $filePath . '.old.' . date('Y-m-d-H-i-s') . '.gz';
        $handle = gzopen($compressedPath, 'w9');
        
        if (!$handle) {
            throw new RuntimeException('Failed to create compressed file: ' . $compressedPath);
        }
        
        foreach ($linesToCompress as $line) {
            gzwrite($handle, $line . PHP_EOL);
        }
        
        gzclose($handle);
        $this->info(sprintf(self::MESSAGE_COMPRESSED, $compressedPath));
    }
}