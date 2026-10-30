<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A contractor is listed for every landlord only after they accept the
     * marketplace terms. The commission percent is the rate they agreed.
     */
    public function up(): void
    {
        Schema::table('contractor_profiles', function (Blueprint $table) {
            if (! Schema::hasColumn('contractor_profiles', 'marketplace_accepted_at')) {
                $table->timestamp('marketplace_accepted_at')->nullable();
            }
            if (! Schema::hasColumn('contractor_profiles', 'marketplace_terms_version')) {
                $table->string('marketplace_terms_version', 16)->nullable();
            }
            if (! Schema::hasColumn('contractor_profiles', 'marketplace_commission_percent')) {
                $table->decimal('marketplace_commission_percent', 5, 2)->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('contractor_profiles', function (Blueprint $table) {
            $columns = array_values(array_filter(
                ['marketplace_commission_percent', 'marketplace_terms_version', 'marketplace_accepted_at'],
                fn (string $column) => Schema::hasColumn('contractor_profiles', $column)
            ));
            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
