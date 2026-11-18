<?php

use Illuminate\Support\Facades\File;
use JiordiViera\LaravelLogCleaner\LogStatsCollector;

use function Pest\Laravel\artisan;

beforeEach(function () {
    $this->logDirectory = storage_path('logs');
    File::ensureDirectoryExists($this->logDirectory);
    File::delete(File::files($this->logDirectory));
});

afterEach(function () {
    File::delete(File::files($this->logDirectory));
});

function writeStatsLog(string $name, array $lines): string
{
    $path = storage_path('logs/'.$name);
    File::put($path, implode(PHP_EOL, $lines).PHP_EOL);

    return $path;
}

describe('LogStatsCollector', function () {
    it('counts records per level and the date range, ignoring stack trace lines', function () {
        writeStatsLog('laravel.log', [
            '[2026-01-05 10:00:00] local.ERROR: Boom',
            '#0 /app/Foo.php(12): bar()',
            '#1 {main}',
            '[2026-01-03 09:00:00] local.INFO: Started',
            '[2026-01-07 08:00:00] local.WARNING: Slow query',
            '[2026-01-07 08:01:00] local.ERROR: Again',
        ]);

        $report = (new LogStatsCollector)->collect();
        $file = $report['files'][0];

        expect($file['file'])->toBe('laravel.log')
            ->and($file['records'])->toBe(4)
            ->and($file['levels']['ERROR'])->toBe(2)
            ->and($file['levels']['WARNING'])->toBe(1)
            ->and($file['levels']['INFO'])->toBe(1)
            ->and($file['oldest'])->toBe('2026-01-03')
            ->and($file['newest'])->toBe('2026-01-07')
            ->and($file['size_bytes'])->toBe(filesize(storage_path('logs/laravel.log')));
    });

    it('aggregates totals across files and reports archives', function () {
        $path = writeStatsLog('laravel.log', ['[2026-02-01 10:00:00] local.ERROR: A']);
        writeStatsLog('worker.log', ['[2025-12-31 10:00:00] local.DEBUG: B']);
        File::put($path.'.backup.2026-02-01-10-00-00', 'backup');
        File::put($path.'.old.2026-02-01-10-00-00.gz', 'gz');
        File::put(storage_path('logs/notes.txt'), 'ignored');

        $report = (new LogStatsCollector)->collect();

        expect($report['totals']['files'])->toBe(2)
            ->and($report['totals']['records'])->toBe(2)
            ->and($report['totals']['levels']['ERROR'])->toBe(1)
            ->and($report['totals']['levels']['DEBUG'])->toBe(1)
            ->and($report['totals']['oldest'])->toBe('2025-12-31')
            ->and($report['totals']['newest'])->toBe('2026-02-01')
            ->and($report['archives'])->toBe(['count' => 2, 'size_bytes' => 8]);
    });

    it('does not modify the analysed files', function () {
        $path = writeStatsLog('laravel.log', ['[2026-02-01 10:00:00] local.ERROR: A']);
        $before = [File::get($path), filemtime($path)];

        (new LogStatsCollector)->collect();
        clearstatcache();

        expect([File::get($path), filemtime($path)])->toBe($before);
    });
});

describe('log:stats command', function () {
    it('prints a table with totals', function () {
        writeStatsLog('laravel.log', [
            '[2026-01-05 10:00:00] local.ERROR: Boom',
            '[2026-01-06 10:00:00] local.INFO: Ok',
        ]);

        artisan('log:stats')
            ->expectsOutputToContain('laravel.log')
            ->expectsOutputToContain('Total: 1 file(s)')
            ->expectsOutputToContain('By level: ERROR 1, INFO 1')
            ->assertExitCode(0);
    });

    it('analyses a single file with --file', function () {
        writeStatsLog('laravel.log', ['[2026-01-05 10:00:00] local.ERROR: A']);
        writeStatsLog('other.log', ['[2026-01-05 10:00:00] local.INFO: B']);

        artisan('log:stats', ['--file' => 'other.log', '--json' => true])
            ->expectsOutputToContain('"file": "other.log"')
            ->doesntExpectOutputToContain('"file": "laravel.log"')
            ->assertExitCode(0);
    });

    it('fails when the logs exceed --max-size', function () {
        writeStatsLog('laravel.log', [str_repeat('x', 2048)]);

        artisan('log:stats', ['--max-size' => '0.001'])
            ->expectsOutputToContain('exceeds the')
            ->assertExitCode(1);
    });

    it('uses the configured health threshold', function () {
        config(['log-cleaner.health.max_size_mb' => 0.001]);
        writeStatsLog('laravel.log', [str_repeat('x', 2048)]);

        artisan('log:stats', ['--json' => true])
            ->expectsOutputToContain('"exceeded": true')
            ->assertExitCode(1);
    });

    it('succeeds under the threshold', function () {
        writeStatsLog('laravel.log', ['[2026-01-05 10:00:00] local.INFO: A']);

        artisan('log:stats', ['--max-size' => '10'])->assertExitCode(0);
    });

    it('rejects an invalid --max-size', function () {
        writeStatsLog('laravel.log', ['[2026-01-05 10:00:00] local.INFO: A']);

        artisan('log:stats', ['--max-size' => 'abc'])
            ->expectsOutputToContain('must be a positive number')
            ->assertExitCode(1);
    });

    it('warns when there are no log files', function () {
        artisan('log:stats')
            ->expectsOutputToContain('No log files found')
            ->assertExitCode(1);
    });
});
