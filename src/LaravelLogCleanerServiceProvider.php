<?php

declare(strict_types=1);

namespace JiordiViera\LaravelLogCleaner;

use Illuminate\Support\ServiceProvider;
use JiordiViera\LaravelLogCleaner\Commands\ClearLogCommand;

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
    }
}
