<?php

declare(strict_types=1);

namespace JiordiViera\LaravelLogCleaner\Tests;

use Illuminate\Support\Facades\File;

trait CreatesLogFiles
{
    protected function createTestLogFile(string $filename = 'laravel.log', string $content = ''): string
    {
        $logPath = storage_path('logs/'.$filename);

        if (! File::exists(dirname($logPath))) {
            File::makeDirectory(dirname($logPath), 0755, true);
        }

        File::put($logPath, $content);

        return $logPath;
    }

    protected function createLogFileWithEntries(array $entries): string
    {
        $content = implode(PHP_EOL, $entries);

        return $this->createTestLogFile('laravel.log', $content);
    }

    protected function createDatedLogEntry(string $message, string $date, string $level = 'INFO'): string
    {
        return "[{$date}] {$level}: {$message}";
    }

    protected function createOldLogEntries(int $daysAgo, int $count = 5): array
    {
        $entries = [];
        $date = now()->subDays($daysAgo)->format('Y-m-d');

        for ($i = 0; $i < $count; $i++) {
            $entries[] = $this->createDatedLogEntry("Old log entry #{$i}", $date);
        }

        return $entries;
    }

    protected function createNewLogEntries(int $count = 5): array
    {
        $entries = [];
        $date = now()->format('Y-m-d');

        for ($i = 0; $i < $count; $i++) {
            $entries[] = $this->createDatedLogEntry("New log entry #{$i}", $date);
        }

        return $entries;
    }

    protected function createMixedAgeLogEntries(int $oldDays = 10, int $oldCount = 5, int $newCount = 5): array
    {
        return array_merge(
            $this->createOldLogEntries($oldDays, $oldCount),
            $this->createNewLogEntries($newCount)
        );
    }

    protected function createLogEntriesWithLevels(array $levels = ['ERROR', 'WARNING', 'INFO', 'DEBUG']): array
    {
        $entries = [];
        $date = now()->format('Y-m-d');

        foreach ($levels as $level) {
            $entries[] = $this->createDatedLogEntry("{$level} message", $date, $level);
        }

        return $entries;
    }

    protected function createMultipleLogFiles(array $filenames = ['laravel.log', 'other.log', 'daily.log']): void
    {
        foreach ($filenames as $filename) {
            $this->createTestLogFile($filename, "Content of {$filename}\n");
        }
    }

    protected function getLogContent(string $filename = 'laravel.log'): string
    {
        return File::get(storage_path('logs/'.$filename));
    }

    protected function getLogLines(string $filename = 'laravel.log'): array
    {
        $content = $this->getLogContent($filename);

        return explode(PHP_EOL, $content);
    }

    protected function assertLogFileExists(string $filename = 'laravel.log'): void
    {
        expect(storage_path('logs/'.$filename))->toBeReadable();
    }

    protected function assertLogFileIsEmpty(string $filename = 'laravel.log'): void
    {
        expect($this->getLogContent($filename))->toBe('');
    }

    protected function assertLogFileContains(string $content, string $filename = 'laravel.log'): void
    {
        expect($this->getLogContent($filename))->toContain($content);
    }

    protected function assertLogFileDoesNotContain(string $content, string $filename = 'laravel.log'): void
    {
        expect($this->getLogContent($filename))->not->toContain($content);
    }

    protected function cleanupLogFiles(array $filenames = []): void
    {
        $files = empty($filenames) ? ['laravel.log', 'other.log', 'daily.log'] : $filenames;

        foreach ($files as $filename) {
            $logPath = storage_path('logs/'.$filename);
            if (File::exists($logPath)) {
                File::delete($logPath);
            }

            // Cleanup backup files
            $backupFiles = glob($logPath.'.backup.*');
            foreach ($backupFiles as $backupFile) {
                File::delete($backupFile);
            }

            // Cleanup compressed files
            $compressedFiles = glob($logPath.'.old.*.gz');
            foreach ($compressedFiles as $compressedFile) {
                File::delete($compressedFile);
            }

            // Cleanup lock files
            $lockFile = $logPath.'.lock';
            if (File::exists($lockFile)) {
                File::delete($lockFile);
            }
        }
    }
}
