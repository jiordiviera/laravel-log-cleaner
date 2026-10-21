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

it('keeps undated preamble and never attaches it to an expired record', function (bool $streaming) {
    $old = now()->subDays(10)->format('Y-m-d');
    $recent = now()->format('Y-m-d');
    $preamble = "Malformed entry without a date\nunknown traceback line\n";
    $expired = "[{$old}] production.ERROR: expired\n#0 old trace\n";
    $kept = "[{$recent}] production.INFO: current\n#0 current trace\n";
    $this->createTestLogFile('laravel.log', $preamble.$expired.$kept);

    (new LogCleaner)->clear(days: 5, memoryEfficient: $streaming);

    expect($this->getLogContent())->toBe($preamble.$kept);
})->with([false, true]);

it('treats malformed undated lines inside an old exception as continuations', function (bool $streaming) {
    $old = now()->subDays(10)->format('Y-m-d');
    $recent = now()->format('Y-m-d');
    $expired = "[{$old}] production.ERROR: expired\n"
        ."malformed not-a-timestamp ERROR message\n"
        ."#0 /app/old.php\n"
        ."Caused by: nested\n";
    $kept = "[{$recent}] production.INFO: current\n";
    $this->createTestLogFile('laravel.log', $expired.$kept);

    $cleaner = new LogCleaner;
    $cleaner->clear(days: 5, memoryEfficient: $streaming);

    expect($this->getLogContent())->toBe($kept)
        ->and($cleaner->getCleaningResults()[0]['lines_removed'])->toBe(4);
})->with([false, true]);

it('preserves recent multiline entries even when the final line has no newline', function (bool $streaming) {
    $old = now()->subDays(10)->format('Y-m-d');
    $recent = now()->format('Y-m-d');
    $expired = "[{$old}] production.ERROR: expired\n#0 old trace\n";
    $kept = "[{$recent}] production.ERROR: current\n#0 /app/current.php (unfinished)";
    $this->createTestLogFile('laravel.log', $expired.$kept);

    (new LogCleaner)->clear(days: 5, memoryEfficient: $streaming);

    expect($this->getLogContent())->toBe($kept);
})->with([false, true]);
