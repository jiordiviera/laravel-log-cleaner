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

it('atomically replaces the log content and removes staging files', function () {
    $path = $this->createTestLogFile('laravel.log', "before\n");
    $before = glob($path.'.tmp.*');

    (new LogFileOperations)->replaceContent($path, "after\n");

    expect(file_get_contents($path))->toBe("after\n")
        ->and(glob($path.'.tmp.*'))->toBe($before);
});

it('detects a concurrent append before replacing a log', function () {
    $path = $this->createTestLogFile('laravel.log', "original\n");
    clearstatcache(true, $path);
    $snapshot = stat($path);

    file_put_contents($path, "append\n", FILE_APPEND);

    expect(fn () => (new LogFileOperations)->assertUnchanged($path, $snapshot))
        ->toThrow(RuntimeException::class, 'Log file changed during cleanup');
    expect(file_get_contents($path))->toBe("original\nappend\n");
});

it('writes every byte of a log buffer', function () {
    $stream = fopen('php://temp', 'w+b');
    try {
        $content = str_repeat("some log entry\n", 4096);
        (new LogFileOperations)->writeAll($stream, $content);
        rewind($stream);
        expect(stream_get_contents($stream))->toBe($content);
    } finally {
        fclose($stream);
    }
});
