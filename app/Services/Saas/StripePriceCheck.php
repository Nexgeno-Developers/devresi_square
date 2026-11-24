<?php

namespace App\Services\Saas;

use Stripe\Exception\InvalidRequestException;
use Stripe\Price;
use Stripe\Stripe;

/**
 * When a plan or add-on names a Stripe price, checkout charges that Stripe price, not the price typed into
 * the app. This checks the two agree before the plan or add-on is saved.
 */
class StripePriceCheck
{
    /**
     * @param  'month'|'year'  $interval
     * @return string|null why the price does not fit, or null when it does
     */
    public function problem(string $priceId, int $expectedMinor, string $currency, string $interval): ?string
    {
        $secret = config('services.stripe.secret');
        if (! $secret) {
            return 'Stripe is not configured, so this price ID cannot be checked.';
        }
        Stripe::setApiKey($secret);

        try {
            $price = Price::retrieve($priceId);
        } catch (InvalidRequestException $exception) {
            return $exception->getStripeCode() === 'resource_missing'
                ? 'Stripe has no price '.$priceId.' (check it belongs to this Stripe account and mode).'
                : 'Stripe rejected '.$priceId.': '.$exception->getMessage();
        } catch (\Throwable $exception) {
            return 'Stripe could not be reached to check '.$priceId.'. Try again.';
        }

        $label = $interval === 'year' ? 'annual' : 'monthly';
        if (! $price->active) {
            return 'Stripe price '.$priceId.' is archived.';
        }
        if (($price->recurring->interval ?? null) !== $interval || (int) ($price->recurring->interval_count ?? 1) !== 1) {
            return 'Stripe price '.$priceId.' does not bill once a '.$interval.', so it cannot be the '.$label.' price.';
        }
        if (strtoupper((string) $price->currency) !== strtoupper($currency)) {
            return 'Stripe price '.$priceId.' is in '.strtoupper((string) $price->currency).', not '.strtoupper($currency).'.';
        }
        if ((int) $price->unit_amount !== $expectedMinor) {
            return 'Stripe price '.$priceId.' charges '.number_format($price->unit_amount / 100, 2).', but the '.$label.' price here is '.number_format($expectedMinor / 100, 2).'. Customers would be charged the Stripe amount.';
        }

        return null;
    }
}
