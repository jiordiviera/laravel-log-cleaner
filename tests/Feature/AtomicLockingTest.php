<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Config;
use JiordiViera\LaravelLogCleaner\LogCleaner;
use JiordiViera\LaravelLogCleaner\Tests\CreatesLogFiles;

uses(CreatesLogFiles::class);

beforeEach(function () {
    $this->cleanupLogFiles();
    Config::set('log-cleaner.locking.enabled', true);
    Config::set('log-cleaner.locking.timeout', 0);
});

afterEach(function () {
    $this->cleanupLogFiles();
});

it('rejects a second cleaner while a cooperative lock is held', function () {
    $this->createTestLogFile('laravel.log', "old log\n");
    $path = storage_path('logs/laravel.log');
    $handle = fopen($path.'.lock', 'c');
    expect($handle)->not->toBeFalse();
    expect(flock($handle, LOCK_EX | LOCK_NB))->toBeTrue();

    try {
        expect(fn () => (new LogCleaner)->clearAll())->toThrow(\JiordiViera\LaravelLogCleaner\Exceptions\FileLockException::class);
        expect(file_get_contents($path))->toBe("old log\n");
    } finally {
        flock($handle, LOCK_UN);
        fclose($handle);
    }
});

it('releases its lock when cleaning throws', function () {
    $this->createTestLogFile('laravel.log', "old log\n");
    $path = storage_path('logs/laravel.log');
    $cleaner = new LogCleaner;

    $reflection = new ReflectionMethod($cleaner, 'acquireLock');
    $reflection->invoke($cleaner, $path);

    $second = fopen($path.'.lock', 'c');
    expect($second)->not->toBeFalse();

    try {
        expect(flock($second, LOCK_EX | LOCK_NB))->toBeFalse();
    } finally {
        (new ReflectionMethod($cleaner, 'releaseLock'))->invoke($cleaner, $path);
    }

    try {
        expect(flock($second, LOCK_EX | LOCK_NB))->toBeTrue();
    } finally {
        flock($second, LOCK_UN);
        fclose($second);
    }
});
