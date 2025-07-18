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

// Test backup creation
it('creates backup when backup option is used', function () {
    // Arrange
    $filePath = $this->logDirectory . '/laravel.log';
    $content = 'Test log content';
    File::put($filePath, $content);

    // Act
    artisan('log:clear --backup')
        ->assertExitCode(0);

    // Assert
    $backupFiles = glob($filePath . '.backup.*');
    expect($backupFiles)->toHaveCount(1);
    expect(File::get($backupFiles[0]))->toBe($content);
});

// Test dry run mode
it('shows what would be removed in dry run mode', function () {
    // Arrange
    $recentDate = Carbon::now()->format('Y-m-d');
    $recentLog = sprintf(RECENT_LOG_MESSAGE, $recentDate);
    $content = OLD_LOG_MESSAGE . PHP_EOL . $recentLog;
    $filePath = $this->logDirectory . '/laravel.log';
    File::put($filePath, $content);

    // Act & Assert
    artisan('log:clear --days=30 --dry-run')
        ->expectsOutput('[DRY RUN] Would remove 1 lines from laravel.log')
        ->assertExitCode(0);

    // Verify file is unchanged
    expect(File::get($filePath))->toBe($content);
});

// Test memory efficient processing
it('processes large files with memory efficient mode', function () {
    // Arrange
    $recentDate = Carbon::now()->format('Y-m-d');
    $recentLog = sprintf(RECENT_LOG_MESSAGE, $recentDate);
    $content = OLD_LOG_MESSAGE . PHP_EOL . $recentLog;
    $filePath = $this->logDirectory . '/laravel.log';
    File::put($filePath, $content);

    // Act
    artisan('log:clear --days=30 --memory-efficient')
        ->assertExitCode(0);

    // Assert
    expect(File::get($filePath))->not->toContain(OLD_LOG_MESSAGE)->toContain($recentLog);
});

// Test custom pattern support
it('supports custom date patterns', function () {
    // Arrange
    $customLog = '2023-01-01 Custom log entry';
    $recentLog = Carbon::now()->format('Y-m-d') . ' Recent custom log';
    $content = $customLog . PHP_EOL . $recentLog;
    $filePath = $this->logDirectory . '/laravel.log';
    File::put($filePath, $content);

    // Act
    artisan('log:clear', ['--days' => 30, '--pattern' => '/^(\d{4}-\d{2}-\d{2})/'])
        ->assertExitCode(0);

    // Assert
    expect(File::get($filePath))->not->toContain($customLog)->toContain($recentLog);
});

// Test log level filtering
it('filters logs by level', function () {
    // Arrange
    $errorLog = '[' . Carbon::now()->format('Y-m-d') . ' 12:00:00] test.ERROR: Error message';
    $infoLog = '[' . Carbon::now()->format('Y-m-d') . ' 12:00:00] test.INFO: Info message';
    $content = $errorLog . PHP_EOL . $infoLog;
    $filePath = $this->logDirectory . '/laravel.log';
    File::put($filePath, $content);

    // Act - keep only ERROR logs
    artisan('log:clear --days=0 --level=ERROR')
        ->assertExitCode(0);

    // Assert
    expect(File::get($filePath))->toContain($errorLog)->not->toContain($infoLog);
});

// Test invalid log level
it('rejects invalid log levels', function () {
    // Arrange
    $filePath = $this->logDirectory . '/laravel.log';
    File::put($filePath, 'test content');

    // Act & Assert
    artisan('log:clear --level=INVALID')
        ->expectsOutput('Invalid log level. Must be one of: EMERGENCY, ALERT, CRITICAL, ERROR, WARNING, NOTICE, INFO, DEBUG')
        ->assertExitCode(1);
});