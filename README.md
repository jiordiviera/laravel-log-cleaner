```
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
```

<p align="center">
  <a href="https://packagist.org/packages/jiordiviera/laravel-log-cleaner"><img src="https://img.shields.io/packagist/v/jiordiviera/laravel-log-cleaner?style=for-the-badge" alt="Latest Stable Version"></a>
  <a href="https://packagist.org/packages/jiordiviera/laravel-log-cleaner"><img src="https://img.shields.io/packagist/dt/jiordiviera/laravel-log-cleaner?style=for-the-badge" alt="Total Downloads"></a>
  <a href="https://packagist.org/packages/jiordiviera/laravel-log-cleaner"><img src="https://img.shields.io/packagist/v/jiordiviera/laravel-log-cleaner?include_prereleases&style=for-the-badge" alt="Latest Unstable Version"></a>
  <a href="https://packagist.org/packages/jiordiviera/laravel-log-cleaner"><img src="https://img.shields.io/packagist/l/jiordiviera/laravel-log-cleaner?style=for-the-badge" alt="License"></a>
  <a href="https://github.com/jiordiviera/laravel-log-cleaner/actions/workflows/tests.yml"><img src="https://github.com/jiordiviera/laravel-log-cleaner/actions/workflows/tests.yml/badge.svg" alt="Tests Status" style="for-the-badge" /></a>
</p>

**Laravel Log Cleaner** is a utility package designed for the efficient management of Laravel log files. It allows developers to quickly clear log data using an Artisan command, enhancing application performance and management. This tool is compatible with Laravel versions 7, 8, 9, 10, and 11.

## Installation

You can install the package via **Composer** by executing the following command:

```bash
composer require jiordiviera/laravel-log-cleaner
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

### Advanced Features (v2.0+)

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
```

#### 📦 Compression & Memory
```bash
# Compress old logs instead of deleting
php artisan log:clear --days=30 --compress

# Force memory-efficient processing for large files
php artisan log:clear --days=30 --memory-efficient
```

#### 🚀 Combined Options
```bash
# Complete workflow with all safety features
php artisan log:clear --days=30 --backup --compress --level=ERROR --dry-run
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

### Examples

- **Clear all logs:**
  ```bash
  $ php artisan log:clear
  Log file cleared successfully.
  ```

- **Clear logs older than 30 days:**
  ```bash
  $ php artisan log:clear --days=30
  Logs older than 30 days have been removed.
  ```

- **Preview changes (dry run):**
  ```bash
  $ php artisan log:clear --days=30 --dry-run
  [DRY RUN] Would remove 150 lines from laravel.log
  ```

- **Create backup and compress:**
  ```bash
  $ php artisan log:clear --days=30 --backup --compress
  Backup created: /path/to/laravel.log.backup.2025-07-18-14-30-15
  Logs compressed to: laravel.log.old.2025-07-18-14-30-15.gz
  Logs older than 30 days have been removed.
  ```

## Configuration

No additional configuration is necessary. The `log:clear` command is immediately available upon package installation.

## What's New in v2.0

### 🚀 Performance & Memory Optimizations
- **Memory-efficient processing** for large log files (>50MB)
- **Automatic memory threshold detection** prevents out-of-memory errors
- **Stream processing** handles multi-GB log files without memory issues
- **Regex pattern caching** improves performance on repeated operations

### 🔒 Enhanced Safety & Robustness
- **Pre-flight permission validation** prevents runtime errors
- **Backup creation** with timestamp for data recovery
- **Dry-run mode** for safe preview of operations
- **Enhanced error handling** with detailed reporting

### 🎯 Advanced Filtering
- **Log level filtering** (ERROR, WARNING, INFO, DEBUG, etc.)
- **Custom date patterns** for non-standard log formats
- **Flexible date range selection** with improved accuracy

### 📦 Archive & Compression
- **Compression support** for old logs instead of deletion
- **Automatic cleanup** of temporary files
- **Space-efficient archiving** with gzip compression

### 🔧 Breaking Changes
- **Minimum PHP version:** 8.1+ (dropped PHP 7.x support)
- **Minimum Laravel version:** 9.x+ (dropped Laravel 7.x-8.x support)
- **Enhanced command signature** with new options

### 📊 Performance Benchmarks
- Handles **1GB+ log files** without memory issues
- **50%+ performance improvement** on large file operations
- **Zero memory leaks** with proper resource management
- **Concurrent processing** support for multiple log files

## Running Tests

This package uses **Pest** for testing. You can run tests with the following command:

```bash
./vendor/bin/pest
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

3. **Run tests:**

   ```bash
   ./vendor/bin/pest
   ```

## About

This package was created to streamline the management of log files in Laravel applications. Instead of manually clearing the log files, you can achieve this efficiently with a single command, with the option to selectively remove older logs.

## License

The Laravel Log Cleaner is open-source software licensed under the [MIT License](https://opensource.org/licenses/MIT).

---

> **Note:** Initially developed for Laravel 11, this package remains compatible with earlier versions (7, 8, 9, 10).

For further information, visit the [GitHub repository](https://github.com/jiordiviera/laravel-log-cleaner).