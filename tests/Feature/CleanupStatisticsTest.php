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

it('counts duplicate log entries by occurrence and includes CRLF bytes', function (bool $streaming) {
    $old = now()->subDays(10)->format('Y-m-d');
    $recent = now()->format('Y-m-d');
    $removed = "[{$old}] production.ERROR: repeated\r\n";
    $kept = "[{$recent}] production.INFO: retained\r\n";
    $this->createTestLogFile('laravel.log', $removed.$removed.$kept);

    $cleaner = new LogCleaner;
    $cleaner->clear(days: 5, memoryEfficient: $streaming);

    $result = $cleaner->getCleaningResults()[0];
    expect($result['lines_removed'])->toBe(2)
        ->and($result['bytes_freed'])->toBe(strlen($removed) * 2)
        ->and($this->getLogContent())->toBe($kept);
})->with([false, true]);

it('reports zero removed lines and bytes for an empty log', function (bool $streaming) {
    $this->createTestLogFile('laravel.log', '');

    $cleaner = new LogCleaner;
    $cleaner->clear(days: 5, memoryEfficient: $streaming);

    $result = $cleaner->getCleaningResults()[0];
    expect($result['lines_removed'])->toBe(0)
        ->and($result['bytes_freed'])->toBe(0);
})->with([false, true]);

it('estimates duplicate removed lines accurately without modifying the log', function () {
    $old = now()->subDays(10)->format('Y-m-d');
    $content = "[{$old}] production.ERROR: repeated\n";
    $this->createTestLogFile('laravel.log', $content.$content);

    $result = (new LogCleaner)->dryRun(days: 5);

    expect($result[0]['removed_lines'])->toBe(2)
        ->and($this->getLogContent())->toBe($content.$content);
});
