<?php

namespace App\Services\Contractors;

use App\Models\Account;
use App\Models\ComplianceJobInvoice;
use App\Models\ComplianceJobPayment;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Stripe\Checkout\Session;
use Stripe\Stripe;

/**
 * Landlord card payments for a finished compliance check.
 * The charge lands in Resisquare's operating Stripe account. Commission stays
 * with Resisquare. Staff pay the contractor's share to the bank details on the invoice.
 * The split uses the same settings as a repair invoice.
 */
class ComplianceJobPaymentService
{
    public function __construct(
        private readonly RepairJobPaymentService $repairs,
        private readonly ContractorMail $mail,
    ) {}

    public function isConfigured(): bool
    {
        return $this->repairs->isConfigured();
    }

    /**
     * @return array{gross: float, percent: float, bearer: string, commission: float, landlord_amount: float, contractor_amount: float}
     */
    public function breakdown(float $gross, ?float $percent = null, ?string $bearer = null): array
    {
        return $this->repairs->breakdown($gross, $percent, $bearer);
    }

    /**
     * Amounts to show. A paid row keeps the snapshot from checkout.
     *
     * @return array{gross: float, percent: float, bearer: string, commission: float, landlord_amount: float, contractor_amount: float}
     */
    public function chargeFor(ComplianceJobInvoice $invoice): array
    {
        $payment = $invoice->relationLoaded('cardPayment') ? $invoice->cardPayment : $invoice->cardPayment()->first();
        if ($payment && $payment->isPaid()) {
            return [
                'gross' => (float) $payment->gross_amount,
                'percent' => (float) $payment->commission_percent,
                'bearer' => (string) $payment->commission_bearer,
                'commission' => (float) $payment->commission_amount,
                'landlord_amount' => (float) $payment->landlord_amount,
                'contractor_amount' => (float) $payment->contractor_amount,
            ];
        }

        return $this->breakdown((float) $invoice->gross_amount);
    }

    public function createCheckoutUrl(User $landlord, ComplianceJobInvoice $invoice): string
    {
        if (! $this->isConfigured()) {
            throw ValidationException::withMessages([
                'invoice' => 'Card payments are not available yet.',
            ]);
        }

        $invoice->loadMissing('offer.booking.property', 'offer.booking.type');
        if ($invoice->status !== ComplianceJobInvoice::ISSUED) {
            throw ValidationException::withMessages([
                'invoice' => 'This invoice is not waiting for payment.',
            ]);
        }

        $accountStatus = (string) Account::query()->whereKey($invoice->account_id)->value('status');
        if (in_array($accountStatus, ['suspended', 'cancelled'], true)) {
            throw ValidationException::withMessages([
                'invoice' => 'Card payments are paused because this account is '.$accountStatus.'.',
            ]);
        }

        $breakdown = $this->breakdown((float) $invoice->gross_amount);
        if ($breakdown['landlord_amount'] < 0.01) {
            throw ValidationException::withMessages([
                'invoice' => 'There is nothing to pay on this invoice.',
            ]);
        }

        $payment = DB::transaction(function () use ($invoice, $landlord, $breakdown) {
            $locked = ComplianceJobInvoice::query()->whereKey($invoice->id)->lockForUpdate()->first();
            $existing = ComplianceJobPayment::query()
                ->where('compliance_job_invoice_id', $invoice->id)
                ->lockForUpdate()
                ->first();
            if (! $locked || $locked->status !== ComplianceJobInvoice::ISSUED || ($existing && $existing->isPaid())) {
                throw ValidationException::withMessages([
                    'invoice' => 'This invoice is not waiting for payment.',
                ]);
            }

            $values = $this->snapshotValues($invoice, $landlord, $breakdown);
            if ($existing) {
                $existing->forceFill($values)->save();

                return $existing;
            }

            return ComplianceJobPayment::create($values + [
                'compliance_job_invoice_id' => $invoice->id,
            ]);
        });

        $metadata = [
            'type' => 'compliance_job_payment',
            'compliance_job_invoice_id' => (string) $invoice->id,
            'account_id' => (string) $invoice->account_id,
            'landlord_user_id' => (string) $landlord->id,
            'gross_amount' => number_format($breakdown['gross'], 2, '.', ''),
            'commission_percent' => number_format($breakdown['percent'], 2, '.', ''),
            'commission_bearer' => $breakdown['bearer'],
            'commission_amount' => number_format($breakdown['commission'], 2, '.', ''),
            'landlord_amount' => number_format($breakdown['landlord_amount'], 2, '.', ''),
            'contractor_amount' => number_format($breakdown['contractor_amount'], 2, '.', ''),
        ];

        $offer = $invoice->offer;
        $booking = $offer?->booking;
        $property = $booking?->property?->full_address ?: ($booking?->type?->name ?: 'Compliance check');
        $lineItems = [[
            'price_data' => [
                'currency' => 'gbp',
                'unit_amount' => $this->toMinor($breakdown['bearer'] === ComplianceJobPayment::BEARER_LANDLORD ? $breakdown['gross'] : $breakdown['landlord_amount']),
                'product_data' => [
                    'name' => 'Invoice '.$invoice->number,
                    'description' => $property,
                ],
            ],
            'quantity' => 1,
        ]];
        if ($breakdown['bearer'] === ComplianceJobPayment::BEARER_LANDLORD && $breakdown['commission'] >= 0.01) {
            $lineItems[] = [
                'price_data' => [
                    'currency' => 'gbp',
                    'unit_amount' => $this->toMinor($breakdown['commission']),
                    'product_data' => [
                        'name' => 'Resisquare commission',
                    ],
                ],
                'quantity' => 1,
            ];
        }

        $this->configureStripe();
        $session = Session::create([
            'mode' => 'payment',
            'customer_email' => $landlord->email,
            'line_items' => $lineItems,
            'success_url' => $booking && $offer
                ? route('admin.compliance.bookings.invoice.return', [$booking, $offer])
                : url('/'),
            'cancel_url' => $booking && $offer
                ? route('admin.compliance.bookings.invoice', ['booking' => $booking, 'offer' => $offer, 'checkout' => 'cancelled'])
                : url('/'),
            'client_reference_id' => 'compliance_job_invoice_'.$invoice->id,
            'metadata' => $metadata,
            'payment_intent_data' => [
                'metadata' => $metadata,
                'description' => 'Compliance invoice '.$invoice->number,
            ],
        ]);

        if (! $session->url) {
            throw new RuntimeException('Stripe did not return a checkout URL.');
        }

        $payment->forceFill([
            'stripe_checkout_session_id' => (string) $session->id,
        ])->save();

        return (string) $session->url;
    }

    public function fulfillCheckoutSessionId(string $sessionId): ?ComplianceJobPayment
    {
        $this->configureStripe();
        $session = Session::retrieve($sessionId);

        return $this->fulfillSession($session);
    }

    public function fulfillSession(mixed $session): ?ComplianceJobPayment
    {
        if (! $session) {
            return null;
        }

        $metadata = $this->metadataArray($session->metadata ?? []);
        if (($metadata['type'] ?? '') !== 'compliance_job_payment') {
            return null;
        }

        $paymentStatus = (string) ($session->payment_status ?? '');
        if (! in_array($paymentStatus, ['paid', 'no_payment_required'], true)) {
            return null;
        }

        $invoiceId = (int) ($metadata['compliance_job_invoice_id'] ?? 0);
        $sessionId = (string) ($session->id ?? '');
        if ($invoiceId < 1 || $sessionId === '') {
            Log::warning('Compliance job Stripe session missing invoice metadata', [
                'checkout_session_id' => $sessionId,
            ]);

            return null;
        }

        $paymentIntentId = $this->paymentIntentId($session);
        $existingIntent = $paymentIntentId !== ''
            ? ComplianceJobPayment::query()->where('stripe_payment_intent_id', $paymentIntentId)->first()
            : null;
        if ($existingIntent) {
            return $existingIntent;
        }

        $existingSession = ComplianceJobPayment::query()
            ->where('stripe_checkout_session_id', $sessionId)
            ->where('status', ComplianceJobPayment::PAID)
            ->first();
        if ($existingSession) {
            return $existingSession;
        }

        try {
            $settled = DB::transaction(function () use ($metadata, $invoiceId, $sessionId, $paymentIntentId) {
                $invoice = ComplianceJobInvoice::query()->whereKey($invoiceId)->lockForUpdate()->first();
                if (! $invoice) {
                    Log::warning('Compliance job Stripe session did not match a local invoice', [
                        'checkout_session_id' => $sessionId,
                        'compliance_job_invoice_id' => $invoiceId,
                    ]);

                    return ['payment' => null, 'fresh' => false];
                }

                $payment = ComplianceJobPayment::query()
                    ->where('compliance_job_invoice_id', $invoice->id)
                    ->lockForUpdate()
                    ->first();
                if ($invoice->status !== ComplianceJobInvoice::ISSUED || ($payment && $payment->isPaid())) {
                    if ($payment && $paymentIntentId !== '' && $payment->stripe_payment_intent_id && $payment->stripe_payment_intent_id !== $paymentIntentId) {
                        Log::warning('Compliance job invoice already paid by a different Stripe payment', [
                            'compliance_job_invoice_id' => $invoice->id,
                            'checkout_session_id' => $sessionId,
                            'payment_intent' => $paymentIntentId,
                        ]);
                    }

                    return ['payment' => $payment, 'fresh' => false];
                }

                $gross = array_key_exists('gross_amount', $metadata)
                    ? (float) $metadata['gross_amount']
                    : (float) $invoice->gross_amount;
                $breakdown = $this->breakdown(
                    $gross,
                    (float) ($metadata['commission_percent'] ?? 0),
                    (string) ($metadata['commission_bearer'] ?? ComplianceJobPayment::BEARER_CONTRACTOR),
                );
                $landlordId = (int) ($metadata['landlord_user_id'] ?? 0);
                $nothingToTransfer = $breakdown['contractor_amount'] < 0.01;
                $values = [
                    'account_id' => $invoice->account_id,
                    'paid_by' => $landlordId > 0 ? $landlordId : null,
                    'gross_amount' => $breakdown['gross'],
                    'commission_percent' => $breakdown['percent'],
                    'commission_bearer' => $breakdown['bearer'],
                    'commission_amount' => $breakdown['commission'],
                    'landlord_amount' => $breakdown['landlord_amount'],
                    'contractor_amount' => $breakdown['contractor_amount'],
                    'currency' => 'gbp',
                    'stripe_checkout_session_id' => $sessionId,
                    'stripe_payment_intent_id' => $paymentIntentId !== '' ? $paymentIntentId : null,
                    'status' => ComplianceJobPayment::PAID,
                    'paid_at' => now(),
                    'payout_status' => $nothingToTransfer ? ComplianceJobPayment::PAYOUT_PAID : ComplianceJobPayment::PAYOUT_UNPAID,
                    'payout_reference' => $nothingToTransfer ? 'No transfer due' : null,
                    'payout_paid_at' => $nothingToTransfer ? now() : null,
                ];

                if ($payment) {
                    $payment->forceFill($values)->save();
                } else {
                    $payment = ComplianceJobPayment::create($values + [
                        'compliance_job_invoice_id' => $invoice->id,
                    ]);
                }

                $invoice->forceFill([
                    'status' => ComplianceJobInvoice::PAID,
                    'paid_at' => now(),
                    'paid_by' => $values['paid_by'],
                ])->save();

                return ['payment' => $payment, 'fresh' => true];
            });
        } catch (UniqueConstraintViolationException) {
            return ComplianceJobPayment::query()->where('stripe_checkout_session_id', $sessionId)->first()
                ?: ($paymentIntentId !== '' ? ComplianceJobPayment::query()->where('stripe_payment_intent_id', $paymentIntentId)->first() : null);
        }

        $payment = $settled['payment'] ?? null;
        if ($payment && ($settled['fresh'] ?? false)) {
            $this->tellContractorPaid($payment);
        }

        return $payment;
    }

    public function markPayoutSent(ComplianceJobPayment $payment, User $staff, string $reference): void
    {
        $reference = trim($reference);
        if ($reference === '') {
            throw new ContractorMarketplaceException('Enter the bank transfer reference.');
        }
        if (! $payment->isPaid()) {
            throw new ContractorMarketplaceException('The landlord has not paid this invoice yet.');
        }
        if ($payment->payoutIsSent()) {
            throw new ContractorMarketplaceException('This transfer is already recorded.');
        }

        $payment->forceFill([
            'payout_status' => ComplianceJobPayment::PAYOUT_PAID,
            'payout_reference' => Str::limit($reference, 64, ''),
            'payout_paid_at' => now(),
            'payout_paid_by' => $staff->id,
        ])->save();

        $this->tellContractorPayout($payment->fresh());
    }

    /**
     * @param  array{gross: float, percent: float, bearer: string, commission: float, landlord_amount: float, contractor_amount: float}  $breakdown
     * @return array<string, mixed>
     */
    private function snapshotValues(ComplianceJobInvoice $invoice, User $landlord, array $breakdown): array
    {
        return [
            'account_id' => $invoice->account_id,
            'paid_by' => $landlord->id,
            'gross_amount' => $breakdown['gross'],
            'commission_percent' => $breakdown['percent'],
            'commission_bearer' => $breakdown['bearer'],
            'commission_amount' => $breakdown['commission'],
            'landlord_amount' => $breakdown['landlord_amount'],
            'contractor_amount' => $breakdown['contractor_amount'],
            'currency' => 'gbp',
            'status' => ComplianceJobPayment::PENDING,
            'paid_at' => null,
            'payout_status' => ComplianceJobPayment::PAYOUT_UNPAID,
            'payout_reference' => null,
            'payout_paid_at' => null,
            'payout_paid_by' => null,
        ];
    }

    private function tellContractorPaid(ComplianceJobPayment $payment): void
    {
        $payment->loadMissing('invoice.contractor', 'invoice.offer');
        $invoice = $payment->invoice;
        $offer = $invoice?->offer;
        $contractor = $invoice?->contractor;
        if (! $invoice || ! $offer || ! $contractor) {
            return;
        }

        $owed = (float) $payment->contractor_amount;
        $notice = $owed < 0.01
            ? 'The landlord paid invoice '.$invoice->number.'. No transfer is due after the Resisquare commission.'
            : 'The landlord paid invoice '.$invoice->number.'. Resisquare will transfer £'.number_format($owed, 2).' to the account on the invoice.';
        $url = route('contractor.jobs.compliance.invoice', $offer);
        $this->mail->toUser($contractor, 'Invoice paid', '<p>'.e($notice).'</p>');
        $this->mail->bell($contractor, 'Invoice paid', $notice, $url, (int) $invoice->account_id, 'compliance.booking');
    }

    private function tellContractorPayout(ComplianceJobPayment $payment): void
    {
        $payment->loadMissing('invoice.contractor', 'invoice.offer');
        $invoice = $payment->invoice;
        $offer = $invoice?->offer;
        $contractor = $invoice?->contractor;
        if (! $invoice || ! $offer || ! $contractor) {
            return;
        }

        $notice = 'Resisquare sent £'.number_format((float) $payment->contractor_amount, 2).' for invoice '.$invoice->number.'. Reference '.$payment->payout_reference.'.';
        $url = route('contractor.jobs.compliance.invoice', $offer);
        $this->mail->toUser($contractor, 'Payment sent', '<p>'.e($notice).'</p>');
        $this->mail->bell($contractor, 'Payment sent', $notice, $url, (int) $invoice->account_id, 'compliance.booking');
    }

    private function configureStripe(): void
    {
        $secret = trim((string) config('services.stripe.secret'));
        if ($secret === '') {
            throw new RuntimeException('Stripe secret is not configured. Set STRIPE_SECRET.');
        }

        Stripe::setApiKey($secret);
    }

    private function toMinor(float $pounds): int
    {
        return (int) round($pounds * 100);
    }

    private function paymentIntentId(mixed $session): string
    {
        $paymentIntent = $session->payment_intent ?? null;
        if (is_string($paymentIntent)) {
            return $paymentIntent;
        }
        if (is_object($paymentIntent)) {
            return (string) ($paymentIntent->id ?? '');
        }

        return '';
    }

    /**
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
