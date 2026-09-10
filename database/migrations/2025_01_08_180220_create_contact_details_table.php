<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('employment_status')->nullable();
            $table->string('business_name')->nullable();
            $table->string('registered_address')->nullable();
            $table->boolean('guarantee')->nullable();
            $table->boolean('previously_rented')->nullable();
            $table->boolean('poor_credit')->nullable();
            $table->string('correspondence_address')->nullable();
            $table->string('occupation')->nullable();
            $table->string('vat_number')->nullable();
            $table->boolean('allow_email')->default(false);
            $table->boolean('allow_post')->default(false);
            $table->boolean('allow_text')->default(false);
            $table->boolean('allow_call')->default(false);
            $table->json('emails')->nullable();
            $table->json('phones')->nullable();

            $table->decimal('budget', 10, 2)->nullable();
            $table->string('area')->nullable();
            $table->date('tentative_move_in')->nullable();
            $table->unsignedTinyInteger('no_of_beds')->nullable();
            $table->unsignedTinyInteger('no_of_tenants')->nullable();

            $table->json('specialisations')->nullable();
            $table->string('cover_areas')->nullable();
            $table->boolean('pi_insurance')->default(false);
            $table->string('pi_reference_number')->nullable();
            $table->string('pi_certificate')->nullable();

            $table->unsignedBigInteger('nationality_id')->nullable();
            $table->date('visa_expiry')->nullable();
            $table->string('passport_no')->nullable();
            $table->string('nrl_number')->nullable();
            $table->boolean('right_to_rent_check')->default(false);
            $table->unsignedBigInteger('checked_by_user')->nullable();
            $table->string('checked_by_external')->nullable();

            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('checked_by_user')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_details');
    }
};
