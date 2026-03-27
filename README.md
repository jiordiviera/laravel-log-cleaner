██╗      █████╗ ██████╗  █████╗ ██╗   ██╗███████╗██╗
██║     ██╔══██╗██╔══██╗██╔══██╗██║   ██║██╔════╝██║
██║     ███████║██████╔╝███████║██║   ██║█████╗  ██║
██║     ██╔══██║██╔══██╗██╔══██║╚██╗ ██╔╝██╔══╝  ██║
███████╗██║  ██║██║  ██║██║  ██║ ╚████╔╝ ███████╗███████╗
╚══════╝╚═╝  ╚═╝╚═╝  ╚═╝╚═╝  ╚═╝  ╚═══╝  ╚══════╝╚══════╝

██╗      ██████╗  ██████╗
██║     ██╔═══██╗██╔════╝
██║     ██║   ██║██║  ███╗
██║     ██║   ██║██║   ██║
███████╗╚██████╔╝╚██████╔╝
╚══════╝ ╚═════╝  ╚═════╝

 ██████╗██╗     ███████╗ █████╗ ███╗   ██╗███████╗██████╗
██╔════╝██║     ██╔════╝██╔══██╗████╗  ██║██╔════╝██╔══██╗
██║     ██║     █████╗  ███████║██╔██╗ ██║█████╗  ██████╔╝
██║     ██║     ██╔══╝  ██╔══██║██║╚██╗██║██╔══╝  ██╔══██╗
╚██████╗███████╗███████╗██║  ██║██║ ╚████║███████╗██║  ██║
 ╚═════╝╚══════╝╚══════╝╚═╝  ╚═╝╚═╝  ╚═══╝╚══════╝╚═╝  ╚═╝

<p align="center">
  <a href="https://packagist.org/packages/jiordiviera/laravel-log-cleaner"><img src="https://img.shields.io/packagist/v/jiordiviera/laravel-log-cleaner?style=for-the-badge" alt="Latest Stable Version"></a>
  <a href="https://packagist.org/packages/jiordiviera/laravel-log-cleaner"><img src="https://img.shields.io/packagist/dt/jiordiviera/laravel-log-cleaner?style=for-the-badge" alt="Total Downloads"></a>
  <a href="https://packagist.org/packages/jiordiviera/laravel-log-cleaner"><img src="https://img.shields.io/packagist/v/jiordiviera/laravel-log-cleaner?include_prereleases&style=for-the-badge" alt="Latest Unstable Version"></a>
  <a href="https://packagist.org/packages/jiordiviera/laravel-log-cleaner"><img src="https://img.shields.io/packagist/l/jiordiviera/laravel-log-cleaner?style=for-the-badge" alt="License"></a>
  <a href="https://github.com/jiordiviera/laravel-log-cleaner/actions/workflows/tests.yml"><img src="https://github.com/jiordiviera/laravel-log-cleaner/actions/workflows/tests.yml/badge.svg" alt="Tests Status" style="for-the-badge" /></a>
</p>

**Laravel Log Cleaner** is a utility package designed for the efficient management of Laravel log files. It allows developers to quickly clear log data using an Artisan command, enhancing application performance and management. This tool is compatible with Laravel versions 9, 10, 11, and 12.

## Installation

You can install the package via **Composer** by executing the following command:

```bash
composer require jiordiviera/laravel-log-cleaner
```

### Publishing Configuration (Optional)

To customize default behavior, publish the configuration file:

```bash
php artisan vendor:publish --provider="JiordiViera\LaravelLogCleaner\LaravelLogCleanerServiceProvider" --tag="log-cleaner-config"
```

## Compatibility

### Version 2.x (Current)
**PHP Requirements:**
- PHP 8.1+
- PHP 8.2+
- PHP 8.3+

**Laravel Support:**
- Laravel 9.x
- Laravel 10.x
- Laravel 11.x
- Laravel 12.x

### Version 1.x (Legacy)
For older PHP versions, use version 1.x:
- PHP 7.0+ to 8.2
- Laravel 7.x to 11.x

```bash
composer require jiordiviera/laravel-log-cleaner:^1.0
```

## Usage

After installation, an Artisan command is available to clear the Laravel log file with advanced options:

### Basic Usage

1. **Clear all logs:**
   ```bash
   php artisan log:clear
   ```

2. **Clear logs older than specific days:**
   ```bash
   php artisan log:clear --days=30
   ```

### Advanced Features

#### 🔒 Safe Operations
```bash
# Preview what would be deleted (dry run)
php artisan log:clear --days=30 --dry-run

# Create backup before cleaning
php artisan log:clear --days=30 --backup
```

#### 🎯 Targeted Cleaning
```bash
# Keep only ERROR level logs
php artisan log:clear --days=0 --level=ERROR

# Clean with custom date pattern
php artisan log:clear --days=30 --pattern="/^(\d{4}-\d{2}-\d{2})/"

# Clean only specific log file
php artisan log:clear --days=30 --file=laravel.log
```

#### 📦 Compression & Memory
```bash
# Compress old logs instead of deleting
php artisan log:clear --days=30 --compress

# Force memory-efficient processing for large files
php artisan log:clear --days=30 --memory-efficient
```

#### 🔧 Advanced Options
```bash
# Disable file locking (for scripted operations)
php artisan log:clear --days=30 --no-lock

# Disable event dispatching (for performance)
php artisan log:clear --days=30 --no-events
```

#### 🚀 Combined Options
```bash
# Complete workflow with all safety features
php artisan log:clear --days=30 --backup --compress --level=ERROR --dry-run
```

### Programmatic Usage with Facade

You can also use the `LogCleaner` facade to clear logs programmatically in your code:

```php
use JiordiViera\LaravelLogCleaner\LogCleanerFacade as LogCleaner;

// Clear all logs
LogCleaner::clearAll();

// Clear logs older than 30 days
LogCleaner::clearOld(30);

// Clear with backup
LogCleaner::clearWithBackup(30);

// Clear with compression
LogCleaner::clearWithCompression(30);

// Clear only specific log file
LogCleaner::clearOld(30, 'laravel.log');

// Advanced usage with all options
LogCleaner::clear(
    days: 30,
    backup: true,
    compress: true,
    level: 'ERROR',
    pattern: '/^(\d{4}-\d{2}-\d{2})/',
    memoryEfficient: true,
    file: 'laravel.log'
);
```

### Available Options

| Option | Description | Example |
|--------|-------------|---------|
| `--days=N` | Keep logs from last N days | `--days=30` |
| `--backup` | Create backup before cleaning | `--backup` |
| `--dry-run` | Preview changes without applying | `--dry-run` |
| `--level=LEVEL` | Filter by log level (ERROR, WARNING, INFO, DEBUG) | `--level=ERROR` |
| `--pattern=REGEX` | Custom date pattern matching | `--pattern="/^(\d{4}-\d{2}-\d{2})/"` |
| `--compress` | Compress old logs instead of deleting | `--compress` |
| `--memory-efficient` | Force memory-efficient processing | `--memory-efficient` |
| `--file=FILENAME` | Clean only specific log file | `--file=laravel.log` |
| `--no-lock` | Disable file locking | `--no-lock` |
| `--no-events` | Disable event dispatching | `--no-events` |

### Examples

- **Clear all logs:**
  ```bash
  $ php artisan log:clear
  ✅ All logs cleared - 1,250 lines removed, 45.2 MB freed
  ```

- **Clear logs older than 30 days:**
  ```bash
  $ php artisan log:clear --days=30
  ✅ Logs older than 30 days cleared - 850 lines removed, 32.1 MB freed
  ```

- **Preview changes (dry run):**
  ```bash
  $ php artisan log:clear --days=30 --dry-run
  🔍 Dry Run Mode - No changes will be made
  
  ┌─────────────┬───────────────┬───────────────┐
  │ File        │ Lines to Remove │ Space to Free │
  ├─────────────┼───────────────┼───────────────┤
  │ laravel.log │ 850           │ 32.1 MB       │
  └─────────────┴───────────────┴───────────────┘
  
  Total: 850 lines to be removed
  ```

- **Create backup and compress:**
  ```bash
  $ php artisan log:clear --days=30 --backup --compress
  
  ┌─────────────┬───────────────┬─────────────┬────────┬────────────┐
  │ File        │ Lines Removed │ Space Freed │ Backup │ Compressed │
  ├─────────────┼───────────────┼─────────────┼────────┼────────────┤
  │ laravel.log │ 850           │ 32.1 MB     │ ✓      │ ✓          │
  └─────────────┴───────────────┴─────────────┴────────┴────────────┘
  
  ✅ Logs older than 30 days cleared - 850 lines removed, 32.1 MB freed
  📦 1 backup(s) created
  🗜️ 1 file(s) compressed
  ```

- **Keep only ERROR logs:**
  ```bash
  $ php artisan log:clear --days=0 --level=ERROR
  ✅ All logs cleared - 1,100 lines removed, 38.5 MB freed
  ```

## Configuration

The package provides a comprehensive configuration file with sensible defaults. Publish it with:

```bash
php artisan vendor:publish --tag="log-cleaner-config"
```

### Configuration Options

```php
return [
    // Default number of days to keep (0 = clear all)
    'days' => 0,

    // Memory threshold for automatic memory-efficient processing (50MB default)
    'memory_threshold' => 50 * 1024 * 1024,

    // Backup settings
    'backup' => [
        'enabled' => false,        // Enable backups by default
        'max_backups' => 5,        // Maximum backups to keep
        'auto_cleanup' => true,    // Auto-delete old backups
    ],

    // Compression settings
    'compression' => [
        'enabled' => false,        // Enable compression by default
        'level' => 9,              // Compression level (1-9)
    ],

    // Default log level filter (null = all levels)
    'level' => null,

    // Custom date pattern for parsing logs
    'pattern' => null,

    // Minimum free disk space (MB) required for backups
    'min_free_disk_space_mb' => 100,

    // File locking settings
    'locking' => [
        'enabled' => true,         // Enable file locking
        'timeout' => 30,           // Lock timeout in seconds
    ],

    // Event dispatching
    'events' => [
        'enabled' => true,         // Enable Laravel events
    ],
];
```

## Events

The package dispatches Laravel events that you can listen to:

```php
use JiordiViera\LaravelLogCleaner\Events\LogCleaning;
use JiordiViera\LaravelLogCleaner\Events\LogCleaned;
use JiordiViera\LaravelLogCleaner\Events\LogFileCleaned;

// In EventServiceProvider or elsewhere:
protected $listen = [
    LogCleaning::class => [
        LogCleaningListener::class,
    ],
    LogCleaned::class => [
        LogCleanedListener::class,
    ],
    LogFileCleaned::class => [
        LogFileCleanedListener::class,
    ],
];
```

### Event Properties

**LogCleaning** (dispatched before cleaning starts):
- `$days`, `$backup`, `$compress`, `$level`, `$pattern`, `$memoryEfficient`, `$file`, `$dryRun`

**LogCleaned** (dispatched after cleaning completes):
- All properties from LogCleaning + `$results` (array of cleaning results)

**LogFileCleaned** (dispatched for each file cleaned):
- `$file`, `$linesRemoved`, `$bytesFreed`, `$backupPath`, `$compressedPath`

## Exception Handling

The package uses custom exceptions for precise error handling:

```php
use JiordiViera\LaravelLogCleaner\Exceptions\InvalidDaysException;
use JiordiViera\LaravelLogCleaner\Exceptions\NoLogFilesException;
use JiordiViera\LaravelLogCleaner\Exceptions\InvalidLogLevelException;
use JiordiViera\LaravelLogCleaner\Exceptions\InvalidPatternException;
use JiordiViera\LaravelLogCleaner\Exceptions\PermissionException;
use JiordiViera\LaravelLogCleaner\Exceptions\BackupException;
use JiordiViera\LaravelLogCleaner\Exceptions\DiskSpaceException;
use JiordiViera\LaravelLogCleaner\Exceptions\FileLockException;
use JiordiViera\LaravelLogCleaner\Exceptions\ZlibException;

try {
    LogCleaner::clear(days: -1);
} catch (InvalidDaysException $e) {
    // Handle invalid days parameter
}
```

## Running Tests

This package uses **Pest** for testing. You can run tests with the following command:

```bash
./vendor/bin/pest
```

### Static Analysis

The package includes PHPStan configuration for static analysis (level 4):

```bash
composer analyse
# or
./vendor/bin/phpstan analyse
```

### Code Formatting

The package uses Laravel Pint for code formatting:

```bash
# Format all files
composer format

# Check formatting without modifying
composer lint
```

Ensure your tests are organized correctly within the `tests/` directory.

## Contributing

Contributions are welcomed! Feel free to submit **Issues** or **Pull Requests** on [GitHub](https://github.com/jiordiviera/laravel-log-cleaner).

### Development Workflow

For contributors:

1. **Clone the repository:**

   ```bash
   git clone https://github.com/jiordiviera/laravel-log-cleaner.git
   ```

2. **Install dependencies:**

   ```bash
   composer install
   ```

3. **Run tests and static analysis:**

   ```bash
   ./vendor/bin/pest
   composer phpstan
   ```

## What's New in v2.1

### 🎯 Configuration System
- **Publishable config file** - Customize defaults without code changes
- **Default values** for days, backup, compression, log levels
- **Memory threshold** configuration
- **Lock timeout** settings

### 🔒 Enhanced Safety
- **File locking** - Prevent concurrent cleaning operations
- **Disk space validation** - Ensure sufficient space before backups
- **Backup retention** - Auto-cleanup old backups (configurable)
- **Custom exceptions** - Precise error handling

### 📡 Events System
- **LogCleaning** - Dispatched before cleaning starts
- **LogCleaned** - Dispatched after cleaning completes
- **LogFileCleaned** - Dispatched for each file cleaned
- **Configurable** - Disable events with `--no-events`

### 🎨 Improved CLI
- **Beautiful table output** - Clear summary of operations
- **Emoji indicators** - Easy visual scanning
- **Detailed statistics** - Lines removed, space freed
- **Backup/compression status** - See what was created

### 🧪 Enhanced Testing
- **Test helpers trait** - Reusable test utilities
- **Edge case coverage** - Empty files, unicode, large files
- **Event testing** - Verify event dispatching
- **Exception testing** - Comprehensive error coverage

### 📊 Code Quality
- **PHPStan level 8** - Strict static analysis
- **Custom exceptions** - Type-safe error handling
- **Better architecture** - Clean separation of concerns

## About

This package was created to streamline the management of log files in Laravel applications. Instead of manually clearing the log files, you can achieve this efficiently with a single command, with the option to selectively remove older logs.

## License

The Laravel Log Cleaner is open-source software licensed under the [MIT License](https://opensource.org/licenses/MIT).

---

> **Note:** This package requires PHP 8.1+ and Laravel 9.x or higher.

For further information, visit the [GitHub repository](https://github.com/jiordiviera/laravel-log-cleaner).
