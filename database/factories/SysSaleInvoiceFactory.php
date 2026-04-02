<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\SysSaleInvoice;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;

class SysSaleInvoiceFactory extends Factory
{
    protected $model = SysSaleInvoice::class;

    public function definition(): array
    {
        $invoiceDate = fake()->dateTimeBetween('-3 months', 'now');

        return [
            'user_id' => $this->resolveUserId(),
            'invoice_no' => 'SI-' . fake()->unique()->numerify('######'),
            'invoice_date' => $invoiceDate->format('Y-m-d'),
            'due_date' => fake()->optional(0.85)->dateTimeBetween($invoiceDate, '+30 days')?->format('Y-m-d'),
            'total_amount' => fake()->randomFloat(2, 50, 5000),
            'balance_amount' => fake()->randomFloat(2, 0, 2000),
            'status' => fake()->randomElement(['draft', 'issued', 'paid', 'partial', 'cancelled']),
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
