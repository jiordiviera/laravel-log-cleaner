<?php

use Carbon\Carbon;
use Illuminate\Support\Facades\File;
use function Pest\Laravel\artisan;

const LOG_PATH = 'logs/laravel.log';
const OLD_LOG_MESSAGE = '[2023-01-01 12:00:00] test.ERROR: Old log message';
const RECENT_LOG_MESSAGE = '[%s 12:00:00] test.INFO: Recent log message';

beforeEach(function () {
	$this->logPath = storage_path(LOG_PATH);
	File::delete($this->logPath);
});

afterEach(function () {
//    File::delete($this->logPath);
});

it('clears the entire log file', function () {
	// Arrange
	File::put($this->logPath, 'Log file content');
	$this->artisan('log:clear')->expectsOutput('Log file cleared successfully')->assertExitCode(0);
	// Act
	expect(File::get($this->logPath))->toBe('');
});

it('warns if the log file does not exist', function () {
	// Act
	$result = artisan('log:clear');

	// Assert
	$result->expectsOutput('No log file found at '.$this->logPath)->assertFailed();
});

it('clears logs older than specified days', function () {
	// Arrange
	$recentDate = Carbon::now()->format('Y-m-d');
	$recentLog = sprintf(RECENT_LOG_MESSAGE, $recentDate);
	$content = OLD_LOG_MESSAGE.PHP_EOL.$recentLog;
	File::put($this->logPath, $content);

	$this->artisan('log:clear --days=30')
		->expectsOutput('Logs older than 30 days have been removed')
		->assertOk();

	$newContent = File::get($this->logPath);
	expect($newContent)->not->toContain(OLD_LOG_MESSAGE)->toContain($recentLog);
});

it('handles invalid days parameter', function () {

	$recentDate = Carbon::now()->format('Y-m-d');
	$recentLog = sprintf(RECENT_LOG_MESSAGE, $recentDate);
	$content = OLD_LOG_MESSAGE.PHP_EOL.$recentLog;
	File::put($this->logPath, $content);

	$this->artisan('log:clear --days=-1')
		->expectsOutput('Days must be a positive integer')
		->assertFailed();
});

it('keeps all logs if all are within the specified days', function () {
	// Arrange
	$recentDate = Carbon::now()->subDays(5)->format('Y-m-d');
	$recentLog = sprintf(RECENT_LOG_MESSAGE, $recentDate);
	File::put($this->logPath, $recentLog);

	// Act
	$this->artisan('log:clear --days=10')
		->expectsOutput('Logs older than 10 days have been removed')
		->assertSuccessful();

	expect(File::get($this->logPath))->toBe($recentLog);
});
