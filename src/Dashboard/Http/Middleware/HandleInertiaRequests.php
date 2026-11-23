<?php

declare(strict_types=1);

namespace JiordiViera\LaravelLogCleaner\Dashboard\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Gate;
use Inertia\Middleware;
use JiordiViera\LaravelLogCleaner\Dashboard\DashboardAssets;

/**
 * Inertia middleware scoped to the dashboard routes.
 *
 * It uses the package's own root view, so the host application does not need
 * to use Inertia itself, and does not affect the host's own Inertia pages.
 */
final class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'log-cleaner::app';

    public function version(Request $request): ?string
    {
        return DashboardAssets::version();
    }

    /**
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return array_merge(parent::share($request), [
            'logCleaner' => [
                'version' => DashboardAssets::PACKAGE_VERSION,
                'repository' => DashboardAssets::REPOSITORY_URL,
                'backUrl' => Config::get('log-cleaner.dashboard.back_url'),
                'environment' => app()->environment(),
                'theme' => Config::get('log-cleaner.dashboard.theme', 'paper'),
                'font' => Config::get('log-cleaner.dashboard.font', 'jetbrains'),
                'canManage' => Gate::check('manageLogCleaner', [$request->user()]),
            ],
        ]);
    }
}
