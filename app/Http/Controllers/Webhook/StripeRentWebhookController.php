<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Services\Finance\RentStripeCheckoutService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;
use UnexpectedValueException;

class StripeRentWebhookController extends Controller
{
    public function handle(Request $request, RentStripeCheckoutService $rentCheckout)
    {
        $webhookSecret = config('services.stripe.rent.webhook_secret')
            ?: config('services.stripe.webhook_secret');
        if (! $webhookSecret) {
            return response('Rent Stripe webhook secret is not configured.', 500);
        }

        $signature = $request->header('Stripe-Signature');
        if (! $signature) {
            return response('Stripe signature header is missing.', 400);
        }

        try {
            $event = Webhook::constructEvent($request->getContent(), $signature, $webhookSecret);
        } catch (UnexpectedValueException) {
            Log::warning('Rent Stripe webhook rejected: invalid payload');

            return response('Invalid Stripe webhook payload.', 400);
        } catch (SignatureVerificationException) {
            Log::warning('Rent Stripe webhook rejected: invalid signature');

            return response('Invalid Stripe webhook signature.', 400);
        }

        $type = $event->type ?? null;
        if (! in_array($type, ['checkout.session.completed', 'checkout.session.async_payment_succeeded'], true)) {
            return response('Rent Stripe webhook ignored.', 200);
        }

        try {
            $rentCheckout->fulfillSession($event->data->object ?? null);
        } catch (\Throwable $exception) {
            Log::error('Rent Stripe webhook processing failed', [
                'event_id' => $event->id ?? null,
                'event_type' => $type,
                'error' => $exception->getMessage(),
            ]);

            throw $exception;
        }

        Log::info('Rent Stripe webhook processed', [
            'event_id' => $event->id ?? null,
            'event_type' => $type,
        ]);

        return response('Rent Stripe webhook handled.', 200);
    }
}
