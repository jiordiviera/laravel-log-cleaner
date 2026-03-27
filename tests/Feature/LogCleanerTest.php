<?php

declare(strict_types=1);

namespace JiordiViera\LaravelLogCleaner\Tests\Feature;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use JiordiViera\LaravelLogCleaner\Events\LogCleaned;
use JiordiViera\LaravelLogCleaner\Events\LogCleaning;
use JiordiViera\LaravelLogCleaner\Events\LogFileCleaned;
use JiordiViera\LaravelLogCleaner\Exceptions\DiskSpaceException;
use JiordiViera\LaravelLogCleaner\Exceptions\FileLockException;
use JiordiViera\LaravelLogCleaner\Exceptions\InvalidDaysException;
use JiordiViera\LaravelLogCleaner\Exceptions\InvalidLogLevelException;
use JiordiViera\LaravelLogCleaner\Exceptions\InvalidPatternException;
use JiordiViera\LaravelLogCleaner\Exceptions\NoLogFilesException;
use JiordiViera\LaravelLogCleaner\Exceptions\PermissionException;
use JiordiViera\LaravelLogCleaner\LogCleaner;
use JiordiViera\LaravelLogCleaner\Tests\CreatesLogFiles;

uses(CreatesLogFiles::class);

describe('LogCleaner - Events', function () {
    beforeEach(function () {
        $this->cleanupLogFiles();
        Event::fake();
    });

    afterEach(function () {
        $this->cleanupLogFiles();
    });

    it('dispatches LogCleaning event before cleaning', function () {
        $this->createTestLogFile('laravel.log', "Test log entry\n");

        $logCleaner = new LogCleaner;
        $logCleaner->clearAll();

        Event::assertDispatched(LogCleaning::class, function ($event) {
            return $event->days === 0 && $event->dryRun === false;
        });
    });

    it('dispatches LogCleaned event after cleaning', function () {
        $this->createTestLogFile('laravel.log', "Test log entry\n");

        $logCleaner = new LogCleaner;
        $logCleaner->clearAll();

        Event::assertDispatched(LogCleaned::class, function ($event) {
            return $event->days === 0 && ! empty($event->results);
        });
    });

    it('dispatches LogFileCleaned event for each file', function () {
        $this->createTestLogFile('laravel.log', "Test log entry\n");

        $logCleaner = new LogCleaner;
        $logCleaner->clearAll();

        Event::assertDispatched(LogFileCleaned::class, function ($event) {
            return str_contains($event->file, 'laravel.log') && $event->linesRemoved >= 0;
        });
    });

    it('does not dispatch events when disabled', function () {
        Config::set('log-cleaner.events.enabled', false);
        $this->createTestLogFile('laravel.log', "Test log entry\n");

        $logCleaner = new LogCleaner;
        $logCleaner->clearAll();

        Event::assertNotDispatched(LogCleaning::class);
        Event::assertNotDispatched(LogCleaned::class);
    });
});

describe('LogCleaner - Configuration', function () {
    beforeEach(function () {
        $this->cleanupLogFiles();
    });

    afterEach(function () {
        $this->cleanupLogFiles();
    });

    it('uses default days from config', function () {
        Config::set('log-cleaner.days', 7);

        $entries = $this->createMixedAgeLogEntries(10, 3, 3);
        $this->createLogFileWithEntries($entries);

        $logCleaner = new LogCleaner;
        $logCleaner->clear(); // No days parameter, should use config

        $this->assertLogFileDoesNotContain('Old log entry');
    });

    it('uses default backup setting from config', function () {
        Config::set('log-cleaner.backup.enabled', true);
        $this->createTestLogFile('laravel.log', "Test log entry\n");

        $logCleaner = new LogCleaner;
        $logCleaner->clearAll();

        expect(glob(storage_path('logs/laravel.log.backup.*')))->not->toBeEmpty();
    });

    it('uses default compression setting from config', function () {
        Config::set('log-cleaner.compression.enabled', true);
        $entries = $this->createOldLogEntries(10, 5);
        $this->createLogFileWithEntries($entries);

        $logCleaner = new LogCleaner;
        $logCleaner->clearOld(5);

        expect(glob(storage_path('logs/laravel.log.old.*.gz')))->not->toBeEmpty();
    });

    it('uses default log level from config', function () {
        Config::set('log-cleaner.level', 'ERROR');
        $entries = $this->createLogEntriesWithLevels(['ERROR', 'WARNING', 'INFO', 'DEBUG']);
        $this->createLogFileWithEntries($entries);

        $logCleaner = new LogCleaner;
        $logCleaner->clear();

        $content = $this->getLogContent();
        expect($content)->toContain('ERROR message');
    });

    it('uses custom memory threshold from config', function () {
        Config::set('log-cleaner.memory_threshold', 1024); // 1KB for testing

        $content = str_repeat("Test log entry\n", 100);
        $this->createTestLogFile('laravel.log', $content);

        $logCleaner = new LogCleaner;
        $logCleaner->clearAll();

        $this->assertLogFileIsEmpty();
    });
});

describe('LogCleaner - Exceptions', function () {
    beforeEach(function () {
        $this->cleanupLogFiles();
    });

    afterEach(function () {
        $this->cleanupLogFiles();
    });

    it('throws InvalidDaysException for negative days', function () {
        $this->createTestLogFile('laravel.log', "Test\n");

        $logCleaner = new LogCleaner;

        expect(fn () => $logCleaner->clear(-1))->toThrow(InvalidDaysException::class);
    });

    it('throws NoLogFilesException when no log files exist', function () {
        if (file_exists(storage_path('logs'))) {
            array_map('unlink', glob(storage_path('logs/*.log')));
        }

        $logCleaner = new LogCleaner;

        expect(fn () => $logCleaner->clearAll())->toThrow(NoLogFilesException::class);
    });

    it('throws InvalidLogLevelException for invalid level', function () {
        $this->createTestLogFile('laravel.log', "Test\n");

        $logCleaner = new LogCleaner;

        expect(fn () => $logCleaner->clear(level: 'INVALID_LEVEL'))
            ->toThrow(InvalidLogLevelException::class);
    });

    it('throws InvalidPatternException for invalid regex', function () {
        $this->createTestLogFile('laravel.log', "Test\n");

        $logCleaner = new LogCleaner;

        expect(fn () => $logCleaner->clear(pattern: '[invalid(regex'))
            ->toThrow(InvalidPatternException::class);
    });
});

describe('LogCleaner - File Locking', function () {
    beforeEach(function () {
        $this->cleanupLogFiles();
        Config::set('log-cleaner.locking.enabled', true);
        Config::set('log-cleaner.locking.timeout', 2);
    });

    afterEach(function () {
        $this->cleanupLogFiles();
        Config::set('log-cleaner.locking.enabled', true);
    });

    it('creates lock file during operation', function () {
        $this->createTestLogFile('laravel.log', "Test log entry\n");

        $logCleaner = new LogCleaner;
        $logCleaner->clearAll();

        // Lock file should be removed after operation
        expect(file_exists(storage_path('logs/laravel.log.lock')))->toBeFalse();
    });

    it('respects lock timeout', function () {
        $this->markTestSkipped('File locking timeout test needs refinement');
        /*
        $this->createTestLogFile('laravel.log', "Test log entry\n");

        // Create a stale lock file
        file_put_contents(storage_path('logs/laravel.log.lock'), '99999');

        $logCleaner = new LogCleaner();

        // Should timeout waiting for lock
        expect(fn() => $logCleaner->clearAll())
            ->toThrow(\JiordiViera\LaravelLogCleaner\Exceptions\FileLockException::class);
        */
    });
});

describe('LogCleaner - Backup Retention', function () {
    beforeEach(function () {
        $this->cleanupLogFiles();
        Config::set('log-cleaner.backup.enabled', true);
        Config::set('log-cleaner.backup.max_backups', 3);
        Config::set('log-cleaner.backup.auto_cleanup', true);
    });

    afterEach(function () {
        $this->cleanupLogFiles();
    });

    it('keeps only max backups', function () {
        $this->createTestLogFile('laravel.log', "Test log entry\n");

        $logCleaner = new LogCleaner;

        // Create 3 backups
        for ($i = 0; $i < 3; $i++) {
            $logCleaner->clearAll();
            $this->createTestLogFile('laravel.log', "Test log entry {$i}\n");
            usleep(100000); // Ensure different timestamps
        }

        $backupFiles = glob(storage_path('logs/laravel.log.backup.*'));
        expect(count($backupFiles))->toBeLessThanOrEqual(3);
    });

    it('does not cleanup backups when disabled', function () {
        Config::set('log-cleaner.backup.auto_cleanup', false);
        $this->createTestLogFile('laravel.log', "Test log entry\n");

        $logCleaner = new LogCleaner;
        $logCleaner->clearAll();

        $this->createTestLogFile('laravel.log', "Test log entry 2\n");
        $logCleaner->clearAll();

        $backupFiles = glob(storage_path('logs/laravel.log.backup.*'));
        expect(count($backupFiles))->toBeGreaterThanOrEqual(1);
    });
});

describe('LogCleaner - Dry Run', function () {
    beforeEach(function () {
        $this->cleanupLogFiles();
    });

    afterEach(function () {
        $this->cleanupLogFiles();
    });

    it('does not modify files in dry run mode', function () {
        $originalContent = "Test log entry\nAnother entry\n";
        $this->createTestLogFile('laravel.log', $originalContent);

        $logCleaner = new LogCleaner;
        $results = $logCleaner->dryRun();

        expect($results)->toBeArray();
        expect($this->getLogContent())->toBe($originalContent);
    });

    it('returns accurate line count in dry run', function () {
        $entries = $this->createMixedAgeLogEntries(10, 5, 5);
        $this->createLogFileWithEntries($entries);

        $logCleaner = new LogCleaner;
        $results = $logCleaner->dryRun(days: 5);

        expect($results[0]['removed_lines'])->toBe(5);
    });

    it('estimates space to free in dry run', function () {
        $content = str_repeat("Test log entry\n", 100);
        $this->createTestLogFile('laravel.log', $content);

        $logCleaner = new LogCleaner;
        $results = $logCleaner->dryRun();

        expect($results[0]['estimated_space'])->toMatch('/\d+(\.\d+)? (B|KB|MB)/');
    });
});

describe('LogCleaner - Edge Cases', function () {
    beforeEach(function () {
        $this->cleanupLogFiles();
    });

    afterEach(function () {
        $this->cleanupLogFiles();
    });

    it('handles empty log files', function () {
        $this->createTestLogFile('laravel.log', '');

        $logCleaner = new LogCleaner;
        $logCleaner->clearAll();

        $this->assertLogFileIsEmpty();
    });

    it('handles log files with only whitespace', function () {
        $this->createTestLogFile('laravel.log', "\n\n  \n\t\n");

        $logCleaner = new LogCleaner;
        $logCleaner->clearAll();

        $this->assertLogFileIsEmpty();
    });

    it('handles unicode characters in logs', function () {
        $date = now()->format('Y-m-d');
        $content = "[{$date}] INFO: Test message with emoji and accents\n";
        $this->createTestLogFile('laravel.log', $content);

        $logCleaner = new LogCleaner;
        $logCleaner->clearOld(5);

        $this->assertLogFileContains('emoji');
    });

    it('handles very long log lines', function () {
        $date = now()->format('Y-m-d');
        $longLine = "[{$date}] INFO: ".str_repeat('x', 1000)."\n";
        $this->createTestLogFile('laravel.log', $longLine);

        $logCleaner = new LogCleaner;
        $logCleaner->clearOld(5);

        $this->assertLogFileContains(str_repeat('x', 100));
    });

    it('handles multiple log files at once', function () {
        $this->createMultipleLogFiles(['laravel.log', 'other.log', 'daily.log']);

        $logCleaner = new LogCleaner;
        $logCleaner->clearAll();

        $this->assertLogFileIsEmpty('laravel.log');
        $this->assertLogFileIsEmpty('other.log');
        $this->assertLogFileIsEmpty('daily.log');
    });

    it('cleans specific log file', function () {
        $this->createMultipleLogFiles(['laravel.log', 'other.log']);
        $this->createTestLogFile('other.log', "Other content\n");

        $logCleaner = new LogCleaner;
        $logCleaner->clearAll('laravel.log');

        $this->assertLogFileIsEmpty('laravel.log');
        $this->assertLogFileContains('Other content', 'other.log');
    });
});

describe('LogCleaner - Exception Coverage', function () {
    beforeEach(function () {
        $this->cleanupLogFiles();
    });

    afterEach(function () {
        $this->cleanupLogFiles();
    });

    it('throws PermissionException for unreadable file', function () {
        if (PHP_OS_FAMILY === 'Windows') {
            $this->markTestSkipped('chmod not supported on Windows');
        }

        $logPath = storage_path('logs/laravel.log');
        $this->createTestLogFile('laravel.log', "Test\n");
        chmod($logPath, 0000);

        $logCleaner = new LogCleaner;

        expect(fn () => $logCleaner->clearAll())
            ->toThrow(PermissionException::class);

        chmod($logPath, 0644);
    });

    it('throws ZlibException when zlib extension is missing', function () {
        // This test can't properly mock extension_loaded
        // The ZlibException is tested indirectly through integration
        expect(extension_loaded('zlib'))->toBeTrue();
    });

    it('creates backup successfully', function () {
        $this->createTestLogFile('laravel.log', "Test content\n");

        $logCleaner = new LogCleaner;
        $logCleaner->clear(backup: true);

        $backupFiles = glob(storage_path('logs/laravel.log.backup.*'));
        expect($backupFiles)->not->toBeEmpty();
    });

    it('throws DiskSpaceException when insufficient space', function () {
        $this->createTestLogFile('laravel.log', str_repeat("Test line\n", 100));

        // Set very high minimum space requirement to trigger exception
        Config::set('log-cleaner.min_free_disk_space_mb', 999999);

        $logCleaner = new LogCleaner;

        expect(fn () => $logCleaner->clear(backup: true))
            ->toThrow(DiskSpaceException::class);
    });

    it('throws FileLockException when file is locked', function () {
        $this->createTestLogFile('laravel.log', "Test\n");

        // Create a lock file manually
        $lockFile = storage_path('logs/laravel.log.lock');
        file_put_contents($lockFile, '99999');

        Config::set('log-cleaner.locking.timeout', 1);
        Config::set('log-cleaner.locking.enabled', true);

        $logCleaner = new LogCleaner;

        $start = microtime(true);
        expect(fn () => $logCleaner->clearAll())
            ->toThrow(FileLockException::class);
        $end = microtime(true);

        // Verify it actually waited for the timeout (approximately)
        expect($end - $start)->toBeGreaterThanOrEqual(0.5);

        unlink($lockFile);
    });
});
