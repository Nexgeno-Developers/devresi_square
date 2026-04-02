<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $keys = [
            'default_ar_account_id',
            'default_ap_account_id',
            'default_revenue_account_id',
            'default_expense_account_id',
        ];

        foreach ($keys as $key) {
            DB::table('business_settings')->updateOrInsert(
                ['type' => $key],
                ['value' => null, 'lang' => null, 'updated_at' => now(), 'created_at' => now()]
            );
        }
    }

    public function down(): void
    {
        DB::table('business_settings')
            ->whereIn('type', [
                'default_ar_account_id',
                'default_ap_account_id',
                'default_revenue_account_id',
                'default_expense_account_id',
            ])->delete();
    }
};
