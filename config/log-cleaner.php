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

];
