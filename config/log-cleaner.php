<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Days to Keep
    |--------------------------------------------------------------------------
    |
    | The default number of days of logs to keep when running log:clear
    | without the --days option. Set to 0 to clear all logs by default.
    |
    */

    'days' => 0,

    /*
    |--------------------------------------------------------------------------
    | Memory Threshold
    |--------------------------------------------------------------------------
    |
    | The file size threshold (in bytes) at which memory-efficient processing
    | will be automatically enabled. Files larger than this will be processed
    | using stream operations to prevent memory issues.
    |
    | Default: 50MB (52428800 bytes)
    |
    */

    'memory_threshold' => 50 * 1024 * 1024,

    /*
    |--------------------------------------------------------------------------
    | Backup Settings
    |--------------------------------------------------------------------------
    |
    | Configure backup behavior for log cleaning operations.
    |
    */

    'backup' => [
        // Enable or disable backups by default
        'enabled' => false,

        // Maximum number of backups to keep (0 = unlimited)
        'max_backups' => 5,

        // Delete oldest backups when max is reached
        'auto_cleanup' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Compression Settings
    |--------------------------------------------------------------------------
    |
    | Configure compression behavior for old logs.
    |
    */

    'compression' => [
        // Enable compression by default
        'enabled' => false,

        // Compression level (1-9, where 9 is maximum compression)
        'level' => 9,
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Log Level Filter
    |--------------------------------------------------------------------------
    |
    | The default log level to filter by. Leave null to keep all levels.
    | Options: EMERGENCY, ALERT, CRITICAL, ERROR, WARNING, NOTICE, INFO, DEBUG
    |
    */

    'level' => null,

    /*
    |--------------------------------------------------------------------------
    | Custom Date Pattern
    |--------------------------------------------------------------------------
    |
    | Custom regex pattern for parsing log dates. Leave null to use defaults.
    | Example: '/^\[(\d{4}-\d{2}-\d{2})/'
    |
    */

    'pattern' => null,

    /*
    |--------------------------------------------------------------------------
    | Disk Space Validation
    |--------------------------------------------------------------------------
    |
    | Minimum free disk space (in MB) required before creating backups.
    | Set to 0 to disable this check.
    |
    */

    'min_free_disk_space_mb' => 100,

    /*
    |--------------------------------------------------------------------------
    | File Locking
    |--------------------------------------------------------------------------
    |
    | Enable file locking to prevent concurrent cleaning operations.
    |
    */

    'locking' => [
        'enabled' => true,
        'timeout' => 30, // Seconds to wait for lock
    ],

    /*
    |--------------------------------------------------------------------------
    | Events
    |--------------------------------------------------------------------------
    |
    | Enable or disable event dispatching for log cleaning operations.
    |
    */

    'events' => [
        'enabled' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | CLI Output Verbosity
    |--------------------------------------------------------------------------
    |
    | Default verbosity level for CLI output.
    | Options: 'minimal', 'normal', 'verbose'
    |
    */

    'verbosity' => 'normal',

    /*
    |--------------------------------------------------------------------------
    | Scheduled Cleaning
    |--------------------------------------------------------------------------
    |
    | When enabled, the package registers `log:clear` in Laravel's scheduler,
    | so you only need the usual `schedule:run` cron entry (or `schedule:work`).
    | `options` are passed to the command, e.g. ['days' => 30, 'compress' => true].
    | Boolean flags set to false are omitted.
    |
    */

    'schedule' => [
        'enabled' => env('LOG_CLEANER_SCHEDULE_ENABLED', false),
        'cron' => env('LOG_CLEANER_SCHEDULE_CRON', '0 2 * * *'), // every day at 02:00
        'timezone' => null,
        'options' => [
            'days' => 30,
            'backup' => false,
            'compress' => false,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Health Threshold
    |--------------------------------------------------------------------------
    |
    | Used by `php artisan log:stats`: the command exits with code 1 when the
    | analysed logs exceed this size (in MB). Leave null to never fail.
    | The --max-size option overrides this value.
    |
    */

    'health' => [
        'max_size_mb' => env('LOG_CLEANER_MAX_SIZE_MB'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Dashboard
    |--------------------------------------------------------------------------
    |
    | Optional web dashboard (Vue 3 + Inertia, shipped prebuilt in the package).
    | Disabled by default. Access is controlled by the `viewLogCleaner` gate
    | (and `manageLogCleaner` for destructive actions). Both default to the
    | `local` environment only: define them in your app to allow production.
    | Publish the assets with: php artisan vendor:publish --tag=log-cleaner-assets
    |
    */

    'dashboard' => [
        'enabled' => env('LOG_CLEANER_DASHBOARD_ENABLED', false),
        'path' => env('LOG_CLEANER_DASHBOARD_PATH', 'log-cleaner'),
        'domain' => env('LOG_CLEANER_DASHBOARD_DOMAIN'),
        'middleware' => ['web'],

        // Scanning large logs is slow: the overview is cached this many seconds.
        // The "refresh" button always rescans. 0 disables the cache.
        'stats_cache_seconds' => 60,

        // Optional "back to app" link shown in the header.
        'back_url' => null,

        // Default look; each viewer can change it from the "display" menu.
        'theme' => 'paper',    // paper | console
        'font' => 'jetbrains', // jetbrains | plex | inter | app
    ],

];
