<?php

namespace App\Services\Finance;

use App\Models\RentInvoice;
use App\Models\RentPayment;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Stripe\Checkout\Session;
use Stripe\Stripe;

class RentStripeCheckoutService
{
    public function __construct(
        private readonly RentFinanceService $finance
    ) {
    }

    public function isConfigured(): bool
    {
        return $this->secret() !== '';
    }

    /**
     * @return array{rent: float, fee: float, total: float, fee_label: string}
     */
    public function feeBreakdown(float $rent): array
    {
        $rent = round(max(0, $rent), 2);
        $percent = (float) config('services.stripe.rent.fee_percent', 0);
        $fixed = (float) config('services.stripe.rent.fee_fixed', 0);
        $fee = round(($rent * $percent / 100) + $fixed, 2);
        if ($fee < 0) {
            $fee = 0.0;
        }

        return [
            'rent' => $rent,
            'fee' => $fee,
            'total' => round($rent + $fee, 2),
            'fee_label' => (string) config('services.stripe.rent.fee_label', 'Card payment fee'),
        ];
    }

    public function createCheckoutUrl(User $tenant, RentInvoice $invoice): string
    {
        if (! $this->isConfigured()) {
            throw ValidationException::withMessages([
                'invoice' => 'Card payments are not available yet. Pay your landlord by bank transfer.',
            ]);
        }

        if ((int) $invoice->tenant_user_id !== (int) $tenant->id) {
            throw ValidationException::withMessages([
                'invoice' => 'That invoice is not on your account.',
            ]);
        }

        if (! $invoice->isOpen()) {
            throw ValidationException::withMessages([
                'invoice' => 'This invoice is already paid.',
            ]);
        }

        $rent = round((float) $invoice->balance, 2);
        $breakdown = $this->feeBreakdown($rent);
        $currency = strtolower((string) config('services.stripe.rent.currency', 'gbp'));
        $property = $invoice->property?->full_address
            ?: ($invoice->property?->line_1 ?: 'your home');

        $metadata = [
            'type' => 'rent_payment',
            'account_id' => (string) $invoice->account_id,
            'rent_invoice_id' => (string) $invoice->id,
            'tenant_user_id' => (string) $tenant->id,
            'rent_amount' => number_format($breakdown['rent'], 2, '.', ''),
            'fee_amount' => number_format($breakdown['fee'], 2, '.', ''),
        ];

        $lineItems = [
            [
                'price_data' => [
                    'currency' => $currency,
                    'unit_amount' => $this->toMinor($breakdown['rent']),
                    'product_data' => [
                        'name' => 'Rent '.$invoice->invoice_no,
                        'description' => $property,
                    ],
                ],
                'quantity' => 1,
            ],
        ];

        if ($breakdown['fee'] >= 0.01) {
            $lineItems[] = [
                'price_data' => [
                    'currency' => $currency,
                    'unit_amount' => $this->toMinor($breakdown['fee']),
                    'product_data' => [
                        'name' => $breakdown['fee_label'],
                    ],
                ],
                'quantity' => 1,
            ];
        }

        $sessionData = [
            'mode' => 'payment',
            'customer_email' => $tenant->email,
            'line_items' => $lineItems,
            'success_url' => route('tenant.rent.paid').'?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => route('tenant.rent', ['checkout' => 'cancelled']),
            'client_reference_id' => 'rent_invoice_'.$invoice->id,
            'metadata' => $metadata,
            'payment_intent_data' => [
                'metadata' => $metadata,
                'description' => 'Rent '.$invoice->invoice_no,
            ],
        ];

        $connected = $this->connectedAccountId();
        if ($connected && $breakdown['fee'] >= 0.01) {
            $sessionData['payment_intent_data']['application_fee_amount'] = $this->toMinor($breakdown['fee']);
        }

        $this->configureStripe();

        $session = Session::create($sessionData, $this->requestOptions());

        if (! $session->url) {
            throw new RuntimeException('Stripe did not return a checkout URL.');
        }

        return (string) $session->url;
    }

    public function fulfillCheckoutSessionId(string $sessionId): ?RentPayment
    {
        $this->configureStripe();
        $session = Session::retrieve($sessionId, [], $this->requestOptions());

        return $this->fulfillSession($session);
    }

    public function fulfillSession(mixed $session): ?RentPayment
    {
        if (! $session) {
            return null;
        }

        $metadata = $this->metadataArray($session->metadata ?? []);
        if (($metadata['type'] ?? '') !== 'rent_payment') {
            return null;
        }

        $paymentStatus = (string) ($session->payment_status ?? '');
        if (! in_array($paymentStatus, ['paid', 'no_payment_required'], true)) {
            return null;
        }

        $invoiceId = (int) ($metadata['rent_invoice_id'] ?? 0);
        $tenantUserId = (int) ($metadata['tenant_user_id'] ?? 0);
        $sessionId = (string) ($session->id ?? '');

        if ($invoiceId < 1 || $tenantUserId < 1 || $sessionId === '') {
            Log::warning('Rent Stripe session missing invoice metadata', [
                'checkout_session_id' => $sessionId,
            ]);

            return null;
        }

        $existing = RentPayment::query()
            ->where('stripe_checkout_session_id', $sessionId)
            ->first();
        if ($existing) {
            return $existing;
        }

        $invoice = RentInvoice::query()->find($invoiceId);
        if (! $invoice || (int) $invoice->tenant_user_id !== $tenantUserId) {
            Log::warning('Rent Stripe session did not match a local invoice', [
                'checkout_session_id' => $sessionId,
                'rent_invoice_id' => $invoiceId,
            ]);

            return null;
        }

        $rent = round((float) ($metadata['rent_amount'] ?? $invoice->balance), 2);
        $fee = round((float) ($metadata['fee_amount'] ?? 0), 2);
        $apply = min($rent, round((float) $invoice->balance, 2));

        if ($apply < 0.01) {
            return RentPayment::query()
                ->where('rent_invoice_id', $invoice->id)
                ->where('stripe_checkout_session_id', $sessionId)
                ->first();
        }

        $paymentIntent = $session->payment_intent ?? null;
        $paymentIntentId = is_string($paymentIntent)
            ? $paymentIntent
            : (is_object($paymentIntent) ? (string) ($paymentIntent->id ?? '') : '');

        try {
            return $this->finance->recordPayment($invoice, [
                'amount' => $apply,
                'paid_at' => now()->toDateString(),
                'method' => RentPayment::METHOD_CARD,
                'reference' => 'Stripe '.$sessionId,
                'fee_amount' => $fee,
                'stripe_checkout_session_id' => $sessionId,
                'stripe_payment_intent_id' => $paymentIntentId ?: null,
            ], $tenantUserId);
        } catch (ValidationException $exception) {
            $existing = RentPayment::query()
                ->where('stripe_checkout_session_id', $sessionId)
                ->first();
            if ($existing) {
                return $existing;
            }

            Log::warning('Rent Stripe fulfill rejected', [
                'checkout_session_id' => $sessionId,
                'error' => $exception->getMessage(),
            ]);

            return null;
        } catch (UniqueConstraintViolationException) {
            return RentPayment::query()
                ->where('stripe_checkout_session_id', $sessionId)
                ->first();
        }
    }

    private function configureStripe(): void
    {
        $secret = $this->secret();
        if ($secret === '') {
            throw new RuntimeException('Rent Stripe secret is not configured. Set STRIPE_RENT_SECRET (client-money) or STRIPE_SECRET.');
        }

        Stripe::setApiKey($secret);
    }

    /**
     * @return array<string, string>
     */
    private function requestOptions(): array
    {
        $connected = $this->connectedAccountId();

        return $connected ? ['stripe_account' => $connected] : [];
    }

    private function secret(): string
    {
        if ($this->connectedAccountId()) {
            return (string) (config('services.stripe.secret') ?: '');
        }

        return (string) (config('services.stripe.rent.secret') ?: config('services.stripe.secret') ?: '');
    }

    private function connectedAccountId(): string
    {
        return trim((string) config('services.stripe.rent.connected_account_id', ''));
    }

    private function toMinor(float $pounds): int
    {
        return (int) round($pounds * 100);
    }

    /**
     * @param  mixed  $metadata
     * @return array<string, string>
     */
    private function metadataArray(mixed $metadata): array
    {
        if (is_array($metadata)) {
            return array_map(static fn ($value) => (string) $value, $metadata);
        }

        if (is_object($metadata) && method_exists($metadata, 'toArray')) {
            return array_map(static fn ($value) => (string) $value, $metadata->toArray());
        }

        if (is_object($metadata)) {
            $out = [];
            foreach ((array) $metadata as $key => $value) {
                if (is_string($key) && ! str_starts_with($key, "\0")) {
                    $out[$key] = (string) $value;
                }
            }

            return $out;
        }

        return [];
    }
}
