<?php

use Carbon\Carbon;
use Illuminate\Support\Facades\File;
use function Pest\Laravel\artisan;

const LOG_DIRECTORY = 'logs';
const OLD_LOG_MESSAGE = '[2023-01-01 12:00:00] test.ERROR: Old log message';
const RECENT_LOG_MESSAGE = '[%s 12:00:00] test.INFO: Recent log message';

beforeEach(function () {
    $this->logDirectory = storage_path(LOG_DIRECTORY);
    File::ensureDirectoryExists($this->logDirectory);
    File::delete(File::files($this->logDirectory));
});

afterEach(function () {
    File::delete(File::files($this->logDirectory));
});

// Test clearing all log files
it('clears all log files', function () {
    // Arrange
    $filePaths = [
        $this->logDirectory . '/laravel.log',
        $this->logDirectory . '/app.log',
    ];
    foreach ($filePaths as $filePath) {
        File::put($filePath, 'Log file content');
    }

    // Act
    artisan('log:clear')
        ->assertExitCode(0);

    // Assert
    foreach ($filePaths as $filePath) {
        expect(File::get($filePath))->toBe('');
    }
});

// Test warning if no log files exist
it('warns if no log files exist', function () {
    // Act
    $result = artisan('log:clear');

    // Assert
    $result->expectsOutput('No log files found in ' . $this->logDirectory)->assertExitCode(1);
});

// Test clearing logs older than specified days across files
it('clears logs older than specified days across files', function () {
    // Arrange
    $recentDate = Carbon::now()->format('Y-m-d');
    $recentLog = sprintf(RECENT_LOG_MESSAGE, $recentDate);
    $content = OLD_LOG_MESSAGE . PHP_EOL . $recentLog;
    $filePaths = [
        $this->logDirectory . '/laravel.log',
        $this->logDirectory . '/app.log',
    ];
    foreach ($filePaths as $filePath) {
        File::put($filePath, $content);
    }

    // Act
    artisan('log:clear --days=30')
        ->assertExitCode(0);

    // Assert
    foreach ($filePaths as $filePath) {
        expect(File::get($filePath))->not->toContain(OLD_LOG_MESSAGE)->toContain($recentLog);
    }
});

// Test invalid days parameter across files
it('handles invalid days parameter across files', function () {
    // Arrange
    $recentDate = Carbon::now()->format('Y-m-d');
    $recentLog = sprintf(RECENT_LOG_MESSAGE, $recentDate);
    $content = OLD_LOG_MESSAGE . PHP_EOL . $recentLog;
    $filePaths = [
        $this->logDirectory . '/laravel.log',
        $this->logDirectory . '/app.log',
    ];
    foreach ($filePaths as $filePath) {
        File::put($filePath, $content);
    }

    // Act
    artisan('log:clear --days=-1')
        ->expectsOutput('Days must be a positive integer')
        ->assertExitCode(1);
});

// Test keeping logs when all are within the specified days across files
it('keeps all logs if all are within the specified days across files', function () {
    // Arrange
    $recentDate = Carbon::now()->subDays(5)->format('Y-m-d');
    $recentLog = sprintf(RECENT_LOG_MESSAGE, $recentDate);
    $filePaths = [
        $this->logDirectory . '/laravel.log',
        $this->logDirectory . '/app.log',
    ];
    foreach ($filePaths as $filePath) {
        File::put($filePath, $recentLog);
    }

    // Act
    artisan('log:clear --days=10')
        ->assertExitCode(0);

    // Assert
    foreach ($filePaths as $filePath) {
        expect(File::get($filePath))->toBe($recentLog);
    }
});