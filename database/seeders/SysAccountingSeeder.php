<?php

namespace Database\Seeders;

use App\Models\SysAdjustmentNote;
use App\Models\SysExpenseCategory;
use App\Models\SysIncomeCategory;
use App\Models\SysPayment;
use App\Models\SysPurchaseInvoice;
use App\Models\SysPurchaseInvoiceItem;
use App\Models\SysReceipt;
use App\Models\SysRefund;
use App\Models\SysSaleInvoice;
use App\Models\SysSaleInvoiceItem;
use App\Models\SysTax;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SysAccountingSeeder extends Seeder
{
    public function run(): void
    {
        if (!DB::getSchemaBuilder()->hasTable('sys_sale_invoices')) {
            return;
        }

        $userIds = User::query()->pluck('id')->all();
        if (empty($userIds)) {
            return;
        }

        SysTax::factory()->count(8)->create();
        SysIncomeCategory::factory()->count(6)->create();
        SysExpenseCategory::factory()->count(6)->create();

        $this->ensurePaymentMethods();
        $this->ensureBankAccounts($userIds);

        $saleInvoices = SysSaleInvoice::factory()
            ->count(20)
            ->state(fn () => ['user_id' => fake()->randomElement($userIds)])
            ->create();

        $purchaseInvoices = SysPurchaseInvoice::factory()
            ->count(20)
            ->state(fn () => ['user_id' => fake()->randomElement($userIds)])
            ->create();

        foreach ($saleInvoices as $invoice) {
            SysSaleInvoiceItem::factory()
                ->count(fake()->numberBetween(1, 4))
                ->create([
                    'sale_invoice_id' => $invoice->id,
                ]);
        }

        foreach ($purchaseInvoices as $invoice) {
            SysPurchaseInvoiceItem::factory()
                ->count(fake()->numberBetween(1, 4))
                ->create([
                    'purchase_invoice_id' => $invoice->id,
                ]);
        }

        $allReferenceOptions = $saleInvoices->map(fn ($i) => ['type' => 'sale_invoice', 'id' => $i->id])
            ->concat($purchaseInvoices->map(fn ($i) => ['type' => 'purchase_invoice', 'id' => $i->id]))
            ->values()
            ->all();

        $notes = collect();
        for ($i = 0; $i < 15; $i++) {
            $ref = fake()->randomElement($allReferenceOptions);

            $notes->push(
                SysAdjustmentNote::factory()->create([
                    'reference_type' => $ref['type'],
                    'reference_id' => $ref['id'],
                    'user_id' => fake()->randomElement($userIds),
                ])
            );
        }

        SysPayment::factory()->count(25)->create([
            'user_id' => fake()->randomElement($userIds),
        ]);

        foreach ($notes->take(8) as $note) {
            SysRefund::factory()->create([
                'adjustment_note_id' => $note->id,
                'user_id' => $note->user_id,
            ]);
        }

        // Create receipts against real sale/purchase invoices so morph data stays valid.
        foreach ($saleInvoices->take(10) as $invoice) {
            SysReceipt::factory()->create([
                'receiptable_type' => SysSaleInvoice::class,
                'receiptable_id' => $invoice->id,
                'user_id' => $invoice->user_id,
            ]);
        }

        foreach ($purchaseInvoices->take(10) as $invoice) {
            SysReceipt::factory()->create([
                'receiptable_type' => SysPurchaseInvoice::class,
                'receiptable_id' => $invoice->id,
                'user_id' => $invoice->user_id,
            ]);
        }
    }

    private function ensurePaymentMethods(): void
    {
        if (DB::table('payment_methods')->count() > 0) {
            return;
        }

        $now = now();
        DB::table('payment_methods')->insert([
            ['name' => 'Cash', 'code' => 'CASH', 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Bank Transfer', 'code' => 'BANK', 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Card', 'code' => 'CARD', 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    private function ensureBankAccounts(array $userIds): void
    {
        if (DB::table('sys_bank_accounts')->count() > 0) {
            return;
        }

        $now = now();
        $rows = [];
        $numRows = max(3, min(5, count($userIds)));

        for ($i = 0; $i < $numRows; $i++) {
            $rows[] = [
                'account_name' => fake()->name(),
                'account_no' => fake()->numerify('##########'),
                'sort_code' => fake()->numerify('##-##-##'),
                'bank_name' => fake()->company(),
                'swift_code' => strtoupper(fake()->bothify('??????##')),
                'is_active' => 1,
                'is_primary' => 1,
                'branch' => fake()->city(),
                'ifsc_code' => strtoupper(fake()->bothify('????0#####')),
                'account_type' => 'business',
                'purpose' => 'general',
                'opening_balance' => fake()->randomFloat(2, 0, 20000),
                'balance_type' => fake()->randomElement(['savings', 'current', 'overdraft']),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if (!empty($rows)) {
            DB::table('sys_bank_accounts')->insert($rows);
        }
    }
}
