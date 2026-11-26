<?php

namespace App\Services\Contractors;

use Illuminate\Validation\ValidationException;
use Stripe\Checkout\Session;
use Stripe\Exception\ApiErrorException;
use Stripe\Exception\InvalidRequestException;

/**
 * Keeps one payable Checkout per contractor invoice, the same rule rent payments follow.
 * Used by services that provide configureStripe() and fulfillSession().
 */
trait ClosesPreviousCheckout
{
    protected function createSession(array $sessionData): mixed
    {
        return Session::create($sessionData);
    }

    protected function retrieveSession(string $sessionId): mixed
    {
        return Session::retrieve($sessionId);
    }

    protected function expireSession(string $sessionId): void
    {
        Session::retrieve($sessionId)->expire();
    }

    /**
     * Expire the invoice's earlier Checkout, or record it when Stripe already took the
     * payment, before another one is created. Otherwise two tabs can charge the invoice twice.
     *
     * @param  \Closure(): bool  $stillAwaitingPayment
     */
    private function closePreviousCheckout(string $previousId, \Closure $stillAwaitingPayment): void
    {
        if ($previousId === '') {
            return;
        }

        $this->configureStripe();

        try {
            $previous = $this->retrieveSession($previousId);

            if (($previous->status ?? '') === 'open') {
                try {
                    $this->expireSession($previousId);

                    return;
                } catch (ApiErrorException $exception) {
                    // Stripe refuses to expire a Checkout that completed meanwhile.
                    report($exception);
                    $previous = $this->retrieveSession($previousId);
                }
            }
        } catch (InvalidRequestException $exception) {
            if ($exception->getStripeCode() === 'resource_missing') {
                return;
            }

            $this->refuseUnchecked($exception);
        } catch (ApiErrorException $exception) {
            $this->refuseUnchecked($exception);
        }

        $paid = in_array((string) ($previous->payment_status ?? ''), ['paid', 'no_payment_required'], true);
        if ($paid) {
            $this->fulfillSession($previous);
        }

        if (! $stillAwaitingPayment()) {
            throw ValidationException::withMessages([
                'invoice' => 'This invoice is not waiting for payment.',
            ]);
        }

        if ($paid || ($previous->status ?? '') === 'open') {
            throw ValidationException::withMessages([
                'invoice' => 'Your last card payment is still being processed. Try again in a minute.',
            ]);
        }
    }

    private function refuseUnchecked(\Throwable $exception): never
    {
        report($exception);

        throw ValidationException::withMessages([
            'invoice' => 'We could not check your last card payment. Try again in a minute.',
        ]);
    }
}
