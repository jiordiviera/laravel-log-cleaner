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

it('reports lines removed before truncating the file', function () {
    $this->createTestLogFile('laravel.log', "First\nSecond\n");

    $cleaner = new LogCleaner;
    $cleaner->clearAll();

    expect($cleaner->getCleaningResults()[0]['lines_removed'])->toBe(2);
    $this->assertLogFileIsEmpty();
});

it('counts duplicate removed lines in the standard path', function () {
    $old = now()->subDays(10)->format('Y-m-d');
    $new = now()->format('Y-m-d');
    $this->createTestLogFile('laravel.log', "[{$old}] INFO: repeated\n[{$old}] INFO: repeated\n[{$new}] INFO: recent\n");

    $cleaner = new LogCleaner;
    $cleaner->clear(days: 5, memoryEfficient: false);

    expect($cleaner->getCleaningResults()[0]['lines_removed'])->toBe(2);
    $this->assertLogFileContains('recent');
});

it('does not create a temporary file during streaming dry-run', function () {
    $old = now()->subDays(10)->format('Y-m-d');
    $content = "[{$old}] INFO: old\n";
    $this->createTestLogFile('laravel.log', $content);

    $cleaner = new LogCleaner;
    $cleaner->clear(days: 5, memoryEfficient: true, dryRun: true);

    expect(file_exists(storage_path('logs/laravel.log.tmp')))->toBeFalse();
    expect($this->getLogContent())->toBe($content);
    expect($cleaner->getCleaningResults()[0]['lines_removed'])->toBe(1);
});
