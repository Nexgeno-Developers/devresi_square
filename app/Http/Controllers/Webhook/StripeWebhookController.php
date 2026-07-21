<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Services\Saas\StripeWebhookService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;
use UnexpectedValueException;

class StripeWebhookController extends Controller
{
    public function handle(Request $request, StripeWebhookService $service)
    {
        $webhookSecret = config('services.stripe.webhook_secret');
        if (! $webhookSecret) {
            return response('Stripe webhook secret is not configured.', 500);
        }

        $signature = $request->header('Stripe-Signature');
        if (! $signature) {
            return response('Stripe signature header is missing.', 400);
        }

        try {
            $event = Webhook::constructEvent($request->getContent(), $signature, $webhookSecret);
        } catch (UnexpectedValueException) {
            Log::warning('Stripe webhook rejected: invalid payload');
            return response('Invalid Stripe webhook payload.', 400);
        } catch (SignatureVerificationException) {
            Log::warning('Stripe webhook rejected: invalid signature');
            return response('Invalid Stripe webhook signature.', 400);
        }

        try {
            $service->handle($event);
        } catch (\Throwable $exception) {
            Log::error('Stripe webhook processing failed', [
                'event_id' => $event->id ?? null,
                'event_type' => $event->type ?? null,
                'error' => $exception->getMessage(),
            ]);

            throw $exception;
        }

        Log::info('Stripe webhook processed', [
            'event_id' => $event->id ?? null,
            'event_type' => $event->type ?? null,
        ]);

        return response('Stripe webhook handled.', 200);
    }
}
