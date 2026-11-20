<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

/**
 * Stops a form being processed twice (double click, refresh, retry on a slow connection).
 * Forms include <x-submit-once-token />; each token is accepted once for 30 minutes.
 * Requests without a token pass through unchanged.
 */
class PreventDuplicateSubmission
{
    public const FIELD = '_submit_token';

    public function handle(Request $request, Closure $next): Response
    {
        $token = (string) $request->input(self::FIELD, '');

        if ($token === '' || ! preg_match('/^[A-Za-z0-9-]{16,64}$/', $token)) {
            return $next($request);
        }

        $key = 'submit-once:'.($request->user()?->id ?? 'guest').':'.$token;

        if (! Cache::add($key, true, now()->addMinutes(30))) {
            $message = 'This was already submitted, so it was not saved a second time.';

            if ($request->expectsJson()) {
                return response()->json(['message' => $message], 409);
            }

            flash($message)->warning();

            return back();
        }

        return $next($request);
    }
}
