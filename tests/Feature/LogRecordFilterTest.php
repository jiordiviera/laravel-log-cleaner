<?php

declare(strict_types=1);

use Carbon\Carbon;
use JiordiViera\LaravelLogCleaner\Exceptions\InvalidPatternException;
use JiordiViera\LaravelLogCleaner\LogRecordFilter;

it('recognizes dated headers but not their multiline continuations', function () {
    $filter = new LogRecordFilter;

    expect($filter->isRecordStart('[2026-10-01] production.ERROR: crash'))->toBeTrue()
        ->and($filter->isRecordStart('2026-10-01 INFO: plain'))->toBeTrue()
        ->and($filter->isRecordStart('Stack trace:'))->toBeFalse()
        ->and($filter->isRecordStart('#0 /app/file.php'))->toBeFalse();
});

it('retains recent records and removes expired records', function () {
    $filter = new LogRecordFilter;
    $cutoff = Carbon::parse('2026-10-15')->startOfDay();

    expect($filter->shouldKeep('[2026-10-01] production.ERROR: old', $cutoff, null))->toBeFalse()
        ->and($filter->shouldKeep('[2026-10-20] production.ERROR: new', $cutoff, null))->toBeTrue();
});

it('supports a custom header date pattern', function () {
    $filter = new LogRecordFilter('/^(\d{4}-\d{2}-\d{2})/');
    $cutoff = Carbon::parse('2026-10-15')->startOfDay();

    expect($filter->isRecordStart('2026-10-01 INFO: old'))->toBeTrue()
        ->and($filter->shouldKeep('2026-10-01 INFO: old', $cutoff, null))->toBeFalse()
        ->and($filter->shouldKeep('2026-10-20 INFO: recent', $cutoff, null))->toBeTrue();
});

it('rejects invalid custom patterns', function () {
    expect(fn () => new LogRecordFilter('/[unclosed/'))
        ->toThrow(InvalidPatternException::class);
});
