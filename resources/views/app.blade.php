<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Log Cleaner</title>
    @if (\JiordiViera\LaravelLogCleaner\Dashboard\DashboardAssets::published())
        <link rel="stylesheet" href="{{ asset('vendor/log-cleaner/app.css') }}?v={{ \JiordiViera\LaravelLogCleaner\Dashboard\DashboardAssets::version() }}">
    @endif
</head>
<body>
    @if (\JiordiViera\LaravelLogCleaner\Dashboard\DashboardAssets::published())
        @if (\JiordiViera\LaravelLogCleaner\Dashboard\DashboardAssets::outdated())
            <p style="margin:0;padding:8px 16px;background:#F2DED6;color:#A3261C;font:13px monospace">
                The dashboard assets are out of date. Run: php artisan vendor:publish --tag=log-cleaner-assets --force
            </p>
        @endif
        @inertia
        <script type="module" src="{{ asset('vendor/log-cleaner/app.js') }}?v={{ \JiordiViera\LaravelLogCleaner\Dashboard\DashboardAssets::version() }}"></script>
    @else
        <pre style="margin:32px;font:14px/1.6 monospace">Log Cleaner dashboard assets are not published.

Run: php artisan vendor:publish --tag=log-cleaner-assets</pre>
    @endif
</body>
</html>
