<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // 2. Create staff_contacts table for multiple emails & phones per staff
        Schema::create('staff_contacts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('staff_id')->index();
            $table->enum('type', ['email', 'phone']);
            $table->string('value');
            $table->string('label')->nullable(); // e.g. "Work", "Personal"
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_contacts');

        Schema::table('form_submissions', function (Blueprint $table) {
            $table->string('staff_email')->nullable();
            $table->string('staff_phone')->nullable();
            $table->string('staff_designation')->nullable();
        });
    }
};
