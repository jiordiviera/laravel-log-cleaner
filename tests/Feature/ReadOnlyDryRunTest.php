<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use JiordiViera\LaravelLogCleaner\Events\LogCleaning;
use JiordiViera\LaravelLogCleaner\LogCleaner;
use JiordiViera\LaravelLogCleaner\Tests\CreatesLogFiles;

uses(CreatesLogFiles::class);

beforeEach(function () {
    $this->cleanupLogFiles();
    Config::set('log-cleaner.events.enabled', true);
    Config::set('log-cleaner.locking.enabled', true);
});

afterEach(function () {
    $this->cleanupLogFiles();
});

it('never creates temp, lock, backup or archive files during a streaming dry run', function () {
    $old = now()->subDays(10)->format('Y-m-d');
    $content = str_repeat("[{$old}] production.ERROR: old entry\nStack trace:\n", 10000);
    $this->createTestLogFile('laravel.log', $content);
    $directory = storage_path('logs');
    $before = scandir($directory);

    (new LogCleaner)->clear(days: 5, backup: true, compress: true, memoryEfficient: true, dryRun: true);

    expect(file_get_contents($directory.'/laravel.log'))->toBe($content)
        ->and(scandir($directory))->toBe($before);
});

it('does not dispatch cleaning events during a dry run', function () {
    Event::fake();
    $this->createTestLogFile('laravel.log', "[2020-01-01] production.INFO: old\n");

    (new LogCleaner)->clear(days: 5, dryRun: true);

    Event::assertNotDispatched(LogCleaning::class);
});

it('accepts readable logs without requiring write permission in dry-run mode', function () {
    $this->createTestLogFile('laravel.log', "[2020-01-01] production.INFO: old\n");
    $path = storage_path('logs/laravel.log');
    chmod($path, 0444);

    try {
        (new LogCleaner)->clear(days: 5, memoryEfficient: true, dryRun: true);
        expect(file_get_contents($path))->toContain('old');
    } finally {
        chmod($path, 0644);
    }
});
