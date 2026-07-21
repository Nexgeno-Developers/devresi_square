<?php

namespace App\Http\Controllers\Backend\Saas;

use App\Http\Controllers\Controller;
use Closure;
use Illuminate\Http\Request;

abstract class BaseSaasController extends Controller
{
    public function __construct()
    {
        $this->middleware(function (Request $request, Closure $next) {
            abort_unless($request->user()?->hasRole('Super Admin'), 403);

            return $next($request);
        });
    }

    protected function normaliseCode(Request $request): void
    {
        $code = strtolower(trim((string) $request->input('code')));
        $code = preg_replace('/[^a-z0-9]+/', '_', $code) ?: '';
        $code = trim(preg_replace('/_+/', '_', $code) ?: '', '_');

        $request->merge([
            'code' => $code,
            'currency' => strtoupper($request->input('currency', 'GBP') ?: 'GBP'),
        ]);
    }

    protected function priceToMinor(mixed $value): int
    {
        return (int) round(((float) $value) * 100);
    }
}
