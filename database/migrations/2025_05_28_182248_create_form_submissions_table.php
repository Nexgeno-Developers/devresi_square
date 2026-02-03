<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('form_submissions', function (Blueprint $table) {
            $table->id();
            $table->string('form_type');             // e.g. 'book_demo'
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->date('demo_date')->nullable();
            $table->time('demo_time')->nullable();
            $table->string('hear_about')->nullable();
            $table->boolean('subscribe')->default(false);
            $table->string('attachment')->nullable(); // optional file path
            $table->string('ip')->nullable();
            $table->string('ip_data')->nullable();
            $table->string('ref_url')->nullable();
            $table->boolean('email_sent')->default(false);
            $table->string('w_countrycode', 10)->nullable();
            $table->string('w_phone', 10)->nullable();
            $table->longText('wati_response')->nullable();            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('form_submissions');
    }
};
