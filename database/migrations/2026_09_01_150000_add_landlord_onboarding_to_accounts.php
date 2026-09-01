<?php

use App\Models\DocumentType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            if (! Schema::hasColumn('accounts', 'onboarding_completed_at')) {
                $table->timestamp('onboarding_completed_at')->nullable()->after('subscription_activation_email_sent_at');
            }
            if (! Schema::hasColumn('accounts', 'onboarding_step')) {
                $table->unsignedTinyInteger('onboarding_step')->default(1)->after('onboarding_completed_at');
            }
            if (! Schema::hasColumn('accounts', 'onboarding_property_id')) {
                $table->unsignedBigInteger('onboarding_property_id')->nullable()->after('onboarding_step');
            }
        });

        foreach ([
            ['name' => 'Photo ID', 'description' => 'Passport, driving licence, or national identity card.'],
            ['name' => 'Proof of Address', 'description' => 'Utility bill, bank statement, or council tax letter dated within 3 months.'],
        ] as $type) {
            DocumentType::query()->firstOrCreate(
                ['name' => $type['name']],
                ['description' => $type['description']]
            );
        }

        $accountIdsWithProperties = DB::table('properties')
            ->whereNotNull('account_id')
            ->distinct()
            ->pluck('account_id');

        if ($accountIdsWithProperties->isNotEmpty()) {
            DB::table('accounts')
                ->whereNull('onboarding_completed_at')
                ->whereIn('id', $accountIdsWithProperties)
                ->update([
                    'onboarding_completed_at' => now(),
                    'onboarding_step' => 4,
                ]);
        }
    }

    public function down(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            if (Schema::hasColumn('accounts', 'onboarding_property_id')) {
                $table->dropColumn('onboarding_property_id');
            }
            if (Schema::hasColumn('accounts', 'onboarding_step')) {
                $table->dropColumn('onboarding_step');
            }
            if (Schema::hasColumn('accounts', 'onboarding_completed_at')) {
                $table->dropColumn('onboarding_completed_at');
            }
        });
    }
};
