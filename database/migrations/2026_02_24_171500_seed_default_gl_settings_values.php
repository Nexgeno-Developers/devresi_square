<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $map = [
            'default_ar_account_id' => '1100',
            'default_ap_account_id' => '2000',
            'default_revenue_account_id' => '4000',
            'default_expense_account_id' => '5000',
        ];

        foreach ($map as $setting => $code) {
            $id = DB::table('gl_accounts')->where('code', $code)->value('id');
            if ($id) {
                DB::table('business_settings')->updateOrInsert(
                    ['type' => $setting],
                    [
                        'value' => $id, // store ID (not code)
                        'lang' => null,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
            }
        }
    }

    public function down(): void
    {
        DB::table('business_settings')->whereIn('type', [
            'default_ar_account_id',
            'default_ap_account_id',
            'default_revenue_account_id',
            'default_expense_account_id',
        ])->update(['value' => null]);
    }
};
