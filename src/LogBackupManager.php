<?php

declare(strict_types=1);

namespace JiordiViera\LaravelLogCleaner;

use Illuminate\Support\Facades\Config;
use JiordiViera\LaravelLogCleaner\Exceptions\BackupException;
use JiordiViera\LaravelLogCleaner\Exceptions\DiskSpaceException;

/**
 * Handles log backup creation, retention and disk-space checks.
 */
final class LogBackupManager
{
    public function validateDiskSpace(string $filePath): void
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

    public function createBackup($file): string
    {
        $filePath = $file->getPathname();
        $backupPath = $filePath.'.backup.'.date('Y-m-d-H-i-s');

        if (! copy($filePath, $backupPath)) {
            throw BackupException::create($filePath);
        }

        return $backupPath;
    }

    public function cleanupOldBackups($file): void
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
}
