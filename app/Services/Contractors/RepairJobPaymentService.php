<?php

namespace App\Services\Contractors;

use App\Enums\CrmNotificationEvent;
use App\Models\Account;
use App\Models\RepairJobInvoice;
use App\Models\RepairJobPayment;
use App\Models\User;
use App\Support\ContractorSettings;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Stripe\Checkout\Session;
use Stripe\Stripe;

/**
 * Landlord card payments for a finished contractor job.
 * The charge lands in Resisquare's operating Stripe account. Commission stays
 * with Resisquare. Staff pay the contractor's share to the bank details on the invoice.
 */
class RepairJobPaymentService
{
    public function __construct(
        private readonly RepairJobOfferService $offers,
    ) {}

    public function isConfigured(): bool
    {
        return $this->secret() !== '';
    }

    /**
     * @return array{gross: float, percent: float, bearer: string, commission: float, landlord_amount: float, contractor_amount: float}
     */
    public function breakdown(float $gross, ?float $percent = null, ?string $bearer = null): array
    {
        $gross = round(max(0, $gross), 2);
        $percent = round($percent ?? ContractorSettings::commissionPercent(), 2);
        if ($percent < 0) {
            $percent = 0.0;
        }
        if ($percent > 100) {
            $percent = 100.0;
        }
        $bearer = $bearer ?? ContractorSettings::commissionBearer();
        if (! in_array($bearer, [RepairJobPayment::BEARER_CONTRACTOR, RepairJobPayment::BEARER_LANDLORD], true)) {
            $bearer = RepairJobPayment::BEARER_CONTRACTOR;
        }

        $commission = round($gross * $percent / 100, 2);
        if ($bearer === RepairJobPayment::BEARER_LANDLORD) {
            $landlordAmount = round($gross + $commission, 2);
            $contractorAmount = $gross;
        } else {
            $landlordAmount = $gross;
            $contractorAmount = round($gross - $commission, 2);
        }

        return [
            'gross' => $gross,
            'percent' => $percent,
            'bearer' => $bearer,
            'commission' => $commission,
            'landlord_amount' => $landlordAmount,
            'contractor_amount' => $contractorAmount,
        ];
    }

    /**
     * Amounts to show. A paid row keeps the snapshot from checkout.
     *
     * @return array{gross: float, percent: float, bearer: string, commission: float, landlord_amount: float, contractor_amount: float}
     */
    public function chargeFor(RepairJobInvoice $invoice): array
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

    public function createCheckoutUrl(User $landlord, RepairJobInvoice $invoice): string
    {
        if (! $this->isConfigured()) {
            throw ValidationException::withMessages([
                'invoice' => 'Card payments are not available yet.',
            ]);
        }

        $invoice->loadMissing('offer.repairIssue.property');
        if ($invoice->status !== RepairJobInvoice::ISSUED) {
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
            $locked = RepairJobInvoice::query()->whereKey($invoice->id)->lockForUpdate()->first();
            $existing = RepairJobPayment::query()
                ->where('repair_job_invoice_id', $invoice->id)
                ->lockForUpdate()
                ->first();
            if (! $locked || $locked->status !== RepairJobInvoice::ISSUED || ($existing && $existing->isPaid())) {
                throw ValidationException::withMessages([
                    'invoice' => 'This invoice is not waiting for payment.',
                ]);
            }

            $values = $this->snapshotValues($invoice, $landlord, $breakdown);
            if ($existing) {
                $existing->forceFill($values)->save();

                return $existing;
            }

            return RepairJobPayment::create($values + [
                'repair_job_invoice_id' => $invoice->id,
            ]);
        });

        $metadata = [
            'type' => 'repair_job_payment',
            'repair_job_invoice_id' => (string) $invoice->id,
            'account_id' => (string) $invoice->account_id,
            'landlord_user_id' => (string) $landlord->id,
            'gross_amount' => number_format($breakdown['gross'], 2, '.', ''),
            'commission_percent' => number_format($breakdown['percent'], 2, '.', ''),
            'commission_bearer' => $breakdown['bearer'],
            'commission_amount' => number_format($breakdown['commission'], 2, '.', ''),
            'landlord_amount' => number_format($breakdown['landlord_amount'], 2, '.', ''),
            'contractor_amount' => number_format($breakdown['contractor_amount'], 2, '.', ''),
        ];

        $property = $invoice->offer?->repairIssue?->property?->full_address ?: 'Repair';
        $lineItems = [[
            'price_data' => [
                'currency' => 'gbp',
                'unit_amount' => $this->toMinor($breakdown['bearer'] === RepairJobPayment::BEARER_LANDLORD ? $breakdown['gross'] : $breakdown['landlord_amount']),
                'product_data' => [
                    'name' => 'Invoice '.$invoice->number,
                    'description' => $property,
                ],
            ],
            'quantity' => 1,
        ]];
        if ($breakdown['bearer'] === RepairJobPayment::BEARER_LANDLORD && $breakdown['commission'] >= 0.01) {
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

        $offer = $invoice->offer;
        $this->configureStripe();
        $session = Session::create([
            'mode' => 'payment',
            'customer_email' => $landlord->email,
            'line_items' => $lineItems,
            'success_url' => route('admin.property_repairs.job_offers.invoice.return', $offer),
            'cancel_url' => route('admin.property_repairs.job_offers.invoice', ['offer' => $offer, 'checkout' => 'cancelled']),
            'client_reference_id' => 'repair_job_invoice_'.$invoice->id,
            'metadata' => $metadata,
            'payment_intent_data' => [
                'metadata' => $metadata,
                'description' => 'Job invoice '.$invoice->number,
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

    public function fulfillCheckoutSessionId(string $sessionId): ?RepairJobPayment
    {
        $this->configureStripe();
        $session = Session::retrieve($sessionId);

        return $this->fulfillSession($session);
    }

    public function fulfillSession(mixed $session): ?RepairJobPayment
    {
        if (! $session) {
            return null;
        }

        $metadata = $this->metadataArray($session->metadata ?? []);
        if (($metadata['type'] ?? '') !== 'repair_job_payment') {
            return null;
        }

        $paymentStatus = (string) ($session->payment_status ?? '');
        if (! in_array($paymentStatus, ['paid', 'no_payment_required'], true)) {
            return null;
        }

        $invoiceId = (int) ($metadata['repair_job_invoice_id'] ?? 0);
        $sessionId = (string) ($session->id ?? '');
        if ($invoiceId < 1 || $sessionId === '') {
            Log::warning('Repair job Stripe session missing invoice metadata', [
                'checkout_session_id' => $sessionId,
            ]);

            return null;
        }

        $paymentIntentId = $this->paymentIntentId($session);
        $existingIntent = $paymentIntentId !== ''
            ? RepairJobPayment::query()->where('stripe_payment_intent_id', $paymentIntentId)->first()
            : null;
        if ($existingIntent) {
            return $existingIntent;
        }

        $existingSession = RepairJobPayment::query()
            ->where('stripe_checkout_session_id', $sessionId)
            ->where('status', RepairJobPayment::PAID)
            ->first();
        if ($existingSession) {
            return $existingSession;
        }

        try {
            $settled = DB::transaction(function () use ($metadata, $invoiceId, $sessionId, $paymentIntentId) {
                $invoice = RepairJobInvoice::query()->whereKey($invoiceId)->lockForUpdate()->first();
                if (! $invoice) {
                    Log::warning('Repair job Stripe session did not match a local invoice', [
                        'checkout_session_id' => $sessionId,
                        'repair_job_invoice_id' => $invoiceId,
                    ]);

                    return ['payment' => null, 'fresh' => false];
                }

                $payment = RepairJobPayment::query()
                    ->where('repair_job_invoice_id', $invoice->id)
                    ->lockForUpdate()
                    ->first();
                if ($invoice->status !== RepairJobInvoice::ISSUED || ($payment && $payment->isPaid())) {
                    if ($payment && $paymentIntentId !== '' && $payment->stripe_payment_intent_id && $payment->stripe_payment_intent_id !== $paymentIntentId) {
                        Log::warning('Repair job invoice already paid by a different Stripe payment', [
                            'repair_job_invoice_id' => $invoice->id,
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
                    (string) ($metadata['commission_bearer'] ?? RepairJobPayment::BEARER_CONTRACTOR),
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
                    'status' => RepairJobPayment::PAID,
                    'paid_at' => now(),
                    'payout_status' => $nothingToTransfer ? RepairJobPayment::PAYOUT_PAID : RepairJobPayment::PAYOUT_UNPAID,
                    'payout_reference' => $nothingToTransfer ? 'No transfer due' : null,
                    'payout_paid_at' => $nothingToTransfer ? now() : null,
                ];

                if ($payment) {
                    $payment->forceFill($values)->save();
                } else {
                    $payment = RepairJobPayment::create($values + [
                        'repair_job_invoice_id' => $invoice->id,
                    ]);
                }

                $invoice->forceFill([
                    'status' => RepairJobInvoice::PAID,
                    'paid_at' => now(),
                    'paid_by' => $values['paid_by'],
                ])->save();

                return ['payment' => $payment, 'fresh' => true];
            });
        } catch (UniqueConstraintViolationException) {
            return RepairJobPayment::query()->where('stripe_checkout_session_id', $sessionId)->first()
                ?: ($paymentIntentId !== '' ? RepairJobPayment::query()->where('stripe_payment_intent_id', $paymentIntentId)->first() : null);
        }

        $payment = $settled['payment'] ?? null;
        if ($payment && ($settled['fresh'] ?? false)) {
            $this->tellContractorPaid($payment);
        }

        return $payment;
    }

    public function markPayoutSent(RepairJobPayment $payment, User $staff, string $reference): void
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
            'payout_status' => RepairJobPayment::PAYOUT_PAID,
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
    private function snapshotValues(RepairJobInvoice $invoice, User $landlord, array $breakdown): array
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
            'status' => RepairJobPayment::PENDING,
            'paid_at' => null,
            'payout_status' => RepairJobPayment::PAYOUT_UNPAID,
            'payout_reference' => null,
            'payout_paid_at' => null,
            'payout_paid_by' => null,
        ];
    }

    private function tellContractorPaid(RepairJobPayment $payment): void
    {
        $payment->loadMissing('invoice.contractor', 'invoice.offer');
        $invoice = $payment->invoice;
        $offer = $invoice?->offer;
        if (! $invoice || ! $offer) {
            return;
        }

        $owed = (float) $payment->contractor_amount;
        $notice = $owed < 0.01
            ? 'The landlord paid invoice '.$invoice->number.'. No transfer is due after the Resisquare commission.'
            : 'The landlord paid invoice '.$invoice->number.'. Resisquare will transfer £'.number_format($owed, 2).' to the account on the invoice.';
        $this->offers->log(
            $offer,
            $offer->status,
            $offer->status,
            null,
            'Card payment received for '.$invoice->number.'. Landlord paid £'.number_format((float) $payment->landlord_amount, 2).'. Commission £'.number_format((float) $payment->commission_amount, 2).'.'
        );
        $this->offers->notify(
            CrmNotificationEvent::RepairJobInvoiced,
            $offer,
            [$invoice->contractor],
            'Invoice paid',
            $notice,
            'invoice-paid-'.$invoice->id,
            route('contractor.jobs.invoice', $offer)
        );
    }

    private function tellContractorPayout(RepairJobPayment $payment): void
    {
        $payment->loadMissing('invoice.contractor', 'invoice.offer');
        $invoice = $payment->invoice;
        $offer = $invoice?->offer;
        if (! $invoice || ! $offer) {
            return;
        }

        $notice = 'Resisquare sent £'.number_format((float) $payment->contractor_amount, 2).' for invoice '.$invoice->number.'. Reference '.$payment->payout_reference.'.';
        $this->offers->log($offer, $offer->status, $offer->status, null, 'Transfer recorded for '.$invoice->number.'. Reference '.$payment->payout_reference.'.');
        $this->offers->notify(
            CrmNotificationEvent::RepairJobInvoiced,
            $offer,
            [$invoice->contractor],
            'Payment sent',
            $notice,
            'invoice-payout-'.$invoice->id,
            route('contractor.jobs.invoice', $offer)
        );
    }

    private function configureStripe(): void
    {
        $secret = $this->secret();
        if ($secret === '') {
            throw new RuntimeException('Stripe secret is not configured. Set STRIPE_SECRET.');
        }

        Stripe::setApiKey($secret);
    }

    private function secret(): string
    {
        return trim((string) config('services.stripe.secret'));
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
