<?php

use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;

function logCleanerEvents(): array
{
    return array_values(array_filter(
        app(Schedule::class)->events(),
        fn (Event $event) => str_contains((string) $event->command, 'log:clear')
    ));
}

it('does not schedule log:clear by default', function () {
    expect(logCleanerEvents())->toBe([]);
});

describe('when scheduling is enabled', function () {
    beforeEach(function () {
        // Resolve the scheduler only after the config is set, as in a real app.
        $this->app->forgetInstance(Schedule::class);
    });

    it('registers log:clear with the configured cron and options', function () {
        config([
            'log-cleaner.schedule.enabled' => true,
            'log-cleaner.schedule.cron' => '30 3 * * 0',
            'log-cleaner.schedule.options' => ['days' => 14, 'compress' => true, 'backup' => false],
        ]);

        $events = logCleanerEvents();

        expect($events)->toHaveCount(1)
            ->and($events[0]->expression)->toBe('30 3 * * 0')
            ->and($events[0]->command)->toContain('--days=14')
            ->and($events[0]->command)->toContain('--compress')
            ->and($events[0]->command)->not->toContain('--backup')
            ->and($events[0]->description)->toBe('log-cleaner:scheduled-clear');
    });

    it('applies the configured timezone', function () {
        config([
            'log-cleaner.schedule.enabled' => true,
            'log-cleaner.schedule.timezone' => 'Africa/Douala',
        ]);

        expect(logCleanerEvents()[0]->timezone)->toBe('Africa/Douala');
    });
});
