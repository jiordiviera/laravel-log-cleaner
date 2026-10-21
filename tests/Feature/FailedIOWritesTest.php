<?php

declare(strict_types=1);

use JiordiViera\LaravelLogCleaner\LogFileOperations;
use JiordiViera\LaravelLogCleaner\Tests\CreatesLogFiles;

uses(CreatesLogFiles::class);

beforeEach(function () {
    $this->cleanupLogFiles();
});

afterEach(function () {
    $this->cleanupLogFiles();
});

it('rejects a failed plain write without changing an existing log', function () {
    $original = "important original log\n";
    $path = $this->createTestLogFile('laravel.log', $original);
    $stream = fopen('php://temp', 'w+b');

    try {
        fclose($stream);

        expect(fn () => (new LogFileOperations)->writeAll($stream, str_repeat('x', 4096)))
            ->toThrow(TypeError::class);
        expect(file_get_contents($path))->toBe($original);
    } finally {
        if (is_resource($stream)) {
            fclose($stream);
        }
    }
});

it('rejects writes to a compressed stream opened for reading', function () {
    $original = "important original log\n";
    $path = $this->createTestLogFile('laravel.log', $original);
    $archive = $path.'.fixture.gz';
    file_put_contents($archive, gzencode('archive fixture'));
    $handle = gzopen($archive, 'rb');

    try {
        expect(fn () => (new LogFileOperations)->writeCompressed($handle, str_repeat('x', 4096)))
            ->toThrow(RuntimeException::class);
        expect(file_get_contents($path))->toBe($original);
    } finally {
        gzclose($handle);
        unlink($archive);
    }
});

it('rejects disk-full writes when the Linux full device is available', function () {
    if (! is_writable('/dev/full')) {
        test()->markTestSkipped('/dev/full is unavailable on this runner.');
    }

    $original = "important original log\n";
    $path = $this->createTestLogFile('laravel.log', $original);
    $handle = fopen('/dev/full', 'wb');

    try {
        expect(fn () => (new LogFileOperations)->writeAll($handle, str_repeat('x', 65536)))
            ->toThrow(RuntimeException::class);
        expect(file_get_contents($path))->toBe($original);
    } finally {
        fclose($handle);
    }
});
