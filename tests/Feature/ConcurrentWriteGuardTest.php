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

it('rejects a log file changed since the streaming snapshot', function () {
    $path = storage_path('logs/laravel.log');
    $this->createTestLogFile('laravel.log', "Original\n");
    clearstatcache(true, $path);
    $snapshot = stat($path);

    file_put_contents($path, "Concurrent append\n", FILE_APPEND);

    $method = new ReflectionMethod(LogCleaner::class, 'assertLogFileUnchanged');
    expect(fn () => $method->invoke(new LogCleaner, $path, $snapshot))
        ->toThrow(RuntimeException::class, 'Log file changed during cleanup');

    expect(file_get_contents($path))->toContain('Concurrent append');
});

it('accepts an unchanged log file snapshot', function () {
    $path = storage_path('logs/laravel.log');
    $this->createTestLogFile('laravel.log', "Original\n");
    clearstatcache(true, $path);
    $snapshot = stat($path);

    $method = new ReflectionMethod(LogCleaner::class, 'assertLogFileUnchanged');
    expect($method->invoke(new LogCleaner, $path, $snapshot))->toBeNull();
});
