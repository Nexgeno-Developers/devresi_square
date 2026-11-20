<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Backend pages stamp every form post and AJAX call with the account they were loaded in
 * (_account_id field / X-Account-Id header, see backend/partials/account-stamp).
 * If the person switched account in another tab since, the save is refused instead of
 * quietly landing in the other account.
 */
class EnsureFormAccountMatches
{
    public const FIELD = '_account_id';

    public const HEADER = 'X-Account-Id';

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethodSafe() || ! $request->user()) {
            return $next($request);
        }

        $stamped = $request->input(self::FIELD, $request->header(self::HEADER));
        if ($stamped === null || $stamped === '' || ! ctype_digit((string) $stamped)) {
            return $next($request);
        }

        $current = function_exists('current_account_id') ? current_account_id() : null;
        if ($current === null || (int) $stamped === (int) $current) {
            return $next($request);
        }

        $message = 'You switched to a different account in another tab, so this was not saved. Reload the page and try again.';

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['message' => $message], 409);
        }

        flash($message)->error();

        return back()->withInput($request->except(['password', 'password_confirmation', '_token', self::FIELD]));
    }
}
