<?php

declare(strict_types=1);

namespace JiordiViera\LaravelLogCleaner;

use Carbon\Carbon;
use Illuminate\Console\OutputStyle;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\File;
use JiordiViera\LaravelLogCleaner\Events\LogCleaned;
use JiordiViera\LaravelLogCleaner\Events\LogCleaning;
use JiordiViera\LaravelLogCleaner\Events\LogFileCleaned;
use JiordiViera\LaravelLogCleaner\Exceptions\BackupException;
use JiordiViera\LaravelLogCleaner\Exceptions\DiskSpaceException;
use JiordiViera\LaravelLogCleaner\Exceptions\FileLockException;
use JiordiViera\LaravelLogCleaner\Exceptions\InvalidDaysException;
use JiordiViera\LaravelLogCleaner\Exceptions\InvalidLogLevelException;
use JiordiViera\LaravelLogCleaner\Exceptions\InvalidPatternException;
use JiordiViera\LaravelLogCleaner\Exceptions\NoLogFilesException;
use JiordiViera\LaravelLogCleaner\Exceptions\PermissionException;
use JiordiViera\LaravelLogCleaner\Exceptions\ZlibException;
use RuntimeException;

class LogCleaner
{
    public const LOG_DIRECTORY = 'logs';

    public const MESSAGE_NO_LOG_FILE = 'No log files found in %s';

    public const MESSAGE_CLEARED_ALL = 'All log files cleared successfully';

    public const MESSAGE_CLEARED_OLD = 'Logs older than %d days have been removed from %s';

    public const MESSAGE_INVALID_DAYS = 'Days must be a positive integer';

    public const MESSAGE_PERMISSION_ERROR = 'Permission denied for file: %s';

    public const MESSAGE_BACKUP_CREATED = 'Backup created: %s';

    public const MESSAGE_COMPRESSED = 'Logs compressed to: %s';

    public const MESSAGE_ZLIB_MISSING = 'The zlib extension is required for compression. Please install it to use the --compress option.';

    public const LOG_LEVELS = ['EMERGENCY', 'ALERT', 'CRITICAL', 'ERROR', 'WARNING', 'NOTICE', 'INFO', 'DEBUG'];

    public const DEFAULT_LOG_PATTERNS = [
        '/^\[(\d{4}-\d{2}-\d{2})/',
        '/^\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})/',
        '/^(\d{4}-\d{2}-\d{2})/',
    ];

    /** @var array<string, string> */
    private array $compiledPatterns = [];

    private ?OutputStyle $output = null;

    /** @var array<int, array{file: string, lines_removed: int, bytes_freed: int, backup_path: string|null, compressed_path: string|null}> */
    private array $cleaningResults = [];

    public function setOutput(OutputStyle $output): self
    {
        $this->output = $output;

        return $this;
    }

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

    public function clear(
        int $days = 0,
        bool $backup = false,
        bool $compress = false,
        ?string $level = null,
        ?string $pattern = null,
        bool $memoryEfficient = false,
        ?string $file = null,
        bool $dryRun = false
    ): void {
        $logDir = $this->getLogDirectory();

        // Load config defaults
        $backup = $backup || Config::get('log-cleaner.backup.enabled', false);
        $compress = $compress || Config::get('log-cleaner.compression.enabled', false);
        $level = $level ?? Config::get('log-cleaner.level');
        $pattern = $pattern ?? Config::get('log-cleaner.pattern');
        $memoryThreshold = Config::get('log-cleaner.memory_threshold', 50 * 1024 * 1024);
        $eventsEnabled = Config::get('log-cleaner.events.enabled', true);

        $this->validateDays($days);
        $this->compilePatterns($pattern);
        $this->validateLogLevel($level);
        $this->validateZlibExtension($compress);

        $logFiles = $this->getLogFiles($logDir, $file);

        if (empty($logFiles)) {
            throw NoLogFilesException::create($logDir);
        }

        // Dispatch starting event
        if ($eventsEnabled) {
            Event::dispatch(new LogCleaning($days, $backup, $compress, $level, $pattern, $memoryEfficient, $file, $dryRun));
        }

        $this->cleaningResults = [];

        foreach ($logFiles as $logFile) {
            $filePath = $logFile->getPathname();

            // Check disk space for backup
            if ($backup && ! $dryRun) {
                $this->validateDiskSpace($filePath);
            }

            // File locking
            if (Config::get('log-cleaner.locking.enabled', true) && ! $dryRun) {
                $this->acquireLock($filePath);
            }

            $this->validatePermissions([$logFile]);

            $linesRemoved = 0;
            $bytesFreed = 0;
            $backupPath = null;
            $compressedPath = null;

            if ($backup && ! $dryRun) {
                $backupPath = $this->createBackup($logFile);
                $this->cleanupOldBackups($logFile);
            }

            if ($days === 0 && ! $level) {
                $result = $this->clearAllLogs($logFile, $dryRun);
                $linesRemoved = $result['lines'];
                $bytesFreed = $result['bytes'];
            } else {
                $result = $this->clearOldLogs(
                    $logFile,
                    $days,
                    $compress,
                    $level,
                    $memoryEfficient,
                    $dryRun,
                    $memoryThreshold
                );
                $linesRemoved = $result['lines'];
                $bytesFreed = $result['bytes'];
                $compressedPath = $result['compressed_path'] ?? null;
            }

            // Release lock
            if (Config::get('log-cleaner.locking.enabled', true) && ! $dryRun) {
                $this->releaseLock($filePath);
            }

            // Dispatch per-file event
            if ($eventsEnabled && ! $dryRun) {
                Event::dispatch(new LogFileCleaned($filePath, $linesRemoved, $bytesFreed, $backupPath, $compressedPath));
            }

            $this->cleaningResults[] = [
                'file' => $filePath,
                'lines_removed' => $linesRemoved,
                'bytes_freed' => $bytesFreed,
                'backup_path' => $backupPath,
                'compressed_path' => $compressedPath,
            ];
        }

        // Dispatch completed event
        if ($eventsEnabled && ! $dryRun) {
            Event::dispatch(new LogCleaned($days, $backup, $compress, $level, $pattern, $memoryEfficient, $file, $dryRun, $this->cleaningResults));
        }
    }

    private function getLogDirectory(): string
    {
        return storage_path(self::LOG_DIRECTORY);
    }

    private function validateDays(int $days): void
    {
        if ($days < 0) {
            throw InvalidDaysException::create();
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

    private function clearAllLogs($file, bool $dryRun = false): array
    {
        $filePath = $file->getPathname();
        $bytes = filesize($filePath);

        if (! $dryRun) {
            File::put($filePath, '');
        }

        return [
            'lines' => count(file($filePath, FILE_IGNORE_NEW_LINES)),
            'bytes' => $bytes,
        ];
    }

    private function clearOldLogs(
        $file,
        int $days,
        bool $compress,
        ?string $level,
        bool $memoryEfficient,
        bool $dryRun,
        int $memoryThreshold
    ): array {
        $cutoffDate = Carbon::now()->subDays($days)->startOfDay();
        $filePath = $file->getPathname();
        $fileSize = filesize($filePath);

        $useMemoryEfficient = $memoryEfficient || $fileSize > $memoryThreshold;

        if ($useMemoryEfficient) {
            return $this->clearOldLogsMemoryEfficient($filePath, $cutoffDate, $compress, $level, $dryRun);
        }

        return $this->clearOldLogsStandard($filePath, $cutoffDate, $compress, $level, $dryRun);
    }

    private function filterOldLogs(array $lines, Carbon $cutoffDate, ?string $level): array
    {
        return array_filter($lines, function ($line) use ($cutoffDate, $level) {
            return $this->shouldKeepLine($line, $cutoffDate, $level);
        });
    }

    private function shouldKeepLine(string $line, Carbon $cutoffDate, ?string $level): bool
    {
        if (! $this->shouldKeepLineByLevel($line, $level)) {
            return false;
        }

        if ($level && $cutoffDate->isToday()) {
            return true;
        }

        foreach ($this->compiledPatterns as $pattern => $format) {
            if (preg_match($pattern, $line, $matches)) {
                try {
                    $logDate = Carbon::createFromFormat($format, $matches[1])->startOfDay();

                    return $logDate->greaterThanOrEqualTo($cutoffDate);
                } catch (\Exception $e) {
                    continue;
                }
            }
        }

        return true;
    }

    private function compilePatterns(?string $customPattern): void
    {
        if ($customPattern) {
            if (@preg_match($customPattern, '') === false) {
                throw InvalidPatternException::create(preg_last_error_msg());
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

    private function clearOldLogsStandard(string $filePath, Carbon $cutoffDate, bool $compress, ?string $level, bool $dryRun): array
    {
        $content = File::get($filePath);
        $lines = explode(PHP_EOL, $content);
        $newLines = $this->filterOldLogs($lines, $cutoffDate, $level);

        $linesToRemove = array_diff($lines, $newLines);
        $linesRemoved = count($linesToRemove);
        $bytesFreed = array_sum(array_map('strlen', $linesToRemove));

        if (! $dryRun) {
            if ($compress) {
                $compressedPath = $this->compressOldLogs($filePath, $linesToRemove);
            }

            File::put($filePath, implode(PHP_EOL, $newLines));
        }

        return [
            'lines' => $linesRemoved,
            'bytes' => $bytesFreed,
            'compressed_path' => ($compress && ! $dryRun) ? ($compressedPath ?? null) : null,
        ];
    }

    private function clearOldLogsMemoryEfficient(string $filePath, Carbon $cutoffDate, bool $compress, ?string $level, bool $dryRun): array
    {
        $tempFile = $filePath.'.tmp';
        $compressHandle = null;
        $compressedPath = null;

        if ($compress && ! $dryRun) {
            $compressedPath = $filePath.'.old.'.date('Y-m-d-H-i-s').'.gz';
            $compressHandle = gzopen($compressedPath, 'w9');
        }

        $inputHandle = fopen($filePath, 'r');
        $outputHandle = fopen($tempFile, 'w');

        if (! $inputHandle || ! $outputHandle) {
            throw new RuntimeException('Unable to open file handles for processing');
        }

        $firstLine = true;
        $linesRemoved = 0;
        $bytesFreed = 0;

        while (($line = fgets($inputHandle)) !== false) {
            $line = rtrim($line, "\r\n");

            if ($this->shouldKeepLine($line, $cutoffDate, $level)) {
                if (! $firstLine) {
                    fwrite($outputHandle, PHP_EOL);
                }
                fwrite($outputHandle, $line);
                $firstLine = false;
            } else {
                $linesRemoved++;
                $bytesFreed += strlen($line);

                if ($compressHandle) {
                    gzwrite($compressHandle, $line.PHP_EOL);
                }
            }
        }

        fclose($inputHandle);
        fclose($outputHandle);

        if ($compressHandle) {
            gzclose($compressHandle);
        }

        if (! $dryRun) {
            if (! rename($tempFile, $filePath)) {
                unlink($tempFile);
                throw new RuntimeException('Failed to replace original file with cleaned version');
            }
        } else {
            unlink($tempFile);
        }

        return [
            'lines' => $linesRemoved,
            'bytes' => $bytesFreed,
            'compressed_path' => ($compress && ! $dryRun) ? $compressedPath : null,
        ];
    }

    private function validatePermissions(array $logFiles): void
    {
        foreach ($logFiles as $file) {
            $filePath = $file->getPathname();

            if (! is_readable($filePath) || ! is_writable($filePath)) {
                throw PermissionException::create($filePath);
            }
        }
    }

    private function createBackup($file): string
    {
        $filePath = $file->getPathname();
        $backupPath = $filePath.'.backup.'.date('Y-m-d-H-i-s');

        if (! copy($filePath, $backupPath)) {
            throw BackupException::create($filePath);
        }

        return $backupPath;
    }

    private function validateLogLevel(?string $level): void
    {
        if ($level && ! in_array(strtoupper($level), self::LOG_LEVELS)) {
            throw InvalidLogLevelException::create($level, self::LOG_LEVELS);
        }
    }

    private function validateZlibExtension(bool $compress): void
    {
        if ($compress && ! extension_loaded('zlib')) {
            throw ZlibException::create();
        }
    }

    private function shouldKeepLineByLevel(string $line, ?string $filterLevel): bool
    {
        if (! $filterLevel) {
            return true;
        }

        foreach (self::LOG_LEVELS as $level) {
            if (preg_match('/\.'.$level.':/', $line)) {
                return strtoupper($filterLevel) === $level;
            }
        }

        return true;
    }

    private function compressOldLogs(string $filePath, array $linesToCompress): ?string
    {
        if (empty($linesToCompress)) {
            return null;
        }

        $compressedPath = $filePath.'.old.'.date('Y-m-d-H-i-s').'.gz';
        $compressionLevel = Config::get('log-cleaner.compression.level', 9);
        $handle = gzopen($compressedPath, 'w'.$compressionLevel);

        if (! $handle) {
            throw new RuntimeException('Failed to create compressed file: '.$compressedPath);
        }

        foreach ($linesToCompress as $line) {
            gzwrite($handle, $line.PHP_EOL);
        }

        gzclose($handle);

        return $compressedPath;
    }

    public function dryRun(int $days = 0, ?string $file = null, ?string $level = null, ?string $pattern = null): array
    {
        $logDir = $this->getLogDirectory();
        $this->validateDays($days);
        $this->compilePatterns($pattern);
        $this->validateLogLevel($level);

        $logFiles = $this->getLogFiles($logDir, $file);

        if (empty($logFiles)) {
            throw NoLogFilesException::create($logDir);
        }

        $results = [];
        $memoryThreshold = Config::get('log-cleaner.memory_threshold', 50 * 1024 * 1024);

        foreach ($logFiles as $logFile) {
            $filePath = $logFile->getPathname();
            $cutoffDate = Carbon::now()->subDays($days)->startOfDay();

            if (filesize($filePath) > $memoryThreshold) {
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
                    'estimated_space' => $this->formatBytes($estimatedBytes),
                ];
            }

            $results[] = $result;
        }

        return $results;
    }

    private function dryRunMemoryEfficient(string $filePath, Carbon $cutoffDate, ?string $level): array
    {
        $inputHandle = fopen($filePath, 'r');
        if (! $inputHandle) {
            throw new RuntimeException('Unable to open file for dry run analysis');
        }

        $removedCount = 0;
        $estimatedBytes = 0;

        while (($line = fgets($inputHandle)) !== false) {
            $line = rtrim($line, "\r\n");
            if (! $this->shouldKeepLine($line, $cutoffDate, $level)) {
                $removedCount++;
                $estimatedBytes += strlen($line);
            }
        }

        fclose($inputHandle);

        return [
            'file' => basename($filePath),
            'removed_lines' => $removedCount,
            'estimated_space' => $this->formatBytes($estimatedBytes),
        ];
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));

        return round($bytes, 2).' '.$units[$pow];
    }

    private function validateDiskSpace(string $filePath): void
    {
        $minSpaceMb = Config::get('log-cleaner.min_free_disk_space_mb', 100);

        if ($minSpaceMb <= 0) {
            return;
        }

        $fileSize = filesize($filePath);
        $requiredSpace = $fileSize * 1.1; // Add 10% buffer
        $requiredSpaceMb = $requiredSpace / 1024 / 1024;

        $freeSpace = disk_free_space(dirname($filePath)) / 1024 / 1024;

        if ($freeSpace < $requiredSpaceMb) {
            throw DiskSpaceException::insufficient($requiredSpaceMb, $freeSpace);
        }

        if ($freeSpace < $minSpaceMb) {
            throw DiskSpaceException::insufficient($minSpaceMb, $freeSpace);
        }
    }

    private function acquireLock(string $filePath): void
    {
        $lockFile = $filePath.'.lock';
        $timeout = Config::get('log-cleaner.locking.timeout', 30);
        $startTime = time();

        while (file_exists($lockFile)) {
            if (time() - $startTime >= $timeout) {
                throw FileLockException::timeout($filePath, $timeout);
            }
            usleep(100000); // Wait 100ms
        }

        file_put_contents($lockFile, getmypid());
    }

    private function releaseLock(string $filePath): void
    {
        $lockFile = $filePath.'.lock';
        if (file_exists($lockFile)) {
            unlink($lockFile);
        }
    }

    private function cleanupOldBackups($file): void
    {
        $maxBackups = Config::get('log-cleaner.backup.max_backups', 5);
        $autoCleanup = Config::get('log-cleaner.backup.auto_cleanup', true);

        if (! $autoCleanup || $maxBackups <= 0) {
            return;
        }

        $filePath = $file->getPathname();
        $backupPattern = preg_quote($filePath.'.backup.', '/').'\d{4}-\d{2}-\d{2}-\d{2}-\d{2}-\d{2}';

        $backupFiles = glob($filePath.'.backup.*');

        if (count($backupFiles) > $maxBackups) {
            sort($backupFiles);
            $filesToRemove = array_slice($backupFiles, 0, count($backupFiles) - $maxBackups);

            foreach ($filesToRemove as $fileToRemove) {
                unlink($fileToRemove);
            }
        }
    }

    public function getCleaningResults(): array
    {
        return $this->cleaningResults;
    }
}
