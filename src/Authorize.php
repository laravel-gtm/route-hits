<?php

namespace LaravelGtm\RouteHits;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class Authorize
{
    public function handle(Request $request, Closure $next)
    {
        abort_unless(app()->environment('local') || Gate::check('viewRouteHits'), 403);

        return $next($request);
    }
}
