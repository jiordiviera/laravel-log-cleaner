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

it('retains every recent record when processing a large log with repeated old entries', function (bool $streaming) {
    $old = now()->subDays(10)->format('Y-m-d');
    $recent = now()->format('Y-m-d');
    $oldEntry = "[{$old}] production.ERROR: repeated old entry\n";
    $recentEntry = "[{$recent}] production.INFO: keep recent entry\n";

    // Large enough to exercise substantial streaming I/O without depending on disk size.
    $content = str_repeat($oldEntry, 20000).str_repeat($recentEntry, 100);
    $this->createTestLogFile('laravel.log', $content);

    $cleaner = new LogCleaner;
    $cleaner->clear(days: 5, memoryEfficient: $streaming);

    expect($this->getLogContent())->toBe(str_repeat($recentEntry, 100))
        ->and($cleaner->getCleaningResults()[0]['lines_removed'])->toBe(20000)
        ->and($cleaner->getCleaningResults()[0]['bytes_freed'])->toBe(strlen($oldEntry) * 20000);
})->with([false, true]);

it('preserves duplicated multiline records exactly in both cleanup paths', function (bool $streaming) {
    $old = now()->subDays(10)->format('Y-m-d');
    $recent = now()->format('Y-m-d');
    $expired = "[{$old}] production.ERROR: same old exception\nStack trace:\n#0 /app/old.php\n";
    $retained = "[{$recent}] production.ERROR: same recent exception\nStack trace:\n#0 /app/new.php\n";
    $content = $expired.$expired.$retained.$retained;
    $this->createTestLogFile('laravel.log', $content);

    $cleaner = new LogCleaner;
    $cleaner->clear(days: 5, memoryEfficient: $streaming);

    expect($this->getLogContent())->toBe($retained.$retained)
        ->and($cleaner->getCleaningResults()[0]['lines_removed'])->toBe(6)
        ->and($cleaner->getCleaningResults()[0]['bytes_freed'])->toBe(strlen($expired) * 2);
})->with([false, true]);

it('keeps log bytes and directory entries unchanged during dry run', function (bool $streaming) {
    $old = now()->subDays(10)->format('Y-m-d');
    $recent = now()->format('Y-m-d');
    $content = "[{$old}] production.ERROR: old\ncontinuation\n[{$recent}] production.INFO: recent\n";
    $path = $this->createTestLogFile('laravel.log', $content);
    $directory = dirname($path);
    $filesBefore = scandir($directory);

    $cleaner = new LogCleaner;
    $cleaner->clear(days: 5, memoryEfficient: $streaming, dryRun: true);

    expect(file_get_contents($path))->toBe($content)
        ->and(scandir($directory))->toBe($filesBefore)
        ->and($cleaner->getCleaningResults()[0]['lines_removed'])->toBe(2);
})->with([false, true]);
