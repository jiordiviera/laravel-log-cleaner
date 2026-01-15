<?php

declare(strict_types=1);

namespace JiordiViera\LaravelLogCleaner;

use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\File;
use InvalidArgumentException;
use RuntimeException;
use SplFileObject;

class LogCleaner
{
    const LOG_DIRECTORY = 'logs';
    const MESSAGE_NO_LOG_FILE = 'No log files found in %s';
    const MESSAGE_CLEARED_ALL = 'All log files cleared successfully';
    const MESSAGE_CLEARED_OLD = 'Logs older than %d days have been removed from %s';
    const MESSAGE_INVALID_DAYS = 'Days must be a positive integer';
    const MESSAGE_PERMISSION_ERROR = 'Permission denied for file: %s';
    const MESSAGE_BACKUP_CREATED = 'Backup created: %s';
    const MESSAGE_COMPRESSED = 'Logs compressed to: %s';
    const MESSAGE_ZLIB_MISSING = 'The zlib extension is required for compression. Please install it to use the --compress option.';
    const MEMORY_THRESHOLD = 50 * 1024 * 1024; // 50MB
    const LOG_LEVELS = ['EMERGENCY', 'ALERT', 'CRITICAL', 'ERROR', 'WARNING', 'NOTICE', 'INFO', 'DEBUG'];
    const DEFAULT_LOG_PATTERNS = [
        '/^\[(\d{4}-\d{2}-\d{2})/',
        '/^\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})/',
        '/^(\d{4}-\d{2}-\d{2})/',
    ];

    private array $compiledPatterns = [];

    public function clearAll(?string $file = null): void
    {
        $this->clear(0, false, false, null, null, false, $file);
    }

    public function clearOld(int $days, ?string $file = null): void
    {
        $this->clear($days, false, false, null, null, false, $file);
    }

    public function clearWithBackup(int $days = 0, ?string $file = null): void
    {
        $this->clear($days, true, false, null, null, false, $file);
    }

    public function clearWithCompression(int $days = 0, ?string $file = null): void
    {
        $this->clear($days, false, true, null, null, false, $file);
    }

    public function clear(int $days = 0, bool $backup = false, bool $compress = false, ?string $level = null, ?string $pattern = null, bool $memoryEfficient = false, ?string $file = null): void
    {
        $logDir = $this->getLogDirectory();
        $this->validateDays($days);
        $this->compilePatterns($pattern);
        $this->validateLogLevel($level);
        $this->validateZlibExtension($compress);

        $logFiles = $this->getLogFiles($logDir, $file);

        if (empty($logFiles)) {
            throw new RuntimeException(sprintf(self::MESSAGE_NO_LOG_FILE, $logDir));
        }

        $this->validatePermissions($logFiles);

        foreach ($logFiles as $logFile) {
            if ($backup) {
                $this->createBackup($logFile);
            }

            if ($days === 0 && !$level) {
                $this->clearAllLogs($logFile);
            } else {
                $this->clearOldLogs($logFile, $days, $compress, $level, $memoryEfficient);
            }
        }
    }

    private function getLogDirectory(): string
    {
        return storage_path(self::LOG_DIRECTORY);
    }

    private function validateDays(int $days): void
    {
        if ($days < 0) {
            throw new InvalidArgumentException(self::MESSAGE_INVALID_DAYS);
        }
    }

    private function getLogFiles(string $logDir, ?string $file = null): array
    {
        $files = File::files($logDir);
        
        if ($file) {
            $files = array_filter($files, function ($f) use ($file) {
                return $f->getFilename() === $file;
            });
        } else {
            $files = array_filter($files, function ($f) {
                return $f->getExtension() === 'log';
            });
        }
        
        return $files;
    }

    private function clearAllLogs($file): void
    {
        File::put($file->getPathname(), '');
    }

    private function clearOldLogs($file, int $days, bool $compress, ?string $level, bool $memoryEfficient): void
    {
        $cutoffDate = Carbon::now()->subDays($days)->startOfDay();
        $filePath = $file->getPathname();

        if ($memoryEfficient || filesize($filePath) > self::MEMORY_THRESHOLD) {
            $this->clearOldLogsMemoryEfficient($filePath, $cutoffDate, $compress, $level);
        } else {
            $this->clearOldLogsStandard($filePath, $cutoffDate, $compress, $level);
        }
    }

    private function filterOldLogs(array $lines, Carbon $cutoffDate, ?string $level): array
    {
        return array_filter($lines, function ($line) use ($cutoffDate, $level) {
            return $this->shouldKeepLine($line, $cutoffDate, $level);
        });
    }

    private function shouldKeepLine(string $line, Carbon $cutoffDate, ?string $level): bool
    {
        // First check log level filter
        if (!$this->shouldKeepLineByLevel($line, $level)) {
            return false;
        }

        // If only filtering by level (days=0 and level specified), keep the line
        if ($level && $cutoffDate->isToday()) {
            return true;
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

    private function compilePatterns(?string $customPattern): void
    {
        if ($customPattern) {
            // Validate regex pattern
            if (@preg_match($customPattern, '') === false) {
                throw new InvalidArgumentException('Invalid regex pattern provided: ' . preg_last_error_msg());
            }
            $this->compiledPatterns = [$customPattern => 'Y-m-d'];
        } else {
            $this->compiledPatterns = [
                '/^\[(\d{4}-\d{2}-\d{2})/' => 'Y-m-d',
                '/^\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})/' => 'Y-m-d H:i:s',
                '/^(\d{4}-\d{2}-\d{2})/' => 'Y-m-d',
            ];
        }
    }

    private function clearOldLogsStandard(string $filePath, Carbon $cutoffDate, bool $compress, ?string $level): void
    {
        $content = File::get($filePath);
        $lines = explode(PHP_EOL, $content);
        $newLines = $this->filterOldLogs($lines, $cutoffDate, $level);

        if ($compress) {
            $linesToCompress = array_diff($lines, $newLines);
            $this->compressOldLogs($filePath, $linesToCompress);
        }

        File::put($filePath, implode(PHP_EOL, $newLines));
    }

    private function clearOldLogsMemoryEfficient(string $filePath, Carbon $cutoffDate, bool $compress, ?string $level): void
    {
        $tempFile = $filePath . '.tmp';
        $compressHandle = null;
        
        if ($compress) {
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
            
            if ($this->shouldKeepLine($line, $cutoffDate, $level)) {
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
        }
        
        if (!rename($tempFile, $filePath)) {
            unlink($tempFile);
            throw new RuntimeException('Failed to replace original file with cleaned version');
        }
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
    }

    private function validateLogLevel(?string $level): void
    {
        if ($level && !in_array(strtoupper($level), self::LOG_LEVELS)) {
            throw new InvalidArgumentException('Invalid log level. Must be one of: ' . implode(', ', self::LOG_LEVELS));
        }
    }

    private function validateZlibExtension(bool $compress): void
    {
        if ($compress && !extension_loaded('zlib')) {
            throw new RuntimeException(self::MESSAGE_ZLIB_MISSING);
        }
    }

    private function shouldKeepLineByLevel(string $line, ?string $filterLevel): bool
    {
        if (!$filterLevel) {
            return true;
        }

        // Check if line has a log level
        foreach (self::LOG_LEVELS as $level) {
            if (preg_match('/\.' . $level . ':/', $line)) {
                return strtoupper($filterLevel) === $level;
            }
        }
        
        // If no log level found in line, keep it (might be multiline log continuation)
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
    }

    public function dryRun(int $days = 0, ?string $file = null, ?string $level = null, ?string $pattern = null): array
    {
        $logDir = $this->getLogDirectory();
        $this->validateDays($days);
        $this->compilePatterns($pattern);
        $this->validateLogLevel($level);

        $logFiles = $this->getLogFiles($logDir, $file);

        if (empty($logFiles)) {
            throw new RuntimeException(sprintf(self::MESSAGE_NO_LOG_FILE, $logDir));
        }

        $results = [];

        foreach ($logFiles as $logFile) {
            $filePath = $logFile->getPathname();
            $cutoffDate = Carbon::now()->subDays($days)->startOfDay();
            
            if (filesize($filePath) > self::MEMORY_THRESHOLD) {
                $result = $this->dryRunMemoryEfficient($filePath, $cutoffDate, $level);
            } else {
                $content = File::get($filePath);
                $lines = explode(PHP_EOL, $content);
                $newLines = $this->filterOldLogs($lines, $cutoffDate, $level);
                $removedCount = count($lines) - count($newLines);
                $estimatedBytes = array_sum(array_map('strlen', array_diff($lines, $newLines)));
                $result = [
                    'file' => basename($filePath),
                    'removed_lines' => $removedCount,
                    'estimated_space' => $this->formatBytes($estimatedBytes)
                ];
            }
            
            $results[] = $result;
        }

        return $results;
    }

    private function dryRunMemoryEfficient(string $filePath, Carbon $cutoffDate, ?string $level): array
    {
        $inputHandle = fopen($filePath, 'r');
        if (!$inputHandle) {
            throw new RuntimeException('Unable to open file for dry run analysis');
        }

        $removedCount = 0;
        $estimatedBytes = 0;

        while (($line = fgets($inputHandle)) !== false) {
            $line = rtrim($line, "\r\n");
            if (!$this->shouldKeepLine($line, $cutoffDate, $level)) {
                $removedCount++;
                $estimatedBytes += strlen($line);
            }
        }

        fclose($inputHandle);
        
        return [
            'file' => basename($filePath),
            'removed_lines' => $removedCount,
            'estimated_space' => $this->formatBytes($estimatedBytes)
        ];
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));

        return round($bytes, 2) . ' ' . $units[$pow];
    }
}
