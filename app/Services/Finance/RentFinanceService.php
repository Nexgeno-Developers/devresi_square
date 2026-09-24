<?php

namespace App\Services\Finance;

use App\Models\RentInvoice;
use App\Models\RentPayment;
use App\Models\Tenancy;
use App\Models\TenantMember;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RentFinanceService
{
    public function billableTenancies(int $accountId): Collection
    {
        return Tenancy::query()
            ->with(['property', 'tenantMembers.user'])
            ->forAccount($accountId)
            ->whereHas('property')
            ->whereHas('tenantMembers')
            ->orderByDesc('id')
            ->get();
    }

    public function createInvoice(int $accountId, array $data): RentInvoice
    {
        $tenancy = Tenancy::query()
            ->with('property')
            ->forAccount($accountId)
            ->find($data['tenancy_id'] ?? null);

        if (! $tenancy) {
            throw ValidationException::withMessages([
                'tenancy_id' => 'Choose a tenancy on this account.',
            ]);
        }

        if (! $tenancy->property_id || ! $tenancy->property) {
            throw ValidationException::withMessages([
                'tenancy_id' => 'That tenancy has no property attached.',
            ]);
        }

        $tenantUserId = (int) ($data['tenant_user_id'] ?? 0);
        $onTenancy = TenantMember::query()
            ->where('account_id', $accountId)
            ->where('tenancy_id', $tenancy->id)
            ->where('user_id', $tenantUserId)
            ->exists();

        if (! $onTenancy) {
            throw ValidationException::withMessages([
                'tenant_user_id' => 'That person is not a tenant on the selected tenancy.',
            ]);
        }

        $amount = $this->money($data['amount'] ?? 0);
        if ($amount < 0.01) {
            throw ValidationException::withMessages([
                'amount' => 'Enter an amount greater than zero.',
            ]);
        }

        $invoice = DB::transaction(function () use ($accountId, $tenancy, $tenantUserId, $amount, $data) {
            return RentInvoice::create([
                'account_id' => $accountId,
                'property_id' => $tenancy->property_id,
                'tenancy_id' => $tenancy->id,
                'tenant_user_id' => $tenantUserId,
                'invoice_no' => $this->nextInvoiceNo($accountId),
                'issue_date' => $data['issue_date'],
                'due_date' => $data['due_date'],
                'period_start' => $data['period_start'] ?? null,
                'period_end' => $data['period_end'] ?? null,
                'amount' => $amount,
                'balance' => $amount,
                'status' => RentInvoice::STATUS_ISSUED,
                'note' => $data['note'] ?? null,
            ]);
        });

        app(RentInvoiceNotifier::class)->issued($invoice->fresh());

        return $invoice;
    }

    public function recordPayment(RentInvoice $invoice, array $data, int $recordedByUserId): RentPayment
    {
        $result = DB::transaction(function () use ($invoice, $data, $recordedByUserId) {
            $locked = RentInvoice::query()
                ->whereKey($invoice->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! $locked->isOpen()) {
                throw ValidationException::withMessages([
                    'amount' => 'This invoice cannot take a payment.',
                ]);
            }

            $amount = $this->money($data['amount'] ?? 0);
            $balance = $this->money($locked->balance);

            if ($amount < 0.01) {
                throw ValidationException::withMessages([
                    'amount' => 'Enter an amount greater than zero.',
                ]);
            }

            if ($amount > $balance) {
                throw ValidationException::withMessages([
                    'amount' => 'Payment cannot be more than the outstanding balance.',
                ]);
            }

            $sessionId = $data['stripe_checkout_session_id'] ?? null;
            if ($sessionId) {
                $existing = RentPayment::query()
                    ->where('stripe_checkout_session_id', $sessionId)
                    ->first();
                if ($existing) {
                    return ['payment' => $existing, 'notify' => false];
                }
            }

            $payment = RentPayment::create([
                'account_id' => $locked->account_id,
                'rent_invoice_id' => $locked->id,
                'amount' => $amount,
                'fee_amount' => $this->money($data['fee_amount'] ?? 0),
                'paid_at' => $data['paid_at'],
                'method' => $data['method'],
                'reference' => $data['reference'] ?? null,
                'stripe_checkout_session_id' => $sessionId,
                'stripe_payment_intent_id' => $data['stripe_payment_intent_id'] ?? null,
                'recorded_by_user_id' => $recordedByUserId,
            ]);

            $newBalance = $this->money($balance - $amount);
            $locked->balance = $newBalance;
            $locked->status = $newBalance <= 0
                ? RentInvoice::STATUS_PAID
                : RentInvoice::STATUS_PARTIAL;
            $locked->save();

            return ['payment' => $payment, 'notify' => true, 'invoice' => $locked];
        });

        if ($result['notify'] ?? false) {
            app(RentInvoiceNotifier::class)->paymentReceived($result['invoice']->fresh(), $result['payment']);
        }

        return $result['payment'];
    }

    public function voidInvoice(RentInvoice $invoice): RentInvoice
    {
        $voided = DB::transaction(function () use ($invoice) {
            $locked = RentInvoice::query()
                ->whereKey($invoice->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($locked->status !== RentInvoice::STATUS_ISSUED || $locked->payments()->exists()) {
                throw ValidationException::withMessages([
                    'invoice' => 'Only an unpaid invoice with no payments can be voided.',
                ]);
            }

            $locked->status = RentInvoice::STATUS_VOID;
            $locked->balance = 0;
            $locked->save();

            return $locked;
        });

        app(RentInvoiceNotifier::class)->voided($voided->fresh());

        return $voided->fresh();
    }

    public function enableAutoInvoice(Tenancy $tenancy, int $tenantUserId, ?Carbon $nextPeriodStart = null): Tenancy
    {
        $onTenancy = TenantMember::query()
            ->where('tenancy_id', $tenancy->id)
            ->where('user_id', $tenantUserId)
            ->exists();

        if (! $onTenancy) {
            throw ValidationException::withMessages([
                'tenant_user_id' => 'That person is not a tenant on the selected tenancy.',
            ]);
        }

        if ((float) ($tenancy->rent ?? 0) < 0.01) {
            throw ValidationException::withMessages([
                'amount' => 'Set the tenancy rent before enabling automatic invoices.',
            ]);
        }

        $frequency = $this->canonicalFrequency($tenancy->frequency);
        if ($frequency === null) {
            throw ValidationException::withMessages([
                'frequency' => 'Set the tenancy to weekly or monthly before automatic invoices. Other frequencies are not billed automatically.',
            ]);
        }

        $tenancy->forceFill([
            'frequency' => $frequency,
            'rent_auto_invoice' => true,
            'rent_auto_invoice_tenant_user_id' => $tenantUserId,
            'rent_next_period_start' => ($nextPeriodStart ?? now())->toDateString(),
        ])->save();

        return $tenancy->refresh();
    }

    /**
     * Generate due recurring rent invoices. Idempotent per tenancy + period_start.
     *
     * @return int Number of invoices created
     */
    public function generateDueRecurring(?Carbon $asOf = null): int
    {
        $asOf = ($asOf ?? now())->copy()->startOfDay();
        $created = 0;

        $tenancies = Tenancy::query()
            ->with(['property', 'tenantMembers'])
            ->where('rent_auto_invoice', true)
            ->whereNotNull('rent_next_period_start')
            ->where('rent_next_period_start', '<=', $asOf->toDateString())
            ->where('status', 'Active')
            ->whereHas('property')
            ->orderBy('id')
            ->get();

        foreach ($tenancies as $tenancy) {
            $created += $this->generateRecurringForTenancy($tenancy, $asOf);
        }

        return $created;
    }

    public function overdueOpenCount(int $accountId): int
    {
        return RentInvoice::query()
            ->forAccount($accountId)
            ->whereIn('status', [RentInvoice::STATUS_ISSUED, RentInvoice::STATUS_PARTIAL])
            ->where('balance', '>', 0)
            ->whereDate('due_date', '<', now()->toDateString())
            ->count();
    }

    public function rentPayBankDetails(?int $accountId): ?\App\Models\BankDetails
    {
        if (! $accountId) {
            return null;
        }

        if (! \Illuminate\Support\Facades\Schema::hasTable('bank_details')) {
            return null;
        }

        return \App\Models\BankDetails::query()
            ->forAccount($accountId)
            ->where('is_active', true)
            ->orderByDesc('is_primary')
            ->orderByDesc('id')
            ->first();
    }

    private function generateRecurringForTenancy(Tenancy $tenancy, Carbon $asOf): int
    {
        $created = 0;
        $cursor = Carbon::parse($tenancy->rent_next_period_start)->startOfDay();
        $tenantUserId = (int) ($tenancy->rent_auto_invoice_tenant_user_id
            ?: $tenancy->tenantMembers->firstWhere('is_main_person', true)?->user_id
            ?: $tenancy->tenantMembers->first()?->user_id);

        if ($tenantUserId <= 0 || (float) ($tenancy->rent ?? 0) < 0.01) {
            return 0;
        }

        if ($this->canonicalFrequency($tenancy->frequency) === null) {
            return 0;
        }

        // Cap catch-up so a long-dormant schedule cannot flood the ledger.
        $safety = 0;
        while ($cursor->lte($asOf) && $safety < 24) {
            $safety++;
            $periodEnd = $this->periodEnd($cursor, $tenancy->frequency);
            if ($tenancy->move_out && $cursor->gt(Carbon::parse($tenancy->move_out)->startOfDay())) {
                $tenancy->forceFill([
                    'rent_auto_invoice' => false,
                    'rent_next_period_start' => null,
                ])->save();
                break;
            }

            $exists = RentInvoice::query()
                ->where('tenancy_id', $tenancy->id)
                ->whereDate('period_start', $cursor->toDateString())
                ->where('status', '!=', RentInvoice::STATUS_VOID)
                ->exists();

            if (! $exists) {
                $this->createInvoice((int) $tenancy->account_id, [
                    'tenancy_id' => $tenancy->id,
                    'tenant_user_id' => $tenantUserId,
                    'amount' => $tenancy->rent,
                    'issue_date' => $cursor->toDateString(),
                    'due_date' => $this->dueDateForPeriod($tenancy, $cursor)->toDateString(),
                    'period_start' => $cursor->toDateString(),
                    'period_end' => $periodEnd->toDateString(),
                    'note' => 'Automatic '.$this->frequencyLabel($tenancy->frequency).' rent',
                ]);
                $created++;
            }

            $cursor = $periodEnd->copy()->addDay()->startOfDay();
            $tenancy->forceFill([
                'rent_next_period_start' => $cursor->toDateString(),
            ])->save();
        }

        return $created;
    }

    private function dueDateForPeriod(Tenancy $tenancy, Carbon $periodStart): Carbon
    {
        if ($this->canonicalFrequency($tenancy->frequency) === 'Weekly') {
            return $periodStart->copy();
        }

        $day = (int) ($tenancy->rent_due_day ?: $periodStart->day);
        $day = max(1, min(28, $day));
        $due = $periodStart->copy()->day($day);

        if ($due->lt($periodStart)) {
            $next = $periodStart->copy()->addMonthNoOverflow();
            $due = $next->day($day);
        }

        return $due;
    }

    private function canonicalFrequency(?string $frequency): ?string
    {
        $freq = strtolower(trim((string) $frequency));

        return match (true) {
            $freq === '' => 'Monthly',
            str_contains($freq, 'week') => 'Weekly',
            $freq === 'monthly' || $freq === 'month' => 'Monthly',
            default => null,
        };
    }

    private function periodEnd(Carbon $periodStart, ?string $frequency): Carbon
    {
        if ($this->canonicalFrequency($frequency) === 'Weekly') {
            return $periodStart->copy()->addDays(6);
        }

        return $periodStart->copy()->addMonthNoOverflow()->subDay();
    }

    private function frequencyLabel(?string $frequency): string
    {
        return $this->canonicalFrequency($frequency) === 'Weekly' ? 'weekly' : 'monthly';
    }

    private function nextInvoiceNo(int $accountId): string
    {
        $count = RentInvoice::query()->forAccount($accountId)->lockForUpdate()->count();

        return 'RENT-'.str_pad((string) ($count + 1), 4, '0', STR_PAD_LEFT);
    }

    private function money(mixed $value): float
    {
        return round((float) $value, 2);
    }
}
