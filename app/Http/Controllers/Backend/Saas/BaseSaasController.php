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

    /**
     * Checkout charges the Stripe price when one is set, so it must match the price shown in the app.
     *
     * @param  array<string, mixed>  $data  validated data with monthly_price, annual_price, currency and the two Stripe price ids
     */
    protected function assertStripePricesMatch(array $data): void
    {
        $check = app(\App\Services\Saas\StripePriceCheck::class);
        $errors = [];
        foreach (['stripe_monthly_price_id' => ['monthly_price', 'month'], 'stripe_annual_price_id' => ['annual_price', 'year']] as $field => [$priceField, $interval]) {
            if (blank($data[$field] ?? null)) {
                continue;
            }
            if ($problem = $check->problem((string) $data[$field], $this->priceToMinor($data[$priceField]), (string) $data['currency'], $interval)) {
                $errors[$field] = $problem;
            }
        }
        if ($errors) {
            throw \Illuminate\Validation\ValidationException::withMessages($errors);
        }
    }

    protected function priceToMinor(mixed $value): int
    {
        return (int) round(((float) $value) * 100);
    }
}
