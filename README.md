# Laravel Log Cleaner

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

This package supports the following Laravel versions:
- Laravel 7.x
- Laravel 8.x
- Laravel 9.x
- Laravel 10.x
- Laravel 11.x

## Usage

After installation, an Artisan command is available to clear the Laravel log file. You can use this command in two ways:

1. **To clear all logs:**

   ```bash
   php artisan log:clear
   ```

2. **To clear logs older than a specific number of days:**

   ```bash
   php artisan log:clear --days=30
   ```

   Replace `30` with desired days. This will delete all log entries older than the specified days.

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

## Configuration

No additional configuration is necessary. The `log:clear` command is immediately available upon package installation.

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