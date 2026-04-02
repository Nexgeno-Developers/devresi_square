<?php

namespace App\Console\Commands;

use App\Http\Controllers\Backend\Accounting\Sale\SaleInvoiceController;
use App\Models\SysSaleInvoice;
use App\Services\Accounting\SaleInvoiceLifecycleService;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Carbon\Carbon;

class RecurringSaleInvoicesSmokeTest extends Command
{
    protected $signature = 'sale-invoices:recurring-smoke-test {--fail-fast : Stop at first failure}';
    protected $description = 'Smoke-test recurring sale invoice scenarios (generation + master/child edit rules)';

    public function handle(): int
    {
        $this->info('Running recurring sale invoice smoke tests...');

        $service = app(SaleInvoiceLifecycleService::class);
        $now = Carbon::now()->copy();
        $masterDate = $now->copy()->startOfMonth()->startOfDay();
        $dueDate = $masterDate->copy()->addDays(30);

        $user = User::query()->inRandomOrder()->first();
        if (! $user) {
            $this->error('No users found in database. Cannot run smoke test.');
            return self::FAILURE;
        }

        $itemsPayload = [
            [
                'item_name' => 'Smoke test item',
                'description' => 'Line for recurring invoice smoke test',
                'quantity' => 1,
                'rate' => 100,
                'discount' => 0,
                'tax_id' => null,
                'tax_rate' => 0,
                'notes' => null,
            ],
        ];

        $fails = [];

        // Scenario 1: Preset month interval + finite cycles
        $masterFinite = $this->createMasterInvoice($service, $user, $masterDate, $dueDate, [
            'recurring_month_interval' => 1,
            'unlimited_cycles' => false,
            'recurring_cycles' => 6,
            'recurring_custom_unit' => null,
        ], $itemsPayload);

        $this->call('sale-invoices:generate-recurring', ['--master_id' => $masterFinite->id]);

        $childSeq2 = SysSaleInvoice::query()
            ->where('recurring_master_invoice_id', $masterFinite->id)
            ->where('recurring_sequence', 2)
            ->first();
        $childSeq3 = SysSaleInvoice::query()
            ->where('recurring_master_invoice_id', $masterFinite->id)
            ->where('recurring_sequence', 3)
            ->first();

        $expectedSeq2 = $masterDate->copy()->addMonths(1)->toDateString();
        $expectedSeq3 = $masterDate->copy()->addMonths(2)->toDateString();

        $this->assertOrCollect(
            $childSeq2 && $childSeq2->invoice_date === $expectedSeq2,
            'Finite recurring: child seq2 should exist with correct invoice_date'
        , $fails, $this->option('fail-fast'));
        $this->assertOrCollect(
            $childSeq3 && $childSeq3->invoice_date === $expectedSeq3,
            'Finite recurring: child seq3 should exist with correct invoice_date'
        , $fails, $this->option('fail-fast'));

        // Scenario 2: Editing master affects future generations
        // Change interval from 1 month -> 2 months, then delete seq6 and ensure seq6 date changes accordingly.
        $childSeq6 = SysSaleInvoice::query()
            ->where('recurring_master_invoice_id', $masterFinite->id)
            ->where('recurring_sequence', 6)
            ->first();

        if ($childSeq6) {
            $masterFinite->update([
                'recurring_month_interval' => 2,
                'recurring_custom_interval' => null,
                'recurring_custom_unit' => null,
            ]);
            $childSeq6->delete();

            $this->call('sale-invoices:generate-recurring', ['--master_id' => $masterFinite->id]);

            $recreatedSeq6 = SysSaleInvoice::query()
                ->where('recurring_master_invoice_id', $masterFinite->id)
                ->where('recurring_sequence', 6)
                ->first();

            $expectedSeq6 = $masterDate->copy()->addMonths(10)->toDateString(); // (seq6 - seq1) * 2 months = 5*2 = 10
            $this->assertOrCollect(
                $recreatedSeq6 && $recreatedSeq6->invoice_date === $expectedSeq6,
                'Master edit: seq6 invoice_date should follow updated interval for missing sequence'
            , $fails, $this->option('fail-fast'));
        } else {
            $this->assertOrCollect(false, 'Master edit: expected child seq6 exists for deletion test', $fails, $this->option('fail-fast'));
        }

        // Scenario 3: Custom + unlimited (custom unit = month)
        $masterUnlimited = $this->createMasterInvoice($service, $user, $masterDate, $dueDate, [
            'recurring_month_interval' => null,
            'unlimited_cycles' => true,
            'recurring_cycles' => null,
            'recurring_custom_interval' => 1,
            'recurring_custom_unit' => 'year',
        ], $itemsPayload);

        $this->call('sale-invoices:generate-recurring', ['--master_id' => $masterUnlimited->id]);

        $childUnlimitedSeq2 = SysSaleInvoice::query()
            ->where('recurring_master_invoice_id', $masterUnlimited->id)
            ->where('recurring_sequence', 2)
            ->first();
        $expectedUnlimitedSeq2 = $masterDate->copy()->addYears(1)->toDateString();

        $this->assertOrCollect(
            $childUnlimitedSeq2 && $childUnlimitedSeq2->invoice_date === $expectedUnlimitedSeq2,
            'Unlimited custom: child seq2 should exist with correct invoice_date'
        , $fails, $this->option('fail-fast'));

        // Scenario 4: Updating a child invoice should not change recurrence config columns.
        // Attempt to update child seq2 with a conflicting recurrence request.
        $childForEdit = $childSeq2 ?? null;
        if ($childForEdit) {
            $originalUnlimited = (bool) ($childForEdit->unlimited_cycles ?? false);
            $originalCycles = $childForEdit->recurring_cycles;

            $itemsForRequest = $childForEdit->items->map(function ($it) {
                return [
                    'item_name' => $it->item_name,
                    'description' => $it->description,
                    'quantity' => $it->quantity,
                    'rate' => $it->rate,
                    'discount' => $it->discount,
                    'tax_id' => $it->tax_id,
                    'tax_rate' => $it->tax_rate,
                    'notes' => $it->notes,
                ];
            })->toArray();

            $controller = app(SaleInvoiceController::class);
            $request = Request::create('/backend/accounting/sale/invoices/'.$childForEdit->id, 'PUT', [
                'invoice_date' => $childForEdit->invoice_date,
                'due_date' => $childForEdit->due_date,
                'status' => $childForEdit->status ?? 'draft',
                'invoice_header_id' => $childForEdit->invoice_header_id,
                'link_to_type' => $childForEdit->link_to_type,
                'link_to_id' => $childForEdit->link_to_id,
                'charge_to_type' => $childForEdit->charge_to_type,
                'charge_to_id' => $childForEdit->charge_to_id,
                'bank_account_id' => $childForEdit->bank_account_id,
                'notes' => $childForEdit->notes,

                // Attempted conflicting recurrence update (should be ignored for child invoices)
                'recurring' => 'custom',
                'repeat_every_custom' => 2,
                'repeat_type_custom' => 'month',
                'unlimited_cycles' => 1,
                'recurring_cycles' => 999,

                'items' => $itemsForRequest,
            ]);

            try {
                $controller->update($request, $childForEdit->id);
            } catch (\Throwable $e) {
                // Redirect/session issues shouldn't affect DB state; still keep going.
                $this->warn('Child update threw exception (possibly redirect/session). Continuing. Error: ' . $e->getMessage());
            }

            $childForEdit->refresh();
            $this->assertOrCollect(
                ((bool) ($childForEdit->unlimited_cycles ?? false)) === $originalUnlimited
                && $childForEdit->recurring_cycles === $originalCycles,
                'Child invoice update: recurrence config should remain unchanged'
            , $fails, $this->option('fail-fast'));
        }

        if (!empty($fails)) {
            $this->error('Smoke test FAILED:');
            foreach ($fails as $fail) {
                $this->line('- ' . $fail);
            }
            return self::FAILURE;
        }

        $this->info('All recurring sale invoice smoke tests PASSED.');
        return self::SUCCESS;
    }

    private function createMasterInvoice(
        SaleInvoiceLifecycleService $service,
        User $user,
        Carbon $masterDate,
        Carbon $dueDate,
        array $recurringOverrides,
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
            'notes' => 'Recurring smoke test master',

            'link_to_type' => 'Property',
            'link_to_id' => (int) $user->id,
            'charge_to_type' => 'Tenant',
            'charge_to_id' => (int) $user->id,
            'bank_account_id' => null,

            // Master recurrence metadata
            'recurring_master_invoice_id' => null,
            'recurring_sequence' => 1,
            'recurring_month_interval' => $recurringOverrides['recurring_month_interval'] ?? null,
            'recurring_custom_interval' => $recurringOverrides['recurring_custom_interval'] ?? null,
            'recurring_custom_unit' => $recurringOverrides['recurring_custom_unit'] ?? null,
            'unlimited_cycles' => $recurringOverrides['unlimited_cycles'] ?? false,
            'recurring_cycles' => $recurringOverrides['recurring_cycles'] ?? null,

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

