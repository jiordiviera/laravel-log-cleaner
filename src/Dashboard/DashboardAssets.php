<?php

declare(strict_types=1);

namespace JiordiViera\LaravelLogCleaner\Dashboard;

/**
 * Locates the prebuilt dashboard assets published to public/vendor/log-cleaner.
 */
final class DashboardAssets
{
    public const PACKAGE_VERSION = '2.5.0-dev';

    public const REPOSITORY_URL = 'https://github.com/jiordiviera/laravel-log-cleaner';

    public const PUBLIC_PATH = 'vendor/log-cleaner';

    public static function published(): bool
    {
        return is_file(public_path(self::PUBLIC_PATH.'/app.js'));
    }

    /**
     * Inertia asset version: changes whenever the published bundle changes.
     */
    public static function version(): ?string
    {
        $file = public_path(self::PUBLIC_PATH.'/app.js');

        return is_file($file) ? (string) hash_file('xxh128', $file) : null;
    }

    /**
     * Whether the published bundle differs from the one shipped in the package.
     */
    public static function outdated(): bool
    {
        $shipped = __DIR__.'/../../dist/app.js';
        $published = public_path(self::PUBLIC_PATH.'/app.js');

        return is_file($shipped) && is_file($published)
            && hash_file('xxh128', $shipped) !== hash_file('xxh128', $published);
    }
}
