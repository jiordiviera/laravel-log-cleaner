<?php

use Carbon\Carbon;
use Illuminate\Support\Facades\File;

it('clears the log file', function () {
    $logPath = storage_path('logs/laravel.log');
    File::put($logPath, 'Log content');
    expect(File::get($logPath))->toBe('Log content');

    $this->artisan('log:clear')
        ->expectsOutput('Log file cleared successfully')
        ->assertExitCode(0);

    expect(File::get($logPath))->toBeEmpty();
});

it('warns if the log file does not exist', function () {
    $logPath = storage_path('logs/laravel.log');
    if (File::exists($logPath)) {
        File::delete($logPath);
    }
    $this->artisan('log:clear')
        ->expectsOutput('No log file found at ' . $logPath)
        ->assertExitCode(0);
});

it('clears logs older than specified days', function () {
    $logPath = storage_path('logs/laravel.log');

    $oldLog = "[2023-01-01 12:00:00] test.ERROR: Old log message";
    $recentLog = "[" . Carbon::now()->format('Y-m-d') . " 12:00:00] test.INFO: Recent log message";
    $content = $oldLog . PHP_EOL . $recentLog;

    File::put($logPath, $content);

    $this->artisan('log:clear --days=30')
        ->expectsOutput('Logs older than 30 days have been removed')
        ->assertExitCode(0);

    $newContent = File::get($logPath);
    expect($newContent)->not->toContain($oldLog);
    expect($newContent)->toContain($recentLog);
});
