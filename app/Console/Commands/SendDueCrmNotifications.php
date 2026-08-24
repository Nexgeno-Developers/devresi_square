<?php

namespace App\Console\Commands;

use App\Enums\CrmNotificationEvent;
use App\Models\Account;
use App\Models\AccountUser;
use App\Models\ComplianceRecord;
use App\Models\PropertyParticipant;
use App\Models\RepairIssue;
use App\Models\SysSaleInvoice;
use App\Models\Tenancy;
use App\Models\TenantMember;
use App\Models\User;
use App\Models\NotificationLog;
use App\Models\Property;
use App\Services\Notifications\CrmNotificationService;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use DateTimeInterface;

class SendDueCrmNotifications extends Command
{
    protected $signature = 'crm-notifications:send-due {--account=} {--at= : ISO date/time override}';

    protected $description = 'Send account-timezone-aware CRM deadline and SLA notifications.';

    public function handle(CrmNotificationService $notifications): int
    {
        $query = Account::query()->where('status', '!=', 'cancelled');
        if ($this->option('account')) {
            $query->whereKey((int) $this->option('account'));
        }

        $created = 0;
        $query->orderBy('id')->chunkById(100, function (Collection $accounts) use ($notifications, &$created): void {
            foreach ($accounts as $account) {
                $timezone = $account->timezone ?: config('crm_notifications.default_timezone', 'Europe/London');
                $now = $this->option('at')
                    ? CarbonImmutable::parse((string) $this->option('at'), $timezone)->setTimezone($timezone)
                    : CarbonImmutable::now($timezone);

                $created += $this->processCompliance($account, $now, $notifications);
                $created += $this->processTenancies($account, $now, $notifications);
                $created += $this->processRightToRent($account, $now, $notifications);
                $created += $this->processRepairSlas($account, $now, $notifications);
                $created += $this->processInvoices($account, $now, $notifications);
            }
        });

        $this->info("Created {$created} channel deliveries.");

        return self::SUCCESS;
    }

    private function processCompliance(Account $account, CarbonImmutable $now, CrmNotificationService $notifications): int
    {
        $created = 0;
        ComplianceRecord::query()
            ->whereHas('property', fn ($query) => $query->where('account_id', $account->id))
            ->whereNull('completed_at')
            ->with(['property', 'complianceType', 'responsibleUser'])
            ->chunkById(200, function ($records) use ($account, $now, $notifications, &$created): void {
                foreach ($records as $record) {
                    if ($record->expiry_date) {
                        $days = $this->calendarDaysUntil($now, $record->expiry_date, $now->timezoneName);
                        $milestone = $this->deadlineMilestone($days, [60, 30, 14, 7, 1, 0]);
                        $event = $days < 0 ? CrmNotificationEvent::ComplianceExpired : CrmNotificationEvent::ComplianceExpiring;
                        $milestone = $this->backfillMilestone($milestone, $days, $record, $event);
                        if ($milestone) {
                            $created += $notifications->dispatch($event, $record, $this->deadlineContext(
                                $account->id,
                                $milestone,
                                $record->expiry_date,
                                $days,
                                [
                                    'recipients' => $this->operationalRecipients($account, $record->property_id, $record->responsibleUser),
                                    'compliance_type' => $record->complianceType?->name ?? 'Compliance record',
                                    'property_address' => $this->propertyAddress($record->property),
                                    'action_url' => route('admin.properties.view', $record->property_id),
                                ]
                            ));
                        }
                    }

                    if ($record->remediation_due_at) {
                        $days = $this->calendarDaysUntil($now, $record->remediation_due_at, $now->timezoneName);
                        $milestone = $this->deadlineMilestone($days, [14, 7, 3, 1, 0]);
                        $milestone = $this->backfillMilestone($milestone, $days, $record, CrmNotificationEvent::ComplianceRemediationDue);
                        if ($milestone) {
                            $created += $notifications->dispatch(CrmNotificationEvent::ComplianceRemediationDue, $record, $this->deadlineContext(
                                $account->id,
                                'remediation-'.$milestone,
                                $record->remediation_due_at,
                                $days,
                                [
                                    'recipients' => $this->operationalRecipients($account, $record->property_id, $record->responsibleUser),
                                    'compliance_type' => $record->complianceType?->name ?? 'Compliance record',
                                    'property_address' => $this->propertyAddress($record->property),
                                    'action_url' => route('admin.properties.view', $record->property_id),
                                ]
                            ));
                        }
                    }
                }
            });

        return $created;
    }

    private function processTenancies(Account $account, CarbonImmutable $now, CrmNotificationService $notifications): int
    {
        $created = 0;
        Tenancy::query()->forAccount($account->id)
            ->whereRaw("LOWER(status) NOT IN ('cancelled', 'ended')")
            ->with(['property', 'propertyManagers', 'tenantMembers.user'])
            ->chunkById(200, function ($tenancies) use ($account, $now, $notifications, &$created): void {
                foreach ($tenancies as $tenancy) {
                    if ($tenancy->move_in) {
                        $days = $this->calendarDaysUntil($now, $tenancy->move_in, $now->timezoneName);
                        if (in_array($days, [14, 7, 1], true)) {
                            $created += $notifications->dispatch(CrmNotificationEvent::TenancyMoveInDue, $tenancy, [
                                'account_id' => $account->id,
                                'milestone' => "move-in-{$days}",
                                'property_address' => $this->propertyAddress($tenancy->property),
                                'days_text' => $days === 1 ? '1 day' : "{$days} days",
                                'action_url' => route('admin.tenancies.show', $tenancy->id),
                            ]);
                        }
                    }

                    if ($tenancy->deposit_received_at && (! $tenancy->deposit_protected_at || ! $tenancy->prescribed_information_sent_at)) {
                        $due = CarbonImmutable::parse($tenancy->deposit_received_at)->setTimezone($now->timezoneName)->addDays(30);
                        $days = $this->calendarDaysUntil($now, $due, $now->timezoneName);
                        $milestone = $this->deadlineMilestone($days, [14, 7, 3, 1, 0]);
                        $milestone = $this->backfillMilestone($milestone, $days, $tenancy, CrmNotificationEvent::TenancyDepositDue);
                        if ($milestone) {
                            $created += $notifications->dispatch(CrmNotificationEvent::TenancyDepositDue, $tenancy, $this->deadlineContext(
                                $account->id,
                                'deposit-'.$milestone,
                                $due,
                                $days,
                                [
                                    'recipients' => $this->operationalRecipients($account, $tenancy->property_id, $tenancy->propertyManagers),
                                    'property_address' => $this->propertyAddress($tenancy->property),
                                    'action_url' => route('admin.tenancies.show', $tenancy->id),
                                ]
                            ));
                        }
                    }
                }
            });

        return $created;
    }

    private function processRightToRent(Account $account, CarbonImmutable $now, CrmNotificationService $notifications): int
    {
        $created = 0;
        TenantMember::query()->forAccount($account->id)
            ->where('right_to_rent_required', true)
            ->whereNotNull('right_to_rent_follow_up_due_at')
            ->with(['tenancy.property', 'tenancy.propertyManagers'])
            ->chunkById(200, function ($members) use ($account, $now, $notifications, &$created): void {
                foreach ($members as $member) {
                    $due = $member->right_to_rent_follow_up_due_at;
                    $days = $this->calendarDaysUntil($now, $due, $now->timezoneName);
                    $milestone = $this->deadlineMilestone($days, [28, 14, 7, 1, 0]);
                    $milestone = $this->backfillMilestone($milestone, $days, $member, CrmNotificationEvent::TenancyRightToRentDue);
                    if (! $milestone || ! $member->tenancy) {
                        continue;
                    }

                    $created += $notifications->dispatch(CrmNotificationEvent::TenancyRightToRentDue, $member, $this->deadlineContext(
                        $account->id,
                        'right-to-rent-'.$milestone,
                        $due,
                        $days,
                        [
                            'recipients' => $this->operationalRecipients($account, $member->tenancy->property_id, $member->tenancy->propertyManagers),
                            'action_url' => route('admin.tenancies.show', $member->tenancy_id),
                        ]
                    ));
                }
            });

        return $created;
    }

    private function processRepairSlas(Account $account, CarbonImmutable $now, CrmNotificationService $notifications): int
    {
        $created = 0;
        RepairIssue::query()->forAccount($account->id)
            ->whereNull('acknowledged_at')
            ->whereRaw("LOWER(status) NOT IN ('completed', 'closed', 'cancelled')")
            ->with(['property', 'tenant', 'repairIssuePropertyManagers.propertyManager'])
            ->chunkById(200, function ($repairs) use ($account, $now, $notifications, &$created): void {
                foreach ($repairs as $repair) {
                    $threshold = match ($repair->priority) {
                        'critical' => $repair->created_at->addHour(),
                        'high' => $repair->created_at->addHours(4),
                        'medium' => $this->addBusinessDays(CarbonImmutable::instance($repair->created_at), 1),
                        default => $this->addBusinessDays(CarbonImmutable::instance($repair->created_at), 2),
                    };
                    if ($now->utc()->lessThan($threshold->utc())) {
                        continue;
                    }

                    $daysOver = max(0, (int) floor($threshold->utc()->diffInHours($now->utc()) / 24));
                    $milestone = 'sla-'.($daysOver === 0 ? 'initial' : "day-{$daysOver}");
                    $created += $notifications->dispatch(CrmNotificationEvent::RepairEscalated, $repair, [
                        'account_id' => $account->id,
                        'milestone' => $milestone,
                        'recipients' => $this->operationalRecipients($account, $repair->property_id, $repair->repairIssuePropertyManagers->pluck('propertyManager')),
                        'repair_reference' => $repair->reference_number ?: "#{$repair->id}",
                        'property_address' => $this->propertyAddress($repair->property),
                        'action_url' => route('admin.property_repairs.show', $repair->id),
                    ]);
                }
            });

        return $created;
    }

    private function processInvoices(Account $account, CarbonImmutable $now, CrmNotificationService $notifications): int
    {
        $created = 0;
        SysSaleInvoice::query()->forAccount($account->id)
            ->whereNotNull('due_date')
            ->whereNotIn('status', ['cancelled', 'void', 'paid'])
            ->where(fn ($query) => $query->whereNull('balance_amount')->orWhere('balance_amount', '>', 0))
            ->with(['user', 'linkTo'])
            ->chunkById(200, function ($invoices) use ($account, $now, $notifications, &$created): void {
                foreach ($invoices as $invoice) {
                    $days = $this->calendarDaysUntil($now, $invoice->due_date, $now->timezoneName);
                    $milestone = $days >= 0
                        ? (in_array($days, [3, 0], true) ? "due-{$days}" : null)
                        : $this->deadlineMilestone($days, []);
                    $event = $days < 0 ? CrmNotificationEvent::FinanceInvoiceOverdue : CrmNotificationEvent::FinanceInvoiceDue;
                    $milestone = $this->backfillMilestone($milestone, $days, $invoice, $event);
                    if (! $milestone) {
                        continue;
                    }

                    $created += $notifications->dispatch($event, $invoice, [
                        'account_id' => $account->id,
                        'milestone' => $milestone,
                        'invoice_id' => $invoice->id,
                        'invoice_number' => $invoice->invoice_no ?: (string) $invoice->id,
                        'invoice_amount' => $this->money($invoice->balance_amount ?? $invoice->total_amount, $account->currency),
                        'due_date' => CarbonImmutable::parse($invoice->due_date)->format('d/m/Y'),
                        'action_url' => route('backend.accounting.sale.invoices.show', $invoice->id),
                    ]);

                    if ($days < 0) {
                        $linked = $invoice->linkTo;
                        $property = $linked instanceof Property ? $linked : ($linked instanceof Tenancy ? $linked->property : null);
                        if ($property) {
                            $managerIds = PropertyParticipant::query()->active()->forAccount($account->id)->forProperty($property->id)
                                ->where('participant_type', 'property_manager')->pluck('user_id');
                            if ($managerIds->isNotEmpty()) {
                                $created += $notifications->dispatch($event, $invoice, [
                                    'account_id' => $account->id,
                                    'milestone' => $milestone.'-manager',
                                    'recipients' => User::whereKey($managerIds)->get(),
                                    'channels' => ['system'],
                                    'invoice_number' => $invoice->invoice_no ?: (string) $invoice->id,
                                    'invoice_amount' => $this->money($invoice->balance_amount ?? $invoice->total_amount, $account->currency),
                                    'due_date' => CarbonImmutable::parse($invoice->due_date)->format('d/m/Y'),
                                    'action_url' => route('backend.accounting.sale.invoices.show', $invoice->id),
                                ]);
                            }
                        }
                    }
                }
            });

        return $created;
    }

    private function deadlineContext(int $accountId, string $milestone, mixed $due, int $days, array $extra): array
    {
        return array_merge([
            'account_id' => $accountId,
            'milestone' => $milestone,
            'due_date' => CarbonImmutable::parse($due)->format('d/m/Y'),
            'due_text' => $days > 0 ? "due in {$days} ".($days === 1 ? 'day' : 'days') : ($days === 0 ? 'due today' : 'overdue'),
        ], $extra);
    }

    private function deadlineMilestone(int $daysUntil, array $advanceDays): ?string
    {
        if (in_array($daysUntil, $advanceDays, true)) {
            return $daysUntil === 0 ? 'due' : "before-{$daysUntil}";
        }
        if ($daysUntil === -1 || ($daysUntil < -1 && ((abs($daysUntil) - 1) % 7 === 0))) {
            return 'overdue-'.abs($daysUntil);
        }

        return null;
    }

    private function backfillMilestone(?string $milestone, int $daysUntil, Model $subject, CrmNotificationEvent $event): ?string
    {
        if ($milestone || $daysUntil >= 0) {
            return $milestone;
        }

        $alreadyAlerted = NotificationLog::query()
            ->where('identifier', $event->value)
            ->where('subject_type', $subject->getMorphClass())
            ->where('subject_id', $subject->getKey())
            ->exists();

        return $alreadyAlerted ? null : 'overdue-backfill';
    }

    private function calendarDaysUntil(CarbonImmutable $now, mixed $due, string $timezone): int
    {
        $today = CarbonImmutable::createFromFormat('Y-m-d', $now->setTimezone($timezone)->format('Y-m-d'), 'UTC');
        $dueDate = $due instanceof DateTimeInterface
            ? $due->format('Y-m-d')
            : CarbonImmutable::parse($due, $timezone)->format('Y-m-d');
        $dueDay = CarbonImmutable::createFromFormat('Y-m-d', $dueDate, 'UTC');

        return (int) round($today->diffInDays($dueDay, false));
    }

    private function operationalRecipients(Account $account, ?int $propertyId = null, mixed $additional = null): Collection
    {
        $ids = AccountUser::query()->where('account_id', $account->id)->where('status', 'active')
            ->whereIn('member_type', ['owner', 'admin', 'staff', 'property_manager'])
            ->pluck('user_id');
        $ids->push($account->owner_user_id);

        if ($propertyId) {
            $ids = $ids->merge(PropertyParticipant::query()->active()->forAccount($account->id)->forProperty($propertyId)
                ->whereIn('participant_type', ['owner', 'landlord', 'property_manager'])->pluck('user_id'));
        }

        $users = User::whereKey($ids->filter()->unique())->get();
        return $users->merge(collect($additional)->flatten()->filter(fn ($item) => $item instanceof User))->unique('id')->values();
    }

    private function addBusinessDays(CarbonImmutable $date, int $days): CarbonImmutable
    {
        while ($days > 0) {
            $date = $date->addDay();
            if (! $date->isWeekend()) {
                $days--;
            }
        }

        return $date;
    }

    private function propertyAddress(mixed $property): string
    {
        return trim((string) ($property?->address ?? $property?->display_address ?? $property?->name ?? "Property #{$property?->id}"));
    }

    private function money(mixed $amount, ?string $currency): string
    {
        $symbol = strtoupper((string) $currency) === 'GBP' || ! $currency ? '£' : strtoupper((string) $currency).' ';
        return $symbol.number_format((float) $amount, 2);
    }
}
