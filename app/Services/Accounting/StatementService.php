<?php

namespace App\Services\Accounting;

use App\Models\GlAccount;
use App\Models\GlJournal;
use App\Models\GlJournalLine;
use App\Models\BusinessSetting;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class StatementService
{
    /**
     * Customer statement (net + detailed lines)
     */
    public function customerStatement(int $userId, ?int $companyId, ?string $from, ?string $to): array
    {
        [$fromDate, $toDate] = $this->normalizeDates($from, $to);

        $opening = $this->sumNetByUser($userId, $companyId, $fromDate);
        $rawLines = $this->linesByUser($userId, $companyId, $fromDate, $toDate);

        [$lines, $closing] = $this->attachRunning($rawLines, $opening);

        return [
            'opening' => $opening,
            'closing' => $closing,
            'lines' => $lines,
            'from' => $fromDate,
            'to' => $toDate,
        ];
    }

    /**
     * Account ledger
     */
    public function accountStatement(int $accountId, ?int $companyId, ?string $from, ?string $to): array
    {
        [$fromDate, $toDate] = $this->normalizeDates($from, $to);

        $opening = $this->sumByAccount($accountId, $companyId, $fromDate);
        $rawLines = $this->linesByAccount($accountId, $companyId, $fromDate, $toDate);

        [$lines, $closing] = $this->attachRunning($rawLines, $opening, $this->accountType($accountId));

        return [
            'opening' => $opening,
            'closing' => $closing,
            'lines' => $lines,
            'from' => $fromDate,
            'to' => $toDate,
        ];
    }

    private function normalizeDates(?string $from, ?string $to): array
    {
        $fromDate = $from ? Carbon::parse($from)->toDateString() : Carbon::now()->startOfYear()->toDateString();
        $toDate = $to ? Carbon::parse($to)->toDateString() : null;
        if ($toDate && $toDate < $fromDate) {
            $toDate = $fromDate;
        }
        return [$fromDate, $toDate];
    }

    /**
     * Contact statement (AR-style) with summary buckets.
     */
    public function contactStatement(int $userId, ?int $companyId, ?string $from, ?string $to): array
    {
        [$fromDate, $toDate] = $this->normalizeDates($from, $to);
        $accounts = $this->contactAccounts();

        $opening = $this->sumNetByUser($userId, $companyId, $fromDate, $accounts);
        $rawLines = $this->linesByUser($userId, $companyId, $fromDate, $toDate, $accounts);
        [$lines, $closing] = $this->attachRunning($rawLines, $opening, null, $accounts);

        $summary = $this->arSummary($userId, $companyId, $fromDate, $toDate, $accounts);
        $summary['totals']['balance_due'] = $closing;

        return [
            'opening' => $opening,
            'closing' => $closing,
            'lines' => $lines,
            'from' => $fromDate,
            'to' => $toDate,
            'summary' => $summary['totals'],
            'warnings' => $summary['warnings'],
        ];
    }

    public function getContactStatementDateRange(int $userId, ?int $companyId): ?array
    {
        $accounts = $this->contactAccounts();
        $accountIds = collect($accounts['ar_ids'] ?? [])
            ->merge($accounts['adv_ids'] ?? [])
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (empty($accountIds)) {
            return null;
        }

        $q = \App\Models\GlJournalLine::query()
            ->join('gl_journals', 'gl_journal_lines.gl_journal_id', '=', 'gl_journals.id')
            ->where('gl_journal_lines.user_id', $userId)
            ->whereIn('gl_journal_lines.gl_account_id', $accountIds);

        if ($companyId) {
            $q->where('gl_journal_lines.company_id', $companyId);
        }

        $result = $q->selectRaw('MIN(gl_journals.date) as min_date, MAX(gl_journals.date) as max_date')
            ->first();

        if (!$result || !$result->min_date) {
            return null;
        }

        return [
            'min_date' => $result->min_date,
            'max_date' => $result->max_date,
        ];
    }

    /**
     * Property statement (shows transactions for tenants + property-linked transactions)
     */
    public function propertyStatement(int $propertyId, ?int $companyId, ?string $from, ?string $to): array
    {
        [$fromDate, $toDate] = $this->normalizeDates($from, $to);
        $accounts = $this->contactAccounts();

        // Get all user IDs associated with this property through tenancies and tenant_members
        $tenantUserIds = \App\Models\TenantMember::query()
            ->join('tenancies', 'tenant_members.tenancy_id', '=', 'tenancies.id')
            ->where('tenancies.property_id', $propertyId)
            ->whereNotNull('tenant_members.user_id')
            ->pluck('tenant_members.user_id')
            ->unique()
            ->values()
            ->all();

        // Get user IDs from sale invoices directly linked to this property
        $propertyLinkedUserIds = \DB::table('sys_sale_invoices')
            ->where('link_to_type', 'Property')
            ->where('link_to_id', $propertyId)
            ->pluck('user_id')
            ->unique()
            ->filter()
            ->values()
            ->all();

        // Merge all user IDs (tenants + property-linked sale invoices)
        $userIds = collect($tenantUserIds)
            ->merge($propertyLinkedUserIds)
            ->unique()
            ->values()
            ->all();

        if (empty($userIds)) {
            // No transactions found
            return [
                'opening' => 0,
                'closing' => 0,
                'lines' => collect(),
                'from' => $fromDate,
                'to' => $toDate,
                'summary' => [
                    'invoiced' => 0,
                    'paid' => 0,
                    'balance_due' => 0,
                ],
                'warnings' => ['No transactions found for this property.'],
            ];
        }

        $opening = $this->sumNetByPropertyUsers($userIds, $companyId, $fromDate, $accounts);
        $rawLines = $this->linesByPropertyUsers($userIds, $companyId, $fromDate, $toDate, $accounts);
        [$lines, $closing] = $this->attachRunning($rawLines, $opening, null, $accounts);

        $summary = $this->propertyArSummary($userIds, $companyId, $fromDate, $toDate, $accounts);
        $summary['totals']['balance_due'] = $closing;

        return [
            'opening' => $opening,
            'closing' => $closing,
            'lines' => $lines,
            'from' => $fromDate,
            'to' => $toDate,
            'summary' => $summary['totals'],
            'warnings' => $summary['warnings'],
        ];
    }

    private function linesByPropertyUsers(array $userIds, ?int $companyId, string $from, ?string $to, array $accounts = []): Collection
    {
        $accountIds = collect($accounts['ar_ids'] ?? [])
            ->merge($accounts['adv_ids'] ?? [])
            ->filter()
            ->unique()
            ->values()
            ->all();

        $q = GlJournalLine::query()
            ->select([
                'gl_journal_lines.*',
                'gl_journals.date',
                'gl_journals.memo',
                'gl_journals.source_type',
                'gl_journals.source_id',
                'gl_accounts.code',
                'gl_accounts.name as account_name',
                'gl_accounts.type as account_type',
            ])
            ->join('gl_journals', 'gl_journal_lines.gl_journal_id', '=', 'gl_journals.id')
            ->join('gl_accounts', 'gl_journal_lines.gl_account_id', '=', 'gl_accounts.id')
            ->where('gl_journals.date', '>=', $from)
            ->whereIn('gl_journal_lines.user_id', $userIds);

        if (!empty($accountIds)) {
            $q->whereIn('gl_journal_lines.gl_account_id', $accountIds);
        }

        if ($companyId) {
            $q->where('gl_journal_lines.company_id', $companyId);
        }
        if ($to) {
            $q->where('gl_journals.date', '<=', $to);
        }

        return $q->orderBy('gl_journals.date')
            ->orderBy('gl_journal_lines.id')
            ->get()
            ->map(fn ($row) => $this->mapLine($row));
    }

    private function sumNetByPropertyUsers(array $userIds, ?int $companyId, string $before, array $accounts = []): float
    {
        $q = GlJournalLine::query()
            ->join('gl_journals', 'gl_journal_lines.gl_journal_id', '=', 'gl_journals.id')
            ->join('gl_accounts', 'gl_journal_lines.gl_account_id', '=', 'gl_accounts.id')
            ->where('gl_journals.date', '<', $before)
            ->whereIn('gl_journal_lines.user_id', $userIds);

        $accountIds = collect($accounts['ar_ids'] ?? [])
            ->merge($accounts['adv_ids'] ?? [])
            ->filter()
            ->unique()
            ->values()
            ->all();
        if (!empty($accountIds)) {
            $q->whereIn('gl_journal_lines.gl_account_id', $accountIds);
        }

        if ($companyId) {
            $q->where('gl_journal_lines.company_id', $companyId);
        }

        return (float) $q->get()->sum(function ($row) use ($accounts) {
            $scoped = $this->deltaAccountScoped(
                (int)$row->gl_account_id,
                (float)$row->debit,
                (float)$row->credit,
                $accounts
            );
            if ($scoped !== null) {
                return $scoped;
            }
            return $this->delta((float)$row->debit, (float)$row->credit, $row->type);
        });
    }

    private function propertyArSummary(array $userIds, ?int $companyId, string $from, ?string $to, array $accounts): array
    {
        $warnings = [];
        $arIds = $accounts['ar_ids'] ?? [];
        $advIds = $accounts['adv_ids'] ?? [];
        if (empty($arIds)) {
            $warnings[] = 'default_ar_account_id is not set; invoiced/paid totals may be zero.';
        }

        $invoiced = 0.0;
        $paid = 0.0;

        if (!empty($arIds)) {
            $q = GlJournalLine::query()
                ->join('gl_journals', 'gl_journal_lines.gl_journal_id', '=', 'gl_journals.id')
                ->whereIn('gl_journal_lines.gl_account_id', $arIds)
                ->where('gl_journals.date', '>=', $from)
                ->whereIn('gl_journal_lines.user_id', $userIds);

            if ($companyId) {
                $q->where('gl_journal_lines.company_id', $companyId);
            }
            if ($to) {
                $q->where('gl_journals.date', '<=', $to);
            }

            $rows = $q->get(['gl_journal_lines.debit', 'gl_journal_lines.credit']);
            $invoiced = (float) $rows->sum('debit');
            $paid = (float) $rows->sum('credit');
        }

        if (!empty($advIds)) {
            $qAdv = GlJournalLine::query()
                ->join('gl_journals', 'gl_journal_lines.gl_journal_id', '=', 'gl_journals.id')
                ->whereIn('gl_journal_lines.gl_account_id', $advIds)
                ->where('gl_journals.date', '>=', $from)
                ->whereIn('gl_journal_lines.user_id', $userIds);

            if ($companyId) {
                $qAdv->where('gl_journal_lines.company_id', $companyId);
            }
            if ($to) {
                $qAdv->where('gl_journals.date', '<=', $to);
            }

            $advRows = $qAdv->get(['gl_journal_lines.debit', 'gl_journal_lines.credit']);
            $paid += (float) $advRows->sum('credit') - (float) $advRows->sum('debit');
        }

        return [
            'totals' => [
                'invoiced' => $invoiced,
                'paid' => $paid,
                'balance_due' => null,
            ],
            'warnings' => $warnings,
        ];
    }



    private function arSummary(int $userId, ?int $companyId, string $from, ?string $to, array $accounts): array
    {
        $warnings = [];
        $arIds = $accounts['ar_ids'] ?? [];
        $advIds = $accounts['adv_ids'] ?? [];
        if (empty($arIds)) {
            $warnings[] = 'default_ar_account_id is not set; invoiced/paid totals may be zero.';
        }

        $invoiced = 0.0;
        $paid = 0.0;

        if (!empty($arIds)) {
            $q = GlJournalLine::query()
                ->join('gl_journals', 'gl_journal_lines.gl_journal_id', '=', 'gl_journals.id')
                ->whereIn('gl_journal_lines.gl_account_id', $arIds)
                ->where('gl_journals.date', '>=', $from)
                ->where('gl_journal_lines.user_id', $userId);

            if ($companyId) {
                $q->where('gl_journal_lines.company_id', $companyId);
            }
            if ($to) {
                $q->where('gl_journals.date', '<=', $to);
            }

            $rows = $q->get(['gl_journal_lines.debit', 'gl_journal_lines.credit']);
            $invoiced = (float) $rows->sum('debit');
            $paid = (float) $rows->sum('credit'); // all AR credits count as payments (cash, card, credit notes, applied advances)
        }

        // Treat advance receipts as payments that reduce the balance
        if (!empty($advIds)) {
            $qAdv = GlJournalLine::query()
                ->join('gl_journals', 'gl_journal_lines.gl_journal_id', '=', 'gl_journals.id')
                ->whereIn('gl_journal_lines.gl_account_id', $advIds)
                ->where('gl_journals.date', '>=', $from)
                ->where('gl_journal_lines.user_id', $userId);

            if ($companyId) {
                $qAdv->where('gl_journal_lines.company_id', $companyId);
            }
            if ($to) {
                $qAdv->where('gl_journals.date', '<=', $to);
            }

            $advRows = $qAdv->get(['gl_journal_lines.debit', 'gl_journal_lines.credit']);
            // Credits to advances are cash received; debits are applications (reduce the advance balance)
            $paid += (float) $advRows->sum('credit') - (float) $advRows->sum('debit');
        }

        return [
            'totals' => [
                'invoiced' => $invoiced,
                'paid' => $paid,
                'balance_due' => null, // filled by caller using closing
            ],
            'warnings' => $warnings,
        ];
    }

    private function linesByUser(int $userId, ?int $companyId, string $from, ?string $to, array $accounts = []): Collection
    {
        $accountIds = collect($accounts['ar_ids'] ?? [])
            ->merge($accounts['adv_ids'] ?? [])
            ->filter()
            ->unique()
            ->values()
            ->all();

        $q = GlJournalLine::query()
            ->select([
                'gl_journal_lines.*',
                'gl_journals.date',
                'gl_journals.memo',
                'gl_journals.source_type',
                'gl_journals.source_id',
                'gl_accounts.code',
                'gl_accounts.name as account_name',
                'gl_accounts.type as account_type',
            ])
            ->join('gl_journals', 'gl_journal_lines.gl_journal_id', '=', 'gl_journals.id')
            ->join('gl_accounts', 'gl_journal_lines.gl_account_id', '=', 'gl_accounts.id')
            ->where('gl_journals.date', '>=', $from)
            ->where('gl_journal_lines.user_id', $userId);

        if (!empty($accountIds)) {
            $q->whereIn('gl_journal_lines.gl_account_id', $accountIds);
        }

        if ($companyId) {
            $q->where('gl_journal_lines.company_id', $companyId);
        }
        if ($to) {
            $q->where('gl_journals.date', '<=', $to);
        }

        return $q->orderBy('gl_journals.date')
            ->orderBy('gl_journal_lines.id')
            ->get()
            ->map(fn ($row) => $this->mapLine($row));
    }

    private function linesByAccount(int $accountId, ?int $companyId, string $from, ?string $to): Collection
    {
        $q = GlJournalLine::query()
            ->select([
                'gl_journal_lines.*',
                'gl_journals.date',
                'gl_journals.memo',
                'gl_journals.source_type',
                'gl_journals.source_id',
                'gl_accounts.code',
                'gl_accounts.name as account_name',
                'gl_accounts.type as account_type',
            ])
            ->join('gl_journals', 'gl_journal_lines.gl_journal_id', '=', 'gl_journals.id')
            ->join('gl_accounts', 'gl_journal_lines.gl_account_id', '=', 'gl_accounts.id')
            ->where('gl_journal_lines.gl_account_id', $accountId)
            ->where('gl_journals.date', '>=', $from);

        if ($companyId) {
            $q->where('gl_journal_lines.company_id', $companyId);
        }
        if ($to) {
            $q->where('gl_journals.date', '<=', $to);
        }

        return $q->orderBy('gl_journals.date')
            ->orderBy('gl_journal_lines.id')
            ->get()
            ->map(fn ($row) => $this->mapLine($row));
    }

    private function mapLine($row): array
    {
        return [
            'id' => $row->id,
            'gl_account_id' => $row->gl_account_id,
            'date' => $row->date,
            'memo' => $row->memo,
            'source_type' => $row->source_type,
            'source_id' => $row->source_id,
            'account_code' => $row->code,
            'account_name' => $row->account_name,
            'account_type' => $row->account_type,
            'debit' => (float) $row->debit,
            'credit' => (float) $row->credit,
        ];
    }

    private function attachRunning(Collection $lines, float $opening, ?string $forcedAccountType = null, array $accounts = []): array
    {
        $running = $opening;
        $mapped = $lines->map(function ($line) use (&$running, $forcedAccountType, $accounts) {
            $type = $forcedAccountType ?? $line['account_type'] ?? 'asset';
            $delta = $this->deltaAccountScoped(
                (int)$line['gl_account_id'],
                $line['debit'],
                $line['credit'],
                $accounts
            );
            if ($delta === null) {
                $delta = $this->delta($line['debit'], $line['credit'], $type);
            }
            $running += $delta;
            return array_merge($line, [
                'delta' => $delta,
                'running' => $running,
            ]);
        });

        return [$mapped, $running];
    }

    private function sumNetByUser(int $userId, ?int $companyId, string $before, array $accounts = []): float
    {
        $q = GlJournalLine::query()
            ->join('gl_journals', 'gl_journal_lines.gl_journal_id', '=', 'gl_journals.id')
            ->join('gl_accounts', 'gl_journal_lines.gl_account_id', '=', 'gl_accounts.id')
            ->where('gl_journals.date', '<', $before)
            ->where('gl_journal_lines.user_id', $userId);

        $accountIds = collect($accounts['ar_ids'] ?? [])
            ->merge($accounts['adv_ids'] ?? [])
            ->filter()
            ->unique()
            ->values()
            ->all();
        if (!empty($accountIds)) {
            $q->whereIn('gl_journal_lines.gl_account_id', $accountIds);
        }

        if ($companyId) {
            $q->where('gl_journal_lines.company_id', $companyId);
        }

        return (float) $q->get()->sum(function ($row) use ($accounts) {
            $scoped = $this->deltaAccountScoped(
                (int)$row->gl_account_id,
                (float)$row->debit,
                (float)$row->credit,
                $accounts
            );
            if ($scoped !== null) {
                return $scoped;
            }
            return $this->delta((float)$row->debit, (float)$row->credit, $row->type);
        });
    }

    private function sumByAccount(int $accountId, ?int $companyId, string $before): float
    {
        $type = $this->accountType($accountId);

        $q = GlJournalLine::query()
            ->join('gl_journals', 'gl_journal_lines.gl_journal_id', '=', 'gl_journals.id')
            ->where('gl_journals.date', '<', $before)
            ->where('gl_journal_lines.gl_account_id', $accountId);

        if ($companyId) {
            $q->where('gl_journal_lines.company_id', $companyId);
        }

        return (float) $q->get()->sum(function ($row) use ($type) {
            return $this->delta((float) $row->debit, (float) $row->credit, $type);
        });
    }

    private function accountType(int $accountId): string
    {
        return GlAccount::where('id', $accountId)->value('type') ?: 'asset';
    }

    private function delta(float $debit, float $credit, ?string $accountType): float
    {
        $type = strtolower($accountType ?: 'asset');
        $raw = $debit - $credit; // positive when debit dominates
        // Only liabilities/equity invert; assets/expenses/income use raw
        $sign = in_array($type, ['liability', 'equity'], true) ? -1 : 1;
        return $raw * $sign;
    }

    private function deltaAccountScoped(int $accountId, float $debit, float $credit, array $accounts): ?float
    {
        $arIds = collect($accounts['ar_ids'] ?? [])
            ->merge(array_filter([$accounts['ar'] ?? null]))
            ->unique()
            ->all();
        $advIds = collect($accounts['adv_ids'] ?? [])
            ->merge(array_filter([$accounts['adv'] ?? null]))
            ->unique()
            ->all();

        if (!empty($arIds) && in_array($accountId, $arIds, true)) {
            return $debit - $credit;
        }
        if (!empty($advIds) && in_array($accountId, $advIds, true)) {
            // Advances reduce the customer's balance (credit = payment), so same sign as AR
            return $debit - $credit;
        }
        return null;
    }

    private function contactAccounts(): array
    {
        $arIds = [];
        $fromSetting = BusinessSetting::where('type', 'default_ar_account_id')->value('value');
        if ($fromSetting) {
            $arIds[] = (int) $fromSetting;
        }
        foreach (['1100', '1200'] as $code) {
            $id = GlAccount::where('code', $code)->value('id');
            if ($id) {
                $arIds[] = (int) $id;
            }
        }
        $arIds = array_values(array_unique($arIds));

        $advId = GlAccount::where('code', '2200')->value('id');
        $advIds = $advId ? [(int) $advId] : [];

        return [
            'ar' => $arIds[0] ?? null,
            'ar_ids' => $arIds,
            'adv' => $advIds[0] ?? null,
            'adv_ids' => $advIds,
        ];
    }
}
