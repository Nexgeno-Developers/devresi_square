<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('contractor_profiles')) {
            return;
        }

        Schema::table('contractor_profiles', function (Blueprint $table) {
            if (! Schema::hasColumn('contractor_profiles', 'claim_token_hash')) {
                $table->string('claim_token_hash', 64)->nullable()->after('claim_invited_at');
            }
            if (! Schema::hasColumn('contractor_profiles', 'claim_expires_at')) {
                $table->timestamp('claim_expires_at')->nullable()->after('claim_token_hash');
            }
        });

        DB::table('contractor_profiles')->where('company_number', '')->update(['company_number' => null]);
        DB::table('contractor_profiles')
            ->where(function ($query) {
                $query->where('approval_status', 'removed')->orWhereNotNull('removed_at');
            })
            ->update(['company_number' => null]);

        $duplicates = DB::table('contractor_profiles')
            ->select('company_number')
            ->whereNotNull('company_number')
            ->groupBy('company_number')
            ->havingRaw('count(*) > 1')
            ->pluck('company_number');
        foreach ($duplicates as $number) {
            $ids = DB::table('contractor_profiles')->where('company_number', $number)->orderBy('id')->pluck('id');
            $ids->shift();
            if ($ids->isNotEmpty()) {
                DB::table('contractor_profiles')->whereIn('id', $ids)->update(['company_number' => null]);
            }
        }

        Schema::table('contractor_profiles', function (Blueprint $table) {
            $index = collect(Schema::getIndexes('contractor_profiles'))->pluck('name');
            if (! $index->contains('contractor_profiles_company_number_unique')) {
                $table->unique('company_number', 'contractor_profiles_company_number_unique');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('contractor_profiles')) {
            return;
        }

        Schema::table('contractor_profiles', function (Blueprint $table) {
            $index = collect(Schema::getIndexes('contractor_profiles'))->pluck('name');
            if ($index->contains('contractor_profiles_company_number_unique')) {
                $table->dropUnique('contractor_profiles_company_number_unique');
            }
            if (Schema::hasColumn('contractor_profiles', 'claim_expires_at')) {
                $table->dropColumn('claim_expires_at');
            }
            if (Schema::hasColumn('contractor_profiles', 'claim_token_hash')) {
                $table->dropColumn('claim_token_hash');
            }
        });
    }
};
