<?php

use JiordiViera\LaravelLogCleaner\LogCleanerFacade as LogCleaner;
use Illuminate\Support\Facades\Storage;

test('facade can clear all logs', function () {
    // Create a test log file
    $logPath = storage_path('logs/laravel.log');
    $testContent = "Test log entry\nAnother log entry\n";
    file_put_contents($logPath, $testContent);

    // Use facade to clear all logs
    LogCleaner::clearAll();

    // Assert log file is empty
    expect(file_get_contents($logPath))->toBe('');
});

test('facade can clear old logs', function () {
    // Create a test log file with old and new entries
    $logPath = storage_path('logs/laravel.log');
    $oldDate = now()->subDays(10)->format('Y-m-d');
    $newDate = now()->format('Y-m-d');
    $testContent = "[{$oldDate}] Old log entry\n[{$newDate}] New log entry\n";
    file_put_contents($logPath, $testContent);

    // Use facade to clear logs older than 5 days
    LogCleaner::clearOld(5);

    // Assert only new log remains
    $remainingContent = file_get_contents($logPath);
    expect($remainingContent)->toContain("[{$newDate}] New log entry");
    expect($remainingContent)->not->toContain("[{$oldDate}] Old log entry");
});

test('facade can clear specific log file', function () {
    // Create multiple log files
    $laravelLog = storage_path('logs/laravel.log');
    $otherLog = storage_path('logs/other.log');
    file_put_contents($laravelLog, "Laravel log content\n");
    file_put_contents($otherLog, "Other log content\n");

    // Clear only laravel.log
    LogCleaner::clearAll('laravel.log');

    // Assert laravel.log is empty, other.log unchanged
    expect(file_get_contents($laravelLog))->toBe('');
    expect(file_get_contents($otherLog))->toBe("Other log content\n");
});