<?php

declare(strict_types=1);

namespace JiordiViera\LaravelLogCleaner\Dashboard\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Inertia\Inertia;
use Inertia\Response;
use JiordiViera\LaravelLogCleaner\Exceptions\NoLogFilesException;
use JiordiViera\LaravelLogCleaner\LogCleaner;
use JiordiViera\LaravelLogCleaner\LogStatsCollector;
use Throwable;

/**
 * Dashboard home: read-only health overview of the log directory.
 */
final class OverviewController
{
    private const CACHE_KEY = 'log-cleaner:dashboard:overview';

    public function __invoke(Request $request): Response
    {
        $ttl = (int) Config::get('log-cleaner.dashboard.stats_cache_seconds', 60);

        $cached = false;

        try {
            if ($request->boolean('refresh') || $ttl <= 0) {
                Cache::forget(self::CACHE_KEY);
            }

            $cached = $ttl > 0 && Cache::has(self::CACHE_KEY);

            /** @var array{report: array<string, mixed>|null, scannedAt: string} $scan */
            $scan = $ttl > 0
                ? Cache::remember(self::CACHE_KEY, $ttl, fn (): array => $this->scan())
                : $this->scan();
        } catch (Throwable) {
            // A broken cache store must not break the dashboard: scan directly.
            $cached = false;
            $scan = $this->scan();
        }

        $report = $scan['report'];

        $limitMb = Config::get('log-cleaner.health.max_size_mb');
        $limitBytes = is_numeric($limitMb) && (float) $limitMb > 0
            ? (int) round((float) $limitMb * 1024 * 1024)
            : null;

        $totalBytes = $report['totals']['size_bytes'] ?? 0;

        return Inertia::render('Overview', [
            'directory' => storage_path(LogCleaner::LOG_DIRECTORY),
            'report' => $report,
            'health' => [
                'limitBytes' => $limitBytes,
                'exceeded' => $limitBytes !== null && $totalBytes > $limitBytes,
            ],
            'schedule' => [
                'enabled' => (bool) Config::get('log-cleaner.schedule.enabled', false),
                'cron' => (string) Config::get('log-cleaner.schedule.cron', '0 2 * * *'),
                'options' => (array) Config::get('log-cleaner.schedule.options', []),
            ],
            'scannedAt' => $scan['scannedAt'],
            'cached' => $cached,
        ]);
    }

    /**
     * @return array{report: array<string, mixed>|null, scannedAt: string}
     */
    private function scan(): array
    {
        try {
            $report = (new LogStatsCollector)->collect();
        } catch (NoLogFilesException) {
            $report = null;
        }

        return ['report' => $report, 'scannedAt' => now()->toDateTimeString()];
    }
}
