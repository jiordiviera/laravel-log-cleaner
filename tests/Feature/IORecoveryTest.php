<?php

declare(strict_types=1);

use JiordiViera\LaravelLogCleaner\LogCleaner;
use JiordiViera\LaravelLogCleaner\Tests\CreatesLogFiles;

uses(CreatesLogFiles::class);

beforeEach(function () {
    $this->cleanupLogFiles();
});

afterEach(function () {
    $this->cleanupLogFiles();
});

it('preserves the original log if compression cannot be started in streaming mode', function () {
    $old = now()->subDays(10)->format('Y-m-d');
    $content = "[{$old}] production.ERROR: old entry\n";
    $this->createTestLogFile('laravel.log', $content);
    $path = storage_path('logs/laravel.log');

    // An existing directory at the archive destination makes gzopen fail.
    $archivePath = $path.'.old.'.date('Y-m-d-H-i-s').'.gz';
    mkdir($archivePath);

    try {
        expect(fn () => (new LogCleaner)->clear(days: 5, compress: true, memoryEfficient: true))
            ->toThrow(\\RuntimeException::class);

        expect(file_get_contents($path))->toBe($content)
            ->and(glob($path.'.tmp.*'))->toBeEmpty()
            ->and(is_dir($archivePath))->toBeTrue();
    } finally {
        rmdir($archivePath);
    }
});

it('preserves the original log if compression cannot be started in standard mode', function () {
    $old = now()->subDays(10)->format('Y-m-d');
    $content = "[{$old}] production.ERROR: old entry\n";
    $this->createTestLogFile('laravel.log', $content);
    $path = storage_path('logs/laravel.log');
    $archivePath = $path.'.old.'.date('Y-m-d-H-i-s').'.gz';
    mkdir($archivePath);

    try {
        expect(fn () => (new LogCleaner)->clear(days: 5, compress: true))
            ->toThrow(\\Throwable::class);

        expect(file_get_contents($path))->toBe($content)
            ->and(glob($path.'.tmp.*'))->toBeEmpty();
    } finally {
        rmdir($archivePath);
    }
});

it('does not leave a compressed archive when there are no expired records', function () {
    $recent = now()->format('Y-m-d');
    $content = "[{$recent}] production.INFO: recent\n";
    $this->createTestLogFile('laravel.log', $content);
    $path = storage_path('logs/laravel.log');

    (new LogCleaner)->clear(days: 5, compress: true, memoryEfficient: true);

    expect(file_get_contents($path))->toBe($content)
        ->and(glob($path.'.old.*.gz'))->toBeEmpty()
        ->and(glob($path.'.tmp.*'))->toBeEmpty();
});
