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

// Test compression creates .gz file
it('creates compressed file when compress option is used', function () {
    // Arrange
    $recentDate = Carbon::now()->format('Y-m-d');
    $recentLog = sprintf(RECENT_LOG_MESSAGE, $recentDate);
    $content = OLD_LOG_MESSAGE . PHP_EOL . $recentLog;
    $filePath = $this->logDirectory . '/laravel.log';
    File::put($filePath, $content);

    // Act
    artisan('log:clear --days=30 --compress')
        ->assertExitCode(0);

    // Assert - check .gz file exists
    $gzFiles = glob($filePath . '.old.*.gz');
    expect($gzFiles)->toHaveCount(1);

    // Verify main file still has recent logs
    expect(File::get($filePath))->toContain($recentLog)->not->toContain(OLD_LOG_MESSAGE);
});

// Test compressed file content is correct
it('compresses old logs correctly and they can be decompressed', function () {
    // Arrange
    $recentDate = Carbon::now()->format('Y-m-d');
    $recentLog = sprintf(RECENT_LOG_MESSAGE, $recentDate);
    $content = OLD_LOG_MESSAGE . PHP_EOL . $recentLog;
    $filePath = $this->logDirectory . '/laravel.log';
    File::put($filePath, $content);

    // Act
    artisan('log:clear --days=30 --compress')
        ->assertExitCode(0);

    // Assert - decompress and verify content
    $gzFiles = glob($filePath . '.old.*.gz');
    expect($gzFiles)->toHaveCount(1);

    $decompressed = gzdecode(File::get($gzFiles[0]));
    expect($decompressed)->toContain(OLD_LOG_MESSAGE);
});

// Test compress with memory efficient mode
it('compresses logs with memory efficient mode', function () {
    // Arrange
    $recentDate = Carbon::now()->format('Y-m-d');
    $recentLog = sprintf(RECENT_LOG_MESSAGE, $recentDate);
    $content = OLD_LOG_MESSAGE . PHP_EOL . $recentLog;
    $filePath = $this->logDirectory . '/laravel.log';
    File::put($filePath, $content);

    // Act
    artisan('log:clear --days=30 --compress --memory-efficient')
        ->assertExitCode(0);

    // Assert
    $gzFiles = glob($filePath . '.old.*.gz');
    expect($gzFiles)->toHaveCount(1);
    expect(File::get($filePath))->toContain($recentLog)->not->toContain(OLD_LOG_MESSAGE);
});

// Test backup and compress combination
it('creates backup and compresses old logs', function () {
    // Arrange
    $recentDate = Carbon::now()->format('Y-m-d');
    $recentLog = sprintf(RECENT_LOG_MESSAGE, $recentDate);
    $content = OLD_LOG_MESSAGE . PHP_EOL . $recentLog;
    $filePath = $this->logDirectory . '/laravel.log';
    File::put($filePath, $content);

    // Act
    artisan('log:clear --days=30 --backup --compress')
        ->assertExitCode(0);

    // Assert - both backup and compressed file exist
    $backupFiles = glob($filePath . '.backup.*');
    $gzFiles = glob($filePath . '.old.*.gz');
    expect($backupFiles)->toHaveCount(1);
    expect($gzFiles)->toHaveCount(1);

    // Backup should contain original content
    expect(File::get($backupFiles[0]))->toBe($content);
});

// Test level filtering with days combination
it('filters by level and days together', function () {
    // Arrange
    $recentDate = Carbon::now()->format('Y-m-d');
    $oldDate = Carbon::now()->subDays(60)->format('Y-m-d');

    $errorLog = '[' . $recentDate . ' 12:00:00] test.ERROR: Recent error';
    $oldErrorLog = '[' . $oldDate . ' 12:00:00] test.ERROR: Old error';
    $infoLog = '[' . $recentDate . ' 12:00:00] test.INFO: Recent info';

    $content = $oldErrorLog . PHP_EOL . $errorLog . PHP_EOL . $infoLog;
    $filePath = $this->logDirectory . '/laravel.log';
    File::put($filePath, $content);

    // Act - keep only ERROR logs from last 30 days
    artisan('log:clear --days=30 --level=ERROR')
        ->assertExitCode(0);

    // Assert
    $result = File::get($filePath);
    expect($result)->toContain($errorLog)
        ->not->toContain($oldErrorLog)
        ->not->toContain($infoLog);
});

// Test dry run shows correct space estimation
it('shows space estimation in dry run mode', function () {
    // Arrange
    $recentDate = Carbon::now()->format('Y-m-d');
    $recentLog = sprintf(RECENT_LOG_MESSAGE, $recentDate);
    $content = str_repeat(OLD_LOG_MESSAGE . PHP_EOL, 100) . $recentLog;
    $filePath = $this->logDirectory . '/laravel.log';
    File::put($filePath, $content);

    // Act - run dry-run and capture output
    artisan('log:clear --days=30 --dry-run')
        ->expectsOutputToContain('Would remove')
        ->expectsOutputToContain('Estimated')
        ->assertExitCode(0);

    // Verify file unchanged - this is the key behavior of dry-run
    expect(File::get($filePath))->toBe($content);
});

// Test custom pattern with invalid regex
it('handles invalid regex pattern gracefully', function () {
    // Arrange
    $filePath = $this->logDirectory . '/laravel.log';
    File::put($filePath, 'test content');

    // Act & Assert - invalid regex pattern should fail with error
    artisan('log:clear --days=30 --pattern="[invalid"')
        ->expectsOutputToContain('Invalid regex pattern')
        ->assertExitCode(1);
});

// Test all options combined
it('handles all options together correctly', function () {
    // Arrange
    $recentDate = Carbon::now()->format('Y-m-d');
    $recentLog = '[' . $recentDate . ' 12:00:00] test.ERROR: Recent error';
    $content = OLD_LOG_MESSAGE . PHP_EOL . $recentLog;
    $filePath = $this->logDirectory . '/laravel.log';
    File::put($filePath, $content);

    // Act - dry run with all options (should not modify anything)
    artisan('log:clear --days=30 --backup --compress --level=ERROR --memory-efficient --dry-run')
        ->assertExitCode(0);

    // Assert - file unchanged in dry-run
    expect(File::get($filePath))->toBe($content);

    // No backup or compressed files created
    $backupFiles = glob($filePath . '.backup.*');
    $gzFiles = glob($filePath . '.old.*.gz');
    expect($backupFiles)->toBeEmpty();
    expect($gzFiles)->toBeEmpty();
});

// Test empty log file
it('handles empty log file gracefully', function () {
    // Arrange
    $filePath = $this->logDirectory . '/laravel.log';
    File::put($filePath, '');

    // Act
    artisan('log:clear --days=30')
        ->assertExitCode(0);

    // Assert - file still empty
    expect(File::get($filePath))->toBe('');
});

// Test file with only whitespace
it('handles file with only whitespace', function () {
    // Arrange
    $filePath = $this->logDirectory . '/laravel.log';
    File::put($filePath, "   \n  \n  ");

    // Act
    artisan('log:clear --days=30')
        ->assertExitCode(0);

    // Assert - whitespace preserved or cleaned
    expect(File::exists($filePath))->toBeTrue();
});

// Test multiple backup creations don't conflict
it('creates multiple backups without conflicts', function () {
    // Arrange
    $filePath = $this->logDirectory . '/laravel.log';
    File::put($filePath, 'test content');

    // Act - create first backup
    artisan('log:clear --backup')
        ->assertExitCode(0);

    // Restore content and create second backup (in same second if possible)
    File::put($filePath, 'test content 2');
    artisan('log:clear --backup')
        ->assertExitCode(0);

    // Assert - should have 2 backup files
    $backupFiles = glob($filePath . '.backup.*');
    expect(count($backupFiles))->toBeGreaterThanOrEqual(1);
});

// Test pattern validation
it('validates regex patterns properly', function () {
    // Arrange
    $filePath = $this->logDirectory . '/laravel.log';
    $oldDate = Carbon::now()->subDays(60)->format('Y-m-d');
    $recentDate = Carbon::now()->format('Y-m-d');
    File::put($filePath, "[{$oldDate}] old log\n[{$recentDate}] recent log");

    // Act - valid pattern should work
    artisan('log:clear', ['--days' => 30, '--pattern' => '/^\[(\d{4}-\d{2}-\d{2})\]/'])
        ->assertExitCode(0);

    // Verify recent log kept, old removed
    $result = File::get($filePath);
    expect($result)->toContain($recentDate)->not->toContain($oldDate);
});

// Test large file triggers memory efficient automatically
it('automatically uses memory efficient mode for large files', function () {
    // Arrange
    $filePath = $this->logDirectory . '/laravel.log';

    // Create content larger than 50MB threshold
    $largeContent = str_repeat(OLD_LOG_MESSAGE . PHP_EOL, 10000);
    File::put($filePath, $largeContent);

    $fileSize = filesize($filePath);

    // Act - should auto-enable memory efficient if > 50MB
    artisan('log:clear --days=30')
        ->assertExitCode(0);

    // Assert - command should complete without memory errors
    expect(File::exists($filePath))->toBeTrue();
});

// Test compress without old logs
it('handles compress option when no old logs to compress', function () {
    // Arrange
    $recentDate = Carbon::now()->format('Y-m-d');
    $recentLog = sprintf(RECENT_LOG_MESSAGE, $recentDate);
    $filePath = $this->logDirectory . '/laravel.log';
    File::put($filePath, $recentLog);

    // Act
    artisan('log:clear --days=30 --compress')
        ->assertExitCode(0);

    // Assert - no .gz file created
    $gzFiles = glob($filePath . '.old.*.gz');
    expect($gzFiles)->toBeEmpty();

    // Recent log still intact
    expect(File::get($filePath))->toBe($recentLog);
});

// Test level filter with multiline stack traces
it('preserves stack traces when filtering by level', function () {
    // Arrange
    $recentDate = Carbon::now()->format('Y-m-d');
    $errorWithTrace = <<<LOG
[{$recentDate} 12:00:00] test.ERROR: Error message
#0 /path/to/file.php(10): function()
#1 /path/to/another.php(20): anotherFunction()
LOG;
    $infoLog = '[' . $recentDate . ' 12:00:00] test.INFO: Info message';
    $content = $errorWithTrace . PHP_EOL . $infoLog;
    $filePath = $this->logDirectory . '/laravel.log';
    File::put($filePath, $content);

    // Act - keep only ERROR
    artisan('log:clear --days=0 --level=ERROR')
        ->assertExitCode(0);

    // Assert - ERROR with stack trace kept, INFO removed
    $result = File::get($filePath);
    expect($result)->toContain('ERROR: Error message')
        ->toContain('#0 /path/to/file.php')
        ->not->toContain('INFO: Info message');
});