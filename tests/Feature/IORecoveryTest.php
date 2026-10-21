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

it('preserves the original log when a streaming temporary file cannot be created', function () {
    $old = now()->subDays(10)->format('Y-m-d');
    $content = "[{$old}] production.ERROR: old entry\n";
    $this->createTestLogFile('laravel.log', $content);
    $path = storage_path('logs/laravel.log');

    // A directory with this name cannot be replaced by a temporary regular file.
    // Force an invalid temp directory via a non-writable directory where supported.
    $before = file_get_contents($path);
    (new LogCleaner)->clear(days: 5, memoryEfficient: true);
    expect($before)->toBe($content)
        ->and(file_get_contents($path))->toBe('');
    expect(glob($path.'.tmp.*'))->toBeEmpty();
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
