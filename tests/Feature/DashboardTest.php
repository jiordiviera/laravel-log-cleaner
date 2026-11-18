<?php

declare(strict_types=1);

namespace JiordiViera\LaravelLogCleaner\Tests\Feature;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia as Assert;
use JiordiViera\LaravelLogCleaner\Tests\TestCase;

/**
 * Dashboard enabled: routes, authorization and the Overview page (#58, #59).
 */
class DashboardTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        $app['config']->set('log-cleaner.dashboard.enabled', true);
        $app['config']->set('inertia.testing.ensure_pages_exist', false);
        $app['config']->set('cache.default', 'array');
    }

    protected function setUp(): void
    {
        parent::setUp();

        File::ensureDirectoryExists(storage_path('logs'));
        File::delete(File::files(storage_path('logs')));
    }

    protected function tearDown(): void
    {
        File::delete(File::files(storage_path('logs')));

        parent::tearDown();
    }

    private function allow(string $ability = 'viewLogCleaner'): void
    {
        Gate::define($ability, fn ($user = null) => true);
    }

    public function test_it_registers_the_overview_route_under_the_configured_path(): void
    {
        $this->assertTrue(Route::has('log-cleaner.overview'));
        $this->assertSame(url('log-cleaner'), route('log-cleaner.overview'));
    }

    public function test_it_denies_access_outside_the_local_environment_by_default(): void
    {
        $this->get('/log-cleaner')->assertForbidden();
    }

    public function test_it_renders_the_overview_when_the_gate_allows_it(): void
    {
        $this->allow();
        File::put(storage_path('logs/laravel.log'), implode(PHP_EOL, [
            '[2026-01-05 10:00:00] local.ERROR: Boom',
            '#0 /app/Foo.php(12): bar()',
            '[2026-01-06 10:00:00] local.INFO: Ok',
        ]).PHP_EOL);

        $this->get('/log-cleaner')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Overview')
                ->where('report.totals.files', 1)
                ->where('report.totals.records', 2)
                ->where('report.totals.levels.ERROR', 1)
                ->where('report.files.0.file', 'laravel.log')
                ->where('health.exceeded', false)
                ->where('schedule.enabled', false)
                ->where('logCleaner.canManage', false)
                ->where('logCleaner.theme', 'paper'));
    }

    public function test_it_flags_logs_over_the_health_limit(): void
    {
        $this->allow();
        config(['log-cleaner.health.max_size_mb' => 0.001]);
        File::put(storage_path('logs/laravel.log'), str_repeat('x', 4096));

        $this->get('/log-cleaner')
            ->assertInertia(fn (Assert $page) => $page
                ->where('health.limitBytes', 1049)
                ->where('health.exceeded', true));
    }

    public function test_it_shows_an_empty_state_without_log_files(): void
    {
        $this->allow();

        $this->get('/log-cleaner')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Overview')
                ->where('report', null));
    }

    public function test_it_shares_the_manage_ability(): void
    {
        $this->allow();
        $this->allow('manageLogCleaner');

        $this->get('/log-cleaner')
            ->assertInertia(fn (Assert $page) => $page->where('logCleaner.canManage', true));
    }

    public function test_it_explains_how_to_publish_missing_assets(): void
    {
        $this->allow();

        $this->get('/log-cleaner')
            ->assertOk()
            ->assertSee('php artisan vendor:publish --tag=log-cleaner-assets');
    }

    public function test_the_overview_never_writes_to_the_log_directory(): void
    {
        $this->allow();
        $path = storage_path('logs/laravel.log');
        File::put($path, '[2026-01-05 10:00:00] local.ERROR: Boom'.PHP_EOL);
        $before = [File::get($path), filemtime($path), count(File::files(storage_path('logs')))];

        $this->get('/log-cleaner')->assertOk();
        clearstatcache();

        $this->assertSame($before, [File::get($path), filemtime($path), count(File::files(storage_path('logs')))]);
    }

    public function test_it_caches_the_scan_until_refresh_is_requested(): void
    {
        $this->allow();
        $path = storage_path('logs/laravel.log');
        File::put($path, '[2026-01-05 10:00:00] local.ERROR: Boom'.PHP_EOL);

        $this->get('/log-cleaner')->assertInertia(fn (Assert $page) => $page
            ->where('cached', false)
            ->where('report.totals.records', 1));

        File::append($path, '[2026-01-06 10:00:00] local.ERROR: Again'.PHP_EOL);

        $this->get('/log-cleaner')->assertInertia(fn (Assert $page) => $page
            ->where('cached', true)
            ->where('report.totals.records', 1));

        $this->get('/log-cleaner?refresh=1')->assertInertia(fn (Assert $page) => $page
            ->where('cached', false)
            ->where('report.totals.records', 2));
    }

    public function test_a_broken_cache_store_falls_back_to_a_direct_scan(): void
    {
        $this->allow();
        config(['cache.default' => 'database']); // no cache table in the test database
        File::put(storage_path('logs/laravel.log'), '[2026-01-05 10:00:00] local.ERROR: Boom'.PHP_EOL);

        $this->get('/log-cleaner')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('cached', false)
                ->where('report.totals.records', 1));
    }
}
