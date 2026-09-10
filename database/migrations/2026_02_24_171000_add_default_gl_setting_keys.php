<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('business_settings')) {
            Schema::create('business_settings', function (Blueprint $table) {
                $table->id();
                $table->string('type');
                $table->longText('value')->nullable();
                $table->string('lang')->nullable();
                $table->timestamps();
                $table->index('type');
            });
        } else {
            Schema::table('business_settings', function (Blueprint $table) {
                if (! Schema::hasColumn('business_settings', 'lang')) {
                    $table->string('lang')->nullable();
                }
            });
        }

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
        if (! Schema::hasTable('business_settings')) {
            return;
        }

        DB::table('business_settings')
            ->whereIn('type', [
                'default_ar_account_id',
                'default_ap_account_id',
                'default_revenue_account_id',
                'default_expense_account_id',
            ])->delete();
    }
};
