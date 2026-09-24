<?php

namespace App\Services\Finance;

use App\Enums\CrmNotificationEvent;
use App\Models\AccountUser;
use App\Models\RentInvoice;
use App\Models\RentPayment;
use App\Models\User;
use App\Services\Notifications\CrmNotificationService;
use App\Support\AccountMembership;
use Illuminate\Support\Collection;

class RentInvoiceNotifier
{
    public function __construct(private readonly CrmNotificationService $notifications)
    {
    }

    public function issued(RentInvoice $invoice): void
    {
        $this->send(
            CrmNotificationEvent::FinanceInvoiceIssued,
            $invoice,
            $this->household($invoice),
            'issued',
            [],
        );
    }

    public function paymentReceived(RentInvoice $invoice, RentPayment $payment): void
    {
        $this->send(
            CrmNotificationEvent::FinancePaymentReceived,
            $invoice,
            $this->household($invoice)->merge($this->landlords((int) $invoice->account_id)),
            'payment-'.$payment->id,
            ['payment_amount' => rs_money($payment->amount)],
        );
    }

    public function voided(RentInvoice $invoice): void
    {
        $this->send(
            CrmNotificationEvent::FinanceInvoiceVoided,
            $invoice,
            $this->household($invoice)->merge($this->landlords((int) $invoice->account_id)),
            'void',
            [],
        );
    }

    /**
     * @param  array<string, scalar|null>  $extra
     */
    public function send(CrmNotificationEvent $event, RentInvoice $invoice, Collection $recipients, string $milestone, array $extra): int
    {
        $recipients = $recipients->filter(fn ($user) => $user instanceof User && filled($user->email))->unique('id')->values();
        if ($recipients->isEmpty()) {
            return 0;
        }

        $invoice->loadMissing(['property', 'tenant']);

        return $this->notifications->dispatch($event, $invoice, array_merge([
            'account_id' => (int) $invoice->account_id,
            'milestone' => $milestone,
            'recipients' => $recipients,
            'invoice_id' => $invoice->id,
            'invoice_number' => $invoice->invoice_no ?: (string) $invoice->id,
            'invoice_amount' => rs_money($invoice->balance > 0 ? $invoice->balance : $invoice->amount),
            'due_date' => $invoice->due_date?->format('d/m/Y') ?? '',
            'property_address' => $invoice->property?->full_address ?: ($invoice->property?->line_1 ?: ''),
            'customer_name' => $invoice->tenant?->name ?: '',
            'action_url' => route('admin.finance.show', $invoice),
            'portal_action_url' => route('tenant.rent.show', $invoice),
            'portal_action_tenants_only' => true,
        ], $extra));
    }

    /**
     * @return Collection<int, User>
     */
    public function household(RentInvoice $invoice): Collection
    {
        $invoice->loadMissing(['tenant', 'tenancy.tenantMembers.user']);

        $users = collect();
        if ($invoice->tenancy) {
            $users = $users->merge($invoice->tenancy->tenantMembers->pluck('user'));
        }
        if ($invoice->tenant) {
            $users->push($invoice->tenant);
        }

        return $users->filter(fn ($user) => $user instanceof User)->unique('id')->values();
    }

    /**
     * @return Collection<int, User>
     */
    public function landlords(int $accountId): Collection
    {
        $ids = AccountUser::query()
            ->where('account_id', $accountId)
            ->where('status', 'active')
            ->whereIn('member_type', AccountMembership::WORKSPACE_ADMIN_TYPES)
            ->pluck('user_id');

        return User::query()->whereKey($ids)->get();
    }
}
