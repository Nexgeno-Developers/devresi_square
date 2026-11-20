<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// The book-a-demo form sends user_role (required in the UI), but the column never existed,
// so every demo request with a role failed with a 500.
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('form_submissions') && ! Schema::hasColumn('form_submissions', 'user_role')) {
            Schema::table('form_submissions', function (Blueprint $table) {
                $table->string('user_role', 50)->nullable()->after('phone');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('form_submissions') && Schema::hasColumn('form_submissions', 'user_role')) {
            Schema::table('form_submissions', function (Blueprint $table) {
                $table->dropColumn('user_role');
            });
        }
    }
};
