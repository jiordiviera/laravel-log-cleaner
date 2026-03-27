# Changelog

All notable changes to `laravel-log-cleaner` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [2.2.0] - 2026-03-27

### Added
- **Configuration file** - Publishable config for customizing defaults (`php artisan vendor:publish --tag="log-cleaner-config"`)
- **Events system** - Three new events: `LogCleaning`, `LogCleaned`, `LogFileCleaned`
- **File locking** - Prevent concurrent cleaning operations with configurable timeout
- **Disk space validation** - Check available space before creating backups
- **Backup retention** - Auto-cleanup old backups with configurable max limit
- **Custom exceptions** - Type-safe exception handling:
  - `InvalidDaysException`
  - `NoLogFilesException`
  - `InvalidLogLevelException`
  - `InvalidPatternException`
  - `PermissionException`
  - `BackupException`
  - `DiskSpaceException`
  - `FileLockException`
  - `ZlibException`
- **New CLI options**:
  - `--no-lock` - Disable file locking
  - `--no-events` - Disable event dispatching
- **PHPStan configuration** - Level 4 static analysis
- **Laravel Pint** - Code formatting and linting (`composer format`, `composer lint`)

### Changed
- **Improved CLI output** - Beautiful table formatting with emoji indicators
- **Better error messages** - Clear, actionable error reporting
- **Enhanced command signature** - More descriptive option help
- **Refactored LogCleaner class** - Cleaner architecture with separation of concerns
- **Updated Service Provider** - Config merging and publishing support

### Improved
- **Default values from config** - Days, backup, compression, level settings
- **Memory threshold** - Configurable threshold for memory-efficient processing
- **Test coverage** - Comprehensive tests for events, exceptions, edge cases
- **Test helpers** - Reusable `CreatesLogFiles` trait for testing
- **Documentation** - Updated README with all new features

### Fixed
- **Exception handling** - Proper exception types throughout the codebase
- **Lock file cleanup** - Always remove lock files after operations
- **Backup file management** - Proper cleanup of old backups

### Deprecated
- Nothing - All previous functionality remains backwards compatible

## [2.1.0] - 2026-01-15

### Added
- **LogCleanerFacade**: New facade for programmatic access to log cleaning functionality
- **File-specific cleaning** (`--file=FILENAME`): Clean specific log files instead of all logs
- **Programmatic API**: Use `LogCleaner::clear()` directly in code for custom integrations

### Improved
- **Code refactoring**: Eliminated code duplication between `ClearLogCommand` and `LogCleaner` class
- **Better architecture**: Command now delegates to shared `LogCleaner` logic for consistency
- **Enhanced test coverage**: Added comprehensive tests for the new facade functionality

### Changed
- **ClearLogCommand refactoring**: Command now uses `LogCleaner` class internally, reducing maintenance overhead
- **Facade registration**: Added `LogCleaner` alias in composer.json for easy access

### Fixed
- Minor improvements in error handling and code organization

## [2.0.1] - 2025-01-06

### Added
- Display estimated disk space to be freed in dry-run mode
- Validation for zlib extension before compression operations
- Enhanced validation for custom regex patterns
- Comprehensive test suite with 26 tests, 81 assertions

### Improved
- Dry-run output now shows both line count and estimated space (MB/GB)
- Better error messages for invalid regex patterns
- Enhanced test coverage for all features including compression
- Improved edge case handling for empty files, whitespace, and multiline logs

### Fixed
- Dry-run mode now properly suppresses non-dry-run messages
- Pattern validation correctly rejects invalid regex with helpful error messages
- Improved handling of empty log files and files with only whitespace
- Better management of backup/compress file naming conflicts

## [2.0.0] - 2024-07-18

### Added
- **Dry-run mode** (`--dry-run`): Preview changes without modifying files
- **Backup creation** (`--backup`): Create timestamped backups before cleaning
  - Format: `laravel.log.backup.YYYY-MM-DD-HH-MM-SS`
  - Automatic conflict resolution for multiple backups
- **Log level filtering** (`--level=LEVEL`): Keep only specific log levels
  - Supported: EMERGENCY, ALERT, CRITICAL, ERROR, WARNING, NOTICE, INFO, DEBUG
  - Preserves multi-line stack traces
  - Combines with date filtering
- **Custom date patterns** (`--pattern=REGEX`): Support for non-standard log formats
  - Custom regex pattern matching
  - Flexible date extraction
- **Compression support** (`--compress`): Archive old logs instead of deleting
  - Creates `.gz` compressed archives
  - Format: `laravel.log.old.YYYY-MM-DD-HH-MM-SS.gz`
  - Maximum compression level (level 9)
- **Memory-efficient processing** (`--memory-efficient`): Handle large log files
  - Stream processing for files >50MB
  - Automatic threshold detection
  - Prevents out-of-memory errors

### Changed
- **BREAKING**: Minimum PHP version now 8.1+ (dropped PHP 7.x support)
- **BREAKING**: Minimum Laravel version now 9.x+ (dropped Laravel 7.x-8.x support)
- Enhanced error handling with detailed messages
- Improved file permission validation before operations
- Better handling of concurrent file access
- Optimized regex pattern compilation and caching

### Performance
- 50%+ performance improvement on large files (>100MB)
- Handles 1GB+ log files without memory issues
- Zero memory leaks with proper resource management
- Concurrent processing support for multiple log files

### Security
- Pre-flight permission validation
- Safe handling of invalid regex patterns
- Protection against path traversal
- Secure temporary file handling

### Testing
- Comprehensive test suite covering all features
- Performance benchmarks
- Memory usage validation
- Edge case coverage

## [1.0.4] - 2024-12-15

### Fixed
- Minor bug fixes and improvements
- Updated dependencies

## [1.0.3] - 2024-12-10

### Fixed
- Compatibility fixes for Laravel 11
- Improved error handling

## [1.0.2] - 2024-11-20

### Added
- Support for Laravel 11.x
- Improved documentation

### Fixed
- Minor bug fixes

## [1.0.1] - 2024-10-15

### Fixed
- Bug fixes and stability improvements
- Documentation updates

## [1.0.0] - 2024-09-01

### Added
- Initial release
- Basic log clearing functionality
- `--days` option to keep recent logs
- Support for Laravel 7.x, 8.x, 9.x, 10.x
- Support for PHP 7.0+

---

## Migration Guides

### From v2.1 to v2.2

#### New Features (Optional)

1. **Publish configuration file** (optional):
   ```bash
   php artisan vendor:publish --tag="log-cleaner-config"
   ```

2. **Listen to events** (optional):
   ```php
   use JiordiViera\LaravelLogCleaner\Events\LogCleaned;
   
   // In EventServiceProvider
   protected $listen = [
       LogCleaned::class => [LogCleanedListener::class],
   ];
   ```

3. **Use custom exceptions** (optional):
   ```php
   use JiordiViera\LaravelLogCleaner\Exceptions\InvalidDaysException;
   
   try {
       LogCleaner::clear(days: -1);
   } catch (InvalidDaysException $e) {
       // Handle error
   }
   ```

#### Breaking Changes
- None - All changes are backwards compatible

#### Upgrade Steps

1. Update the package:
   ```bash
   composer require jiordiviera/laravel-log-cleaner:^2.2
   ```

2. (Optional) Publish config to customize defaults:
   ```bash
   php artisan vendor:publish --tag="log-cleaner-config"
   ```

3. (Optional) Add event listeners for monitoring

### From v1.x to v2.0

#### Requirements
- PHP 8.1 or higher
- Laravel 9.x or higher

#### Breaking Changes
1. **PHP Version**: Minimum PHP 8.1 required
2. **Laravel Version**: Minimum Laravel 9.x required

#### Upgrade Steps

1. Update `composer.json`:
   ```bash
   composer require jiordiviera/laravel-log-cleaner:^2.0
   ```

2. Ensure PHP 8.1+ and Laravel 9.x+ are installed

3. No configuration changes needed - all new features are optional

#### Backwards Compatibility

Basic usage remains unchanged:
```bash
# These work identically in v1.x and v2.x
php artisan log:clear
php artisan log:clear --days=30
```

New features are opt-in:
```bash
# New v2.x features
php artisan log:clear --days=30 --backup
php artisan log:clear --days=30 --compress
php artisan log:clear --level=ERROR --dry-run
```

### Staying on v1.x

For older PHP or Laravel versions, continue using v1.x:
```bash
composer require jiordiviera/laravel-log-cleaner:^1.0
```

**v1.x Support**: Security fixes only until 2026-01-01

---

For detailed information about each release, see the [releases page](https://github.com/jiordiviera/laravel-log-cleaner/releases).
