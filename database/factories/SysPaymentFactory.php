<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\SysPayment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;

class SysPaymentFactory extends Factory
{
    protected $model = SysPayment::class;

    public function definition(): array
    {
        return [
            'user_id' => fake()->boolean(90) ? $this->resolveUserId() : null,
            'sys_bank_account_id' => $this->resolveBankAccountId(),
            'payment_method_id' => $this->resolvePaymentMethodId(),
            'payment_type' => fake()->randomElement(['income', 'expense', 'general']),
            'reference_type' => fake()->optional(0.7)->randomElement(['sale_invoice', 'purchase_invoice']),
            'reference_id' => fake()->optional(0.7)->numberBetween(1, 10000),
            'payment_date' => fake()->date(),
            'amount' => fake()->randomFloat(2, 10, 5000),
            'notes' => fake()->optional()->sentence(),
        ];
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

    private function resolveUserId(): int
    {
        $userId = User::query()->inRandomOrder()->value('id');
        if ($userId) {
            return (int) $userId;
        }

        $companyId = Company::query()->inRandomOrder()->value('id')
            ?? Company::query()->create(['name' => fake()->company()])->id;

        return $this->createUser($companyId);
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
