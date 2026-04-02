<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\SysPurchaseInvoice;
use App\Models\SysReceipt;
use App\Models\SysSaleInvoice;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class SysReceiptFactory extends Factory
{
    protected $model = SysReceipt::class;

    public function definition(): array
    {
        $fallbackCompanyId = $this->resolveCompanyId();
        $receiptable = $this->resolveReceiptable();
        $receiptUserId = $this->resolveReceiptUserId($receiptable, $fallbackCompanyId);

        $receiptUser = User::query()->find($receiptUserId);
        $companyId = (int) ($receiptUser?->company_id ?: $fallbackCompanyId);

        return [
            'company_id' => $companyId,
            'user_id' => $receiptUserId,
            'receiptable_type' => $receiptable::class,
            'receiptable_id' => (int) $receiptable->id,
            'receipt_no' => 'RC-' . fake()->unique()->numerify('######'),
            'receipt_date' => fake()->date(),
            'amount' => fake()->randomFloat(2, 10, 5000),
            'sys_bank_account_id' => $this->resolveBankAccountId(),
            'payment_method_id' => $this->resolvePaymentMethodId(),
            'reference_no' => fake()->optional(0.7)->bothify('REF-####'),
            'notes' => fake()->optional()->sentence(),
        ];
    }

    private function resolveReceiptable(): Model
    {
        $type = fake()->randomElement(['sale', 'purchase']);

        if ($type === 'sale') {
            return SysSaleInvoice::query()->inRandomOrder()->first()
                ?? SysSaleInvoice::factory()->create();
        }

        return SysPurchaseInvoice::query()->inRandomOrder()->first()
            ?? SysPurchaseInvoice::factory()->create();
    }

    private function resolveReceiptUserId(Model $receiptable, int $companyId): int
    {
        $candidateUserId = (int) ($receiptable->user_id ?? 0);
        if ($candidateUserId > 0) {
            return $candidateUserId;
        }

        $userId = User::query()->inRandomOrder()->value('id');
        if ($userId) {
            return (int) $userId;
        }

        return $this->createUser($companyId);
    }

    private function resolveCompanyId(): int
    {
        return (int) (
            Company::query()->inRandomOrder()->value('id')
            ?? Company::query()->create(['name' => fake()->company()])->id
        );
    }

    private function resolveBankAccountId(): int
    {
        $id = DB::table('sys_bank_accounts')->inRandomOrder()->value('id');
        if ($id) {
            return (int) $id;
        }

        return (int) DB::table('sys_bank_accounts')->insertGetId([
            'account_name' => fake()->name(),
            'account_no' => fake()->numerify('##########'),
            'bank_name' => fake()->company(),
            'is_active' => 1,
            'is_primary' => 1,
            'balance_type' => 'savings',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function resolvePaymentMethodId(): int
    {
        $id = DB::table('payment_methods')->inRandomOrder()->value('id');
        if ($id) {
            return (int) $id;
        }

        return (int) DB::table('payment_methods')->insertGetId([
            'name' => fake()->randomElement(['Cash', 'Bank Transfer', 'Card']),
            'code' => strtoupper(fake()->unique()->lexify('PM???')),
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createUser(int $companyId): int
    {
        return (int) DB::table('users')->insertGetId([
            'company_id' => $companyId,
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'password' => bcrypt('password'),
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
