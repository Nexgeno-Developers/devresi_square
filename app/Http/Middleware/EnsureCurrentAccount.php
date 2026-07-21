<?php

namespace App\Http\Middleware;

use App\Services\Saas\CurrentAccountService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCurrentAccount
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        if ($user->hasRole('Super Admin')) {
            return $next($request);
        }

        if (! app(CurrentAccountService::class)->current($user)) {
            abort(403, 'No active SaaS account found for this user.');
        }

        return $next($request);
    }
}
