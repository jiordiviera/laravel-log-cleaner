# Laravel Log Cleaner

[![Latest Version on Packagist](https://img.shields.io/packagist/v/jiordiviera/laravel-log-cleaner.svg?style=flat-square)](https://packagist.org/packages/jiordiviera/laravel-log-cleaner)
[![Total Downloads](https://img.shields.io/packagist/dt/jiordiviera/laravel-log-cleaner.svg?style=flat-square)](https://packagist.org/packages/jiordiviera/laravel-log-cleaner)

**Laravel Log Cleaner** is a simple package that allows you to clear the content of the `laravel.log` file using an Artisan command. This package is compatible with Laravel versions 7, 8, 9, 10, and 11.

## Installation

Install the package via **Composer** by running:

```bash
composer require jiordiviera/laravel-log-cleaner
```

## Compatibility

- Laravel 7.x
- Laravel 8.x
- Laravel 9.x
- Laravel 10.x
- Laravel 11.x

## Usage

This package adds an Artisan command to clear the content of the `laravel.log` file. To use this command, run:

```bash
php artisan log:clear
```

When you execute this command, all content in the `laravel.log` file will be cleared, and a confirmation message will be displayed.

### Example

```bash

$ php artisan log:clear
Laravel log file cleared successfully.
```

## Configuration

No additional configuration is required. Once the package is installed, the `log:clear` command is ready to use.

## Running Tests

This package uses **Pest** for testing. To run the tests, use:

```bash
./vendor/bin/pest
```

Ensure your tests are correctly defined in the `tests/` directory.

## Contributing

Contributions are welcome! Feel free to submit **Issues** or **Pull Requests** via [GitHub](https://github.com/jiordiviera/laravel-log-cleaner).

### Development Workflow

If you want to contribute:

1. Clone the repository:

   ```bash
   git clone https://github.com/jiordiviera/laravel-log-cleaner.git
   ```

2. Install dependencies:

   ```bash
   composer install
   ```

3. Run tests:

   ```bash
   ./vendor/bin/pest
   ```

## About

This package was developed to simplify log file management in Laravel projects. Instead of manually clearing the logs, you can now achieve it with a single command.

## License

The Laravel Log Cleaner package is open-source software licensed under the [MIT License](https://opensource.org/licenses/MIT).

---

> **Note:** Although this package was initially developed with Laravel 11, it is also compatible with earlier versions of Laravel (7, 8, 9, and 10).

For more information, visit the [GitHub repository](https://github.com/jiordiviera/laravel-log-cleaner).