<?php

namespace App\Console\Commands;

use App\Models\SysSaleInvoice;
use App\Models\User;
use App\Services\Accounting\SaleInvoiceLifecycleService;
use App\Services\Accounting\SaleInvoicePenaltyService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SaleInvoicesPenaltySmokeTest extends Command
{
    protected $signature = 'sale-invoices:penalty-smoke-test {--fail-fast : Stop at first failure}';
    protected $description = 'Smoke-test sale invoice late payment penalty scenarios';

    public function handle(): int
    {
        $this->info('Running sale invoice penalty smoke tests...');

        $service = app(SaleInvoiceLifecycleService::class);
        $penaltyService = app(SaleInvoicePenaltyService::class);

        $now = Carbon::now()->copy();
        $today = $now->copy()->startOfDay();
        $dueOverdue = $today->copy()->subDay()->toDateString(); // definitely overdue when grace_days=0

        $user = User::query()->inRandomOrder()->first();
        if (! $user) {
            $this->error('No users found in database. Cannot run smoke test.');
            return self::FAILURE;
        }

        $itemsPayload = [
            [
                'item_name' => 'Penalty smoke item',
                'description' => 'Line for penalty smoke test',
                'quantity' => 1,
                'rate' => 100,
                'discount' => 0,
                'tax_id' => null,
                'tax_rate' => 0,
                'notes' => null,
            ],
        ];

        $fails = [];

        // 1) Percentage penalty (amount_input ignored)
        $invPercent = $this->createInvoice($service, $user, $today->copy(), $dueOverdue, [
            'penalty_enabled' => true,
            'penalty_type' => 'percentage',
            'penalty_fixed_rate' => 10, // 10%
            'penalty_grace_days' => 0,
            'penalty_max_amount' => null,
        ], $itemsPayload);

        $applied1 = $penaltyService->applyPenaltyIfEligible($invPercent, $now);
        $invPercent->refresh();
        $this->assertOrCollect($applied1 === true, 'Percent: penalty should apply when overdue', $fails, $this->option('fail-fast'));
        $this->assertOrCollect((float) $invPercent->penalty_amount_applied === 10.00, 'Percent: penalty amount should be 10.00', $fails, $this->option('fail-fast'));
        $this->assertOrCollect((float) $invPercent->total_amount === 110.00, 'Percent: total_amount should include penalty once', $fails, $this->option('fail-fast'));
        $this->assertOrCollect((float) $invPercent->balance_amount === 110.00, 'Percent: balance_amount should include penalty once', $fails, $this->option('fail-fast'));
        $this->assertOrCollect($invPercent->status === 'issued', 'Percent: status should become issued (no payments)', $fails, $this->option('fail-fast'));

        // Idempotency: apply again should do nothing
        $applied2 = $penaltyService->applyPenaltyIfEligible($invPercent, $now);
        $invPercent->refresh();
        $this->assertOrCollect($applied2 === false, 'Percent: second apply should be skipped', $fails, $this->option('fail-fast'));
        $this->assertOrCollect((float) $invPercent->total_amount === 110.00, 'Percent: total_amount should not increase twice', $fails, $this->option('fail-fast'));

        // 2) Flat penalty
        $invFlat = $this->createInvoice($service, $user, $today->copy(), $dueOverdue, [
            'penalty_enabled' => true,
            'penalty_type' => 'flat_rate',
            'penalty_fixed_rate' => 25, // flat 25 currency
            'penalty_grace_days' => 0,
            'penalty_max_amount' => null,
        ], $itemsPayload);

        $appliedFlat = $penaltyService->applyPenaltyIfEligible($invFlat, $now);
        $invFlat->refresh();
        $this->assertOrCollect($appliedFlat === true, 'Flat: penalty should apply when overdue', $fails, $this->option('fail-fast'));
        $this->assertOrCollect((float) $invFlat->penalty_amount_applied === 25.00, 'Flat: penalty amount should be 25.00', $fails, $this->option('fail-fast'));
        $this->assertOrCollect((float) $invFlat->total_amount === 125.00, 'Flat: total_amount should include flat penalty', $fails, $this->option('fail-fast'));

        // 3) Grace days: not overdue yet
        $dueWithinGrace = $today->copy()->subDay()->toDateString(); // overdue by due_date, but grace should postpone application
        $invGrace = $this->createInvoice($service, $user, $today->copy(), $dueWithinGrace, [
            'penalty_enabled' => true,
            'penalty_type' => 'percentage',
            'penalty_fixed_rate' => 10,
            'penalty_grace_days' => 2, // due_date + 2 >= today => should NOT apply
            'penalty_max_amount' => null,
        ], $itemsPayload);

        $appliedGrace = $penaltyService->applyPenaltyIfEligible($invGrace, $now);
        $invGrace->refresh();
        $this->assertOrCollect($appliedGrace === false, 'Grace: penalty should not apply during grace window', $fails, $this->option('fail-fast'));
        $this->assertOrCollect(empty($invGrace->penalty_applied_at), 'Grace: penalty_applied_at should remain null', $fails, $this->option('fail-fast'));
        $this->assertOrCollect((float) $invGrace->total_amount === 100.00, 'Grace: totals should remain unchanged', $fails, $this->option('fail-fast'));

        // 4) Cap behavior
        $invCap = $this->createInvoice($service, $user, $today->copy(), $dueOverdue, [
            'penalty_enabled' => true,
            'penalty_type' => 'percentage',
            'penalty_fixed_rate' => 50, // 50%
            'penalty_grace_days' => 0,
            'penalty_max_amount' => 10, // cap at 10
        ], $itemsPayload);

        $appliedCap = $penaltyService->applyPenaltyIfEligible($invCap, $now);
        $invCap->refresh();
        $this->assertOrCollect($appliedCap === true, 'Cap: penalty should apply when overdue', $fails, $this->option('fail-fast'));
        $this->assertOrCollect((float) $invCap->penalty_amount_applied === 10.00, 'Cap: penalty should be capped to 10.00', $fails, $this->option('fail-fast'));
        $this->assertOrCollect((float) $invCap->total_amount === 110.00, 'Cap: total_amount should include capped penalty once', $fails, $this->option('fail-fast'));

        // 5) Recurring child penalty settings copied, but applied fields not copied
        $masterDate = $today->copy()->startOfMonth()->startOfDay();
        $masterDue = $masterDate->copy()->addDays(30);
        $master = $this->createRecurringMasterInvoice($service, $user, $masterDate, $masterDue, [
            'recurring_month_interval' => 1,
            'unlimited_cycles' => false,
            'recurring_cycles' => 2,
            'recurring_custom_unit' => null,
        ], [
            'penalty_enabled' => true,
            'penalty_type' => 'percentage',
            'penalty_fixed_rate' => 5,
            'penalty_gl_account_id' => null,
            'penalty_grace_days' => 0,
            'penalty_max_amount' => 7,
        ], $itemsPayload);

        $this->call('sale-invoices:generate-recurring', ['--master_id' => $master->id]);

        $child = SysSaleInvoice::query()
            ->where('recurring_master_invoice_id', $master->id)
            ->where('recurring_sequence', 2)
            ->first();

        $this->assertOrCollect((bool) $child, 'Recurring: child seq2 should be generated', $fails, $this->option('fail-fast'));

        if ($child) {
            $this->assertOrCollect(empty($child->penalty_applied_at), 'Recurring: child penalty_applied_at must be null', $fails, $this->option('fail-fast'));
            $this->assertOrCollect(empty($child->penalty_amount_applied), 'Recurring: child penalty_amount_applied must be null', $fails, $this->option('fail-fast'));
            $this->assertOrCollect((bool) $child->penalty_enabled === true, 'Recurring: child penalty_enabled copied', $fails, $this->option('fail-fast'));
            $this->assertOrCollect($child->penalty_type === 'percentage', 'Recurring: child penalty_type copied', $fails, $this->option('fail-fast'));
            $this->assertOrCollect((float) $child->penalty_fixed_rate === 5.00, 'Recurring: child penalty_fixed_rate copied', $fails, $this->option('fail-fast'));
            $this->assertOrCollect((int) $child->penalty_grace_days === 0, 'Recurring: child penalty_grace_days copied', $fails, $this->option('fail-fast'));
            $this->assertOrCollect((float) $child->penalty_max_amount === 7.00, 'Recurring: child penalty_max_amount copied', $fails, $this->option('fail-fast'));
        }

        if (!empty($fails)) {
            $this->error('Smoke test FAILED:');
            foreach ($fails as $fail) {
                $this->line('- ' . $fail);
            }
            return self::FAILURE;
        }

        $this->info('All penalty smoke tests PASSED.');
        return self::SUCCESS;
    }

    private function createInvoice(
        SaleInvoiceLifecycleService $service,
        User $user,
        Carbon $invoiceDate,
        string $dueDate,
        array $penalty,
        array $itemsPayload
    ): SysSaleInvoice {
        $invoiceNo = $service->nextInvoiceNo();

        $data = array_merge([
            'invoice_no' => $invoiceNo,
            'user_id' => $user->id,
            'invoice_header_id' => null,
            'invoice_date' => $invoiceDate->toDateString(),
            'due_date' => $dueDate,
            'status' => 'draft',
            'notes' => 'Penalty smoke test',

            'link_to_type' => 'Property',
            'link_to_id' => (int) $user->id,
            'charge_to_type' => 'Tenant',
            'charge_to_id' => (int) $user->id,
            'bank_account_id' => null,

            'items' => $itemsPayload,
        ], [
            'penalty_enabled' => $penalty['penalty_enabled'] ?? false,
            'penalty_type' => $penalty['penalty_type'] ?? null,
            'penalty_fixed_rate' => $penalty['penalty_fixed_rate'] ?? null,
            'penalty_gl_account_id' => $penalty['penalty_gl_account_id'] ?? null,
            'penalty_grace_days' => $penalty['penalty_grace_days'] ?? 0,
            'penalty_max_amount' => $penalty['penalty_max_amount'] ?? null,
        ]);

        return $service->persistItems($data, null);
    }

    private function createRecurringMasterInvoice(
        SaleInvoiceLifecycleService $service,
        User $user,
        Carbon $masterDate,
        Carbon $dueDate,
        array $recurringOverrides,
        array $penalty,
        array $itemsPayload
    ): SysSaleInvoice {
        $invoiceNo = $service->nextInvoiceNo();

        $data = [
            'invoice_no' => $invoiceNo,
            'user_id' => $user->id,
            'invoice_header_id' => null,
            'invoice_date' => $masterDate->toDateString(),
            'due_date' => $dueDate->toDateString(),
            'status' => 'draft',
            'notes' => 'Recurring penalty smoke test master',

            'link_to_type' => 'Property',
            'link_to_id' => (int) $user->id,
            'charge_to_type' => 'Tenant',
            'charge_to_id' => (int) $user->id,
            'bank_account_id' => null,

            'recurring_master_invoice_id' => null,
            'recurring_sequence' => 1,
            'recurring_month_interval' => $recurringOverrides['recurring_month_interval'] ?? null,
            'recurring_custom_interval' => $recurringOverrides['recurring_custom_interval'] ?? null,
            'recurring_custom_unit' => $recurringOverrides['recurring_custom_unit'] ?? null,
            'unlimited_cycles' => $recurringOverrides['unlimited_cycles'] ?? false,
            'recurring_cycles' => $recurringOverrides['recurring_cycles'] ?? null,

            'penalty_enabled' => $penalty['penalty_enabled'] ?? false,
            'penalty_type' => $penalty['penalty_type'] ?? null,
            'penalty_fixed_rate' => $penalty['penalty_fixed_rate'] ?? null,
            'penalty_gl_account_id' => $penalty['penalty_gl_account_id'] ?? null,
            'penalty_grace_days' => $penalty['penalty_grace_days'] ?? 0,
            'penalty_max_amount' => $penalty['penalty_max_amount'] ?? null,

            'items' => $itemsPayload,
        ];

        return $service->persistItems($data, null);
    }

    private function assertOrCollect(
        bool $condition,
        string $message,
        array &$fails,
        bool $failFast
    ): void {
        if ($condition) {
            $this->info('[OK] ' . $message);
            return;
        }

        $fails[] = $message;
        $this->error('[FAIL] ' . $message);

        if ($failFast) {
            throw new \RuntimeException($message);
        }
    }
}

