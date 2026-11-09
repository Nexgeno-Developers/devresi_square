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

        $accountStatus = (string) \App\Models\Account::query()->whereKey($invoice->account_id)->value('status');
        if (in_array($accountStatus, ['suspended', 'cancelled'], true)) {
            throw ValidationException::withMessages([
                'invoice' => 'Card payments are paused because this account is '.$accountStatus.'. Bank transfer is still available.',
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
            'success_url' => route('tenant.rent.paid', ['invoice' => $invoice->id]),
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

        // Only one Checkout per invoice may be payable. Otherwise a second tab
        // or a second click can take the rent twice.
        $this->closePreviousCheckout($invoice);

        $session = $this->createSession($sessionData);

        if (! $session->url) {
            throw new RuntimeException('Stripe did not return a checkout URL.');
        }

        $invoice->forceFill([
            'stripe_checkout_session_id' => (string) $session->id,
        ])->save();

        return (string) $session->url;
    }

    /**
     * Expire the invoice's earlier Checkout, or record it when Stripe already
     * took the payment, before a new Checkout is created.
     */
    private function closePreviousCheckout(RentInvoice $invoice): void
    {
        $previousId = trim((string) $invoice->stripe_checkout_session_id);
        if ($previousId === '') {
            return;
        }

        $previous = $this->retrieveSession($previousId);

        if (($previous->status ?? '') === 'open') {
            try {
                $this->expireSession($previousId);

                return;
            } catch (\Throwable $exception) {
                // Stripe refuses to expire a Checkout that completed meanwhile.
                report($exception);
                $previous = $this->retrieveSession($previousId);
            }
        }

        if (in_array((string) ($previous->payment_status ?? ''), ['paid', 'no_payment_required'], true)) {
            $this->fulfillSession($previous);
        }

        if (! $invoice->refresh()->isOpen()) {
            throw ValidationException::withMessages([
                'invoice' => 'This invoice is already paid.',
            ]);
        }

        if (($previous->status ?? '') === 'open') {
            throw ValidationException::withMessages([
                'invoice' => 'Your last card payment is still being processed. Try again in a minute.',
            ]);
        }
    }

    protected function createSession(array $sessionData): mixed
    {
        return Session::create($sessionData, $this->requestOptions());
    }

    protected function retrieveSession(string $sessionId): mixed
    {
        return Session::retrieve($sessionId, [], $this->requestOptions());
    }

    protected function expireSession(string $sessionId): void
    {
        Session::retrieve($sessionId, [], $this->requestOptions())->expire([], $this->requestOptions());
    }

    /**
     * Mark open invoices paid when Stripe already collected the card,
     * even if the browser never returned from Checkout.
     *
     * @param  iterable<int, RentInvoice>  $invoices
     */
    public function reconcileOpenCheckouts(iterable $invoices): void
    {
        foreach ($invoices as $invoice) {
            if (! $invoice instanceof RentInvoice || ! $invoice->isOpen()) {
                continue;
            }

            $sessionId = trim((string) $invoice->stripe_checkout_session_id);
            if ($sessionId === '' || ! $this->isConfigured()) {
                continue;
            }

            try {
                $this->fulfillCheckoutSessionId($sessionId);
            } catch (\Throwable $exception) {
                report($exception);
            }
        }
    }

    public function fulfillCheckoutSessionId(string $sessionId): ?RentPayment
    {
        $this->configureStripe();

        return $this->fulfillSession($this->retrieveSession($sessionId));
    }

    public function noteFailed(mixed $session): void
    {
        if (! $session) {
            return;
        }

        $metadata = $this->metadataArray($session->metadata ?? []);
        if (($metadata['type'] ?? '') !== 'rent_payment') {
            return;
        }

        $invoiceId = (int) ($metadata['rent_invoice_id'] ?? 0);
        $invoice = $invoiceId > 0 ? RentInvoice::query()->find($invoiceId) : null;
        if (! $invoice || ! $invoice->isOpen()) {
            return;
        }

        app(RentInvoiceNotifier::class)->paymentFailed($invoice);
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

        $paymentIntent = $session->payment_intent ?? null;
        $paymentIntentId = is_string($paymentIntent)
            ? $paymentIntent
            : (is_object($paymentIntent) ? (string) ($paymentIntent->id ?? '') : '');

        $rent = round((float) ($metadata['rent_amount'] ?? $invoice->balance), 2);
        $fee = round((float) ($metadata['fee_amount'] ?? 0), 2);
        $apply = $invoice->isOpen() ? min($rent, round((float) $invoice->balance, 2)) : 0.0;

        if ($apply < 0.01) {
            // Stripe took this card payment but the invoice is already settled
            // by another payment. Nothing can be applied, so it must be
            // refunded by hand; it must not disappear silently.
            Log::error('Rent card payment received for an invoice that is already settled', [
                'checkout_session_id' => $sessionId,
                'payment_intent_id' => $paymentIntentId ?: null,
                'rent_invoice_id' => $invoice->id,
                'account_id' => $invoice->account_id,
                'tenant_user_id' => $tenantUserId,
                'amount' => round($rent + $fee, 2),
            ]);

            return null;
        }

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
