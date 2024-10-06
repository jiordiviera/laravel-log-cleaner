<?php

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