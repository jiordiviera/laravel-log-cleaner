<?php

declare(strict_types=1);

namespace JiordiViera\LaravelLogCleaner\Dashboard\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

/**
 * Lets a request through only when the `viewLogCleaner` gate allows it.
 */
final class Authorize
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(Gate::check('viewLogCleaner', [$request->user()]), 403);

        return $next($request);
    }
}
