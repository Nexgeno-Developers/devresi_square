<?php

namespace App\Services\Finance;

use App\Models\RentInvoice;
use App\Models\RentPayment;
use App\Models\Tenancy;
use App\Models\TenantMember;
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

        return DB::transaction(function () use ($accountId, $tenancy, $tenantUserId, $amount, $data) {
            $invoice = RentInvoice::create([
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

            return $invoice;
        });
    }

    public function recordPayment(RentInvoice $invoice, array $data, int $recordedByUserId): RentPayment
    {
        return DB::transaction(function () use ($invoice, $data, $recordedByUserId) {
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
                    return $existing;
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

            return $payment;
        });
    }

    public function voidInvoice(RentInvoice $invoice): RentInvoice
    {
        return DB::transaction(function () use ($invoice) {
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
