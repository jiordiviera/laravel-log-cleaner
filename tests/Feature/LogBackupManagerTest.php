<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Config;
use JiordiViera\LaravelLogCleaner\Exceptions\DiskSpaceException;
use JiordiViera\LaravelLogCleaner\LogBackupManager;
use JiordiViera\LaravelLogCleaner\Tests\CreatesLogFiles;

uses(CreatesLogFiles::class);

beforeEach(function () {
    $this->cleanupLogFiles();
});

afterEach(function () {
    $this->cleanupLogFiles();
});

it('creates a backup containing the original bytes', function () {
    $path = $this->createTestLogFile('laravel.log', "first\nsecond\n");
    $manager = new LogBackupManager;
    $backup = $manager->createBackup(new SplFileInfo($path));

    expect(file_get_contents($backup))->toBe("first\nsecond\n")
        ->and(file_get_contents($path))->toBe("first\nsecond\n");
});

it('keeps the configured number of most recent backups', function () {
    $path = $this->createTestLogFile('laravel.log', "untouched\n");
    Config::set('log-cleaner.backup.max_backups', 2);
    Config::set('log-cleaner.backup.auto_cleanup', true);

    foreach (['2026-01-01-00-00-00', '2026-01-02-00-00-00', '2026-01-03-00-00-00'] as $stamp) {
        file_put_contents($path.'.backup.'.$stamp, $stamp);
    }

    (new LogBackupManager)->cleanupOldBackups(new SplFileInfo($path));

    expect(glob($path.'.backup.*'))->toHaveCount(2)
        ->and(file_exists($path.'.backup.2026-01-01-00-00-00'))->toBeFalse()
        ->and(file_get_contents($path))->toBe("untouched\n");
});

it('does not prune existing backups when automatic retention is disabled', function () {
    $path = $this->createTestLogFile('laravel.log', "untouched\n");
    Config::set('log-cleaner.backup.max_backups', 1);
    Config::set('log-cleaner.backup.auto_cleanup', false);

    foreach (['2026-01-01-00-00-00', '2026-01-02-00-00-00'] as $stamp) {
        file_put_contents($path.'.backup.'.$stamp, $stamp);
    }

    (new LogBackupManager)->cleanupOldBackups(new SplFileInfo($path));

    expect(glob($path.'.backup.*'))->toHaveCount(2);
});

it('checks the minimum free disk space when backup safety is enabled', function () {
    $path = $this->createTestLogFile('laravel.log', "original\n");
    Config::set('log-cleaner.min_free_disk_space_mb', PHP_INT_MAX);

    expect(fn () => (new LogBackupManager)->validateDiskSpace($path))
        ->toThrow(DiskSpaceException::class);
});
