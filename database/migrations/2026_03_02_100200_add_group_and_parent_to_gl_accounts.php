<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gl_accounts', function (Blueprint $table) {
            $table->unsignedBigInteger('parent_id')->nullable()->after('type');
            $table->string('group', 100)->nullable()->after('parent_id');
            $table->integer('sort_order')->default(0)->after('group');

            $table->foreign('parent_id')->references('id')->on('gl_accounts')->nullOnDelete();
            $table->index('group');
        });
    }

    public function down(): void
    {
        Schema::table('gl_accounts', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->dropIndex(['group']);
            $table->dropColumn(['parent_id', 'group', 'sort_order']);
        });
    }
};
