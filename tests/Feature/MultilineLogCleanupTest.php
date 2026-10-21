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

it('removes old multiline records without retaining orphaned stack frames', function (bool $streaming) {
    $old = now()->subDays(10)->format('Y-m-d');
    $recent = now()->format('Y-m-d');
    $content = "[{$old}] production.ERROR: old exception\n"
        ."Stack trace:\n#0 /app/old.php(12): fail()\n"
        ."[{$recent}] production.INFO: recent event\n"
        ."Context: retained continuation\n";

    $this->createTestLogFile('laravel.log', $content);

    (new LogCleaner)->clear(days: 5, memoryEfficient: $streaming);

    expect($this->getLogContent())->not->toContain('old exception')
        ->not->toContain('Stack trace:')
        ->not->toContain('/app/old.php')
        ->toContain('recent event')
        ->toContain('Context: retained continuation');
})->with([false, true]);

it('preserves all continuation lines of a recent exception', function (bool $streaming) {
    $old = now()->subDays(10)->format('Y-m-d');
    $recent = now()->format('Y-m-d');
    $content = "[{$old}] production.INFO: old event\n"
        ."[{$recent}] production.ERROR: recent exception\n"
        ."Stack trace:\n#0 /app/recent.php(42): crash()\n#1 {main}\n";

    $this->createTestLogFile('laravel.log', $content);

    (new LogCleaner)->clear(days: 5, memoryEfficient: $streaming);

    expect($this->getLogContent())->toContain('recent exception')
        ->toContain('Stack trace:')
        ->toContain('/app/recent.php')
        ->toContain('#1 {main}')
        ->not->toContain('old event');
})->with([false, true]);
