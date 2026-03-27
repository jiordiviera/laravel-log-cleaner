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
            return new LogCleaner();
        });

        $this->commands([
            ClearLogCommand::class
        ]);
    }

    public function boot(): void
    {

    }

}