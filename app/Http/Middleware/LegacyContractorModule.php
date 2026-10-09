<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/** The old contractor module stays in the codebase with its data; it is only reachable when the flag is on. */
class LegacyContractorModule
{
    public function handle(Request $request, Closure $next)
    {
        abort_unless(config('contractor_marketplace.legacy_module_enabled'), 404);

        return $next($request);
    }
}
