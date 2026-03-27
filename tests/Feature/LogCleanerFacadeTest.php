<?php

use JiordiViera\LaravelLogCleaner\LogCleanerFacade as LogCleaner;
use JiordiViera\LaravelLogCleaner\Tests\CreatesLogFiles;

uses(CreatesLogFiles::class);

describe('LogCleaner Facade', function () {
    beforeEach(function () {
        $this->cleanupLogFiles();
    });

    afterEach(function () {
        $this->cleanupLogFiles();
    });

    test('facade can clear all logs', function () {
        $this->createTestLogFile('laravel.log', "Test log entry\nAnother log entry\n");

        LogCleaner::clearAll();

        $this->assertLogFileIsEmpty();
    });

    test('facade can clear old logs', function () {
        $entries = $this->createMixedAgeLogEntries(10, 3, 3);
        $this->createLogFileWithEntries($entries);

        LogCleaner::clearOld(5);

        $this->assertLogFileDoesNotContain('Old log entry');
        $this->assertLogFileContains('New log entry');
    });

    test('facade can clear specific log file', function () {
        $this->createMultipleLogFiles(['laravel.log', 'other.log']);
        $this->createTestLogFile('other.log', "Other log content\n");

        LogCleaner::clearAll('laravel.log');

        $this->assertLogFileIsEmpty('laravel.log');
        $this->assertLogFileContains('Other log content', 'other.log');
    });

    test('facade can clear with backup', function () {
        $this->createTestLogFile('laravel.log', "Test log entry\n");

        LogCleaner::clearWithBackup();

        expect(glob(storage_path('logs/laravel.log.backup.*')))->not->toBeEmpty();
    });

    test('facade can clear with compression', function () {
        $entries = $this->createOldLogEntries(10, 5);
        $this->createLogFileWithEntries($entries);

        LogCleaner::clearWithCompression(5);

        expect(glob(storage_path('logs/laravel.log.old.*.gz')))->not->toBeEmpty();
    });

    test('facade clear method with all options', function () {
        $entries = $this->createMixedAgeLogEntries(10, 5, 5);
        $this->createLogFileWithEntries($entries);

        LogCleaner::clear(
            days: 5,
            backup: true,
            compress: false,
            level: null,
            pattern: null,
            memoryEfficient: false
        );

        $this->assertLogFileDoesNotContain('Old log entry');
        $this->assertLogFileContains('New log entry');
        expect(glob(storage_path('logs/laravel.log.backup.*')))->not->toBeEmpty();
    });
});
