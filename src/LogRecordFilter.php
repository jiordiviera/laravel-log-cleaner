<?php

declare(strict_types=1);

namespace JiordiViera\LaravelLogCleaner;

use Carbon\Carbon;
use JiordiViera\LaravelLogCleaner\Exceptions\InvalidPatternException;

/**
 * Classifies log record headers and applies date/level retention rules.
 *
 * Continuation lines are handled by the caller using the previous header's decision.
 */
final class LogRecordFilter
{
    /** @var array<string, string> */
    private array $patterns;

    public function __construct(?string $customPattern = null)
    {
        if ($customPattern) {
            if (@preg_match($customPattern, '') === false) {
                throw InvalidPatternException::create(preg_last_error_msg());
            }

            $this->patterns = [$customPattern => 'Y-m-d'];
        } else {
            $this->patterns = [
                '/^\[(\d{4}-\d{2}-\d{2})/' => 'Y-m-d',
                '/^\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})/' => 'Y-m-d H:i:s',
                '/^(\d{4}-\d{2}-\d{2})/' => 'Y-m-d',
            ];
        }
    }

    public function isRecordStart(string $line): bool
    {
        foreach ($this->patterns as $pattern => $format) {
            if (preg_match($pattern, $line)) {
                return true;
            }
        }

        return false;
    }

    public function shouldKeep(string $line, Carbon $cutoffDate, ?string $level): bool
    {
        if (! $this->matchesLevel($line, $level)) {
            return false;
        }

        if ($level && $cutoffDate->isToday()) {
            return true;
        }

        foreach ($this->patterns as $pattern => $format) {
            if (preg_match($pattern, $line, $matches)) {
                try {
                    $logDate = Carbon::createFromFormat($format, $matches[1])->startOfDay();

                    return $logDate->greaterThanOrEqualTo($cutoffDate);
                } catch (\Exception $e) {
                    continue;
                }
            }
        }

        return true;
    }

    private function matchesLevel(string $line, ?string $filterLevel): bool
    {
        if (! $filterLevel) {
            return true;
        }

        foreach (LogCleaner::LOG_LEVELS as $level) {
            if (preg_match('/\.'.$level.':/', $line)) {
                return strtoupper($filterLevel) === $level;
            }
        }

        return true;
    }
}
