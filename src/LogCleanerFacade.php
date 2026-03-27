<?php

declare(strict_types=1);

namespace JiordiViera\LaravelLogCleaner;

use Illuminate\Support\Facades\Facade;

/**
 * @method static void clearAll(?string $file = null)
 * @method static void clearOld(int $days, ?string $file = null)
 * @method static void clearWithBackup(int $days = 0, ?string $file = null)
 * @method static void clearWithCompression(int $days = 0, ?string $file = null)
 * @method static void clear(int $days = 0, bool $backup = false, bool $compress = false, ?string $level = null, ?string $pattern = null, bool $memoryEfficient = false, ?string $file = null)
 */
class LogCleanerFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'log-cleaner';
    }
}
