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

        $logDate = $this->extractDate($line);

        return $logDate === null || $logDate->greaterThanOrEqualTo($cutoffDate);
    }

    /**
     * Returns the record date (start of day) from a header line, or null when no pattern parses.
     */
    public function extractDate(string $line): ?Carbon
    {
        foreach ($this->patterns as $pattern => $format) {
            if (preg_match($pattern, $line, $matches)) {
                try {
                    $date = Carbon::createFromFormat($format, $matches[1]);
                } catch (\Exception $e) {
                    continue;
                }

                if ($date instanceof Carbon) {
                    return $date->startOfDay();
                }
            }
        }

        return null;
    }

    /**
     * Returns the Monolog level of a header line (e.g. "local.ERROR:"), or null when none is found.
     */
    public function detectLevel(string $line): ?string
    {
        foreach (LogCleaner::LOG_LEVELS as $level) {
            if (preg_match('/\.'.$level.':/', $line)) {
                return $level;
            }
        }

        return null;
    }

    private function matchesLevel(string $line, ?string $filterLevel): bool
    {
        if (! $filterLevel) {
            return true;
        }

        $level = $this->detectLevel($line);

        return $level === null || strtoupper($filterLevel) === $level;
    }
}
