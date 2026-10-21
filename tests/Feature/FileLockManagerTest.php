<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Config;
use JiordiViera\LaravelLogCleaner\Exceptions\FileLockException;
use JiordiViera\LaravelLogCleaner\FileLockManager;
use JiordiViera\LaravelLogCleaner\Tests\CreatesLogFiles;

uses(CreatesLogFiles::class);

beforeEach(function () {
    $this->cleanupLogFiles();
    Config::set('log-cleaner.locking.timeout', 0);
});

afterEach(function () {
    $this->cleanupLogFiles();
});

it('prevents a second manager from acquiring a held file lock', function () {
    $path = $this->createTestLogFile('laravel.log', "original\n");
    $first = new FileLockManager;
    $second = new FileLockManager;

    $first->acquire($path);
    try {
        expect(fn () => $second->acquire($path))->toThrow(FileLockException::class);
        expect(file_get_contents($path))->toBe("original\n");
    } finally {
        $first->release($path);
    }

    $second->acquire($path);
    $second->release($path);
});

it('can safely release a file lock more than once', function () {
    $path = $this->createTestLogFile('laravel.log', "original\n");
    $manager = new FileLockManager;
    $manager->acquire($path);
    $manager->release($path);
    $manager->release($path);

    $other = new FileLockManager;
    $other->acquire($path);
    $other->release($path);
    expect(file_get_contents($path))->toBe("original\n");
});
