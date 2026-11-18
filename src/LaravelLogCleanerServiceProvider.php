<?php

declare(strict_types=1);

namespace JiordiViera\LaravelLogCleaner;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;
use JiordiViera\LaravelLogCleaner\Commands\ClearLogCommand;
use JiordiViera\LaravelLogCleaner\Commands\LogStatsCommand;

class LaravelLogCleanerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton('log-cleaner', function ($app) {
            return new LogCleaner;
        });

        $this->mergeConfigFrom(__DIR__.'/../config/log-cleaner.php', 'log-cleaner');

        $this->commands([
            ClearLogCommand::class,
            LogStatsCommand::class,
        ]);
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/log-cleaner.php' => config_path('log-cleaner.php'),
        ], 'log-cleaner-config');

        $this->publishes([
            __DIR__.'/../config/log-cleaner.php' => config_path('log-cleaner.php'),
        ], 'log-cleaner');

        $this->callAfterResolving(Schedule::class, function (Schedule $schedule): void {
            $this->registerSchedule($schedule);
        });
    }

    /**
     * Registers `log:clear` in the application scheduler when `log-cleaner.schedule.enabled` is true.
     */
    private function registerSchedule(Schedule $schedule): void
    {
        if (! Config::get('log-cleaner.schedule.enabled', false)) {
            return;
        }

        $parameters = [];
        foreach ((array) Config::get('log-cleaner.schedule.options', []) as $option => $value) {
            if ($value === false || $value === null) {
                continue;
            }

            $option = str_starts_with((string) $option, '--') ? (string) $option : '--'.$option;
            $parameters[$option] = $value;
        }

        $event = $schedule->command('log:clear', $parameters)
            ->cron((string) Config::get('log-cleaner.schedule.cron', '0 2 * * *'))
            ->name('log-cleaner:scheduled-clear');

        $timezone = Config::get('log-cleaner.schedule.timezone');
        if (is_string($timezone) && $timezone !== '') {
            $event->timezone($timezone);
        }
    }
}
