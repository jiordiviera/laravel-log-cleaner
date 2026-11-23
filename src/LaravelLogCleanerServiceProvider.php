<?php

declare(strict_types=1);

namespace JiordiViera\LaravelLogCleaner;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use JiordiViera\LaravelLogCleaner\Commands\ClearLogCommand;
use JiordiViera\LaravelLogCleaner\Commands\LogStatsCommand;
use JiordiViera\LaravelLogCleaner\Dashboard\DashboardAssets;
use JiordiViera\LaravelLogCleaner\Dashboard\Http\Middleware\Authorize;
use JiordiViera\LaravelLogCleaner\Dashboard\Http\Middleware\HandleInertiaRequests;

class LaravelLogCleanerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton('log-cleaner', function ($app) {
            return new LogCleaner;
        });

        $this->mergeConfigFrom(__DIR__.'/../config/log-cleaner.php', 'log-cleaner');

        // The dashboard needs Inertia's bindings even when the app disabled package discovery.
        $this->app->register(\Inertia\ServiceProvider::class);

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

        $this->bootDashboard();
    }

    /**
     * Views, assets, gates and routes of the optional web dashboard.
     */
    private function bootDashboard(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'log-cleaner');

        $this->publishes([
            __DIR__.'/../dist' => public_path(DashboardAssets::PUBLIC_PATH),
        ], 'log-cleaner-assets');

        // Apps override these in their own service provider, like Horizon's viewHorizon.
        foreach (['viewLogCleaner', 'manageLogCleaner'] as $ability) {
            if (! Gate::has($ability)) {
                Gate::define($ability, fn ($user = null): bool => $this->app->environment('local'));
            }
        }

        if (! Config::get('log-cleaner.dashboard.enabled', false) || $this->app->routesAreCached()) {
            return;
        }

        Route::group([
            'domain' => Config::get('log-cleaner.dashboard.domain'),
            'prefix' => trim((string) Config::get('log-cleaner.dashboard.path', 'log-cleaner'), '/'),
            'middleware' => array_merge(
                (array) Config::get('log-cleaner.dashboard.middleware', ['web']),
                [Authorize::class, HandleInertiaRequests::class],
            ),
            'as' => 'log-cleaner.',
        ], function (): void {
            $this->loadRoutesFrom(__DIR__.'/../routes/dashboard.php');
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
