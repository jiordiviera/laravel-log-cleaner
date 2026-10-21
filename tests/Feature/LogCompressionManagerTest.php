<?php

declare(strict_types=1);

use JiordiViera\LaravelLogCleaner\LogCompressionManager;
use JiordiViera\LaravelLogCleaner\Tests\CreatesLogFiles;

uses(CreatesLogFiles::class);

beforeEach(function () {
    $this->cleanupLogFiles();
});

afterEach(function () {
    $this->cleanupLogFiles();
});

it('creates a readable gzip archive containing all removed entries', function () {
    $path = $this->createTestLogFile('laravel.log', "original\n");
    $manager = new LogCompressionManager;
    $archive = $manager->compressOldLogs($path, ["expired line 1\n", "expired line 2\n"]);

    try {
        expect($archive)->not->toBeNull()
            ->and(is_file($archive))->toBeTrue()
            ->and(gzdecode(file_get_contents($archive)))->toBe("expired line 1\nexpired line 2\n")
            ->and(file_get_contents($path))->toBe("original\n");
    } finally {
        if ($archive !== null && is_file($archive)) {
            unlink($archive);
        }
    }
});

it('does not create an archive if there are no removed entries', function () {
    $path = $this->createTestLogFile('laravel.log', "original\n");

    expect((new LogCompressionManager)->compressOldLogs($path, []))->toBeNull()
        ->and(glob($path.'.old.*.gz'))->toBeEmpty()
        ->and(file_get_contents($path))->toBe("original\n");
});

it('preserves the log if the archive path cannot be opened', function () {
    $path = $this->createTestLogFile('laravel.log', "original\n");
    $archive = $path.'.old.'.date('Y-m-d-H-i-s').'.gz';
    mkdir($archive);

    try {
        expect(fn () => (new LogCompressionManager)->compressOldLogs($path, ["expired\n"]))
            ->toThrow(Throwable::class);
        expect(file_get_contents($path))->toBe("original\n")
            ->and(is_dir($archive))->toBeTrue();
    } finally {
        rmdir($archive);
    }
});
