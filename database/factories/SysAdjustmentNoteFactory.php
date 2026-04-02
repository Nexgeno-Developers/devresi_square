<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\SysAdjustmentNote;
use App\Models\SysPurchaseInvoice;
use App\Models\SysSaleInvoice;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;

class SysAdjustmentNoteFactory extends Factory
{
    protected $model = SysAdjustmentNote::class;

    public function definition(): array
    {
        $referenceType = fake()->randomElement(['sale_invoice', 'purchase_invoice']);

        return [
            'note_type' => fake()->randomElement(['credit', 'debit']),
            'adjustment_reason' => fake()->randomElement(['return', 'refund', 'writeoff']),
            'reference_type' => $referenceType,
            'reference_id' => $referenceType === 'sale_invoice'
                ? SysSaleInvoice::factory()
                : SysPurchaseInvoice::factory(),
            'user_id' => $this->resolveUserId(),
            'note_no' => 'AN-' . fake()->unique()->numerify('######'),
            'note_date' => fake()->date(),
            'total_amount' => fake()->randomFloat(2, 10, 3000),
            'balance_amount' => fake()->randomFloat(2, 0, 1500),
            'is_refunded' => fake()->boolean(35),
            'notes' => fake()->optional()->sentence(),
        ];
    }

    private function resolveUserId(): int
    {
        $userId = User::query()->inRandomOrder()->value('id');
        if ($userId) {
            return (int) $userId;
        }

        $companyId = Company::query()->inRandomOrder()->value('id')
            ?? Company::query()->create(['name' => fake()->company()])->id;

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
