<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('user_details', function (Blueprint $table) {
            $table->id(); // Primary Key
            $table->unsignedBigInteger('user_id'); // Foreign key to users table
            $table->string('employment_status')->nullable(); // Employment Status
            $table->string('business_name')->nullable(); // Business Name (if applicable)
            $table->string('registered_address')->nullable(); // Business Name (if applicable)
            $table->boolean('guarantee')->nullable();
            $table->boolean('previously_rented')->nullable(); // Has previously rented?
            $table->boolean('poor_credit')->nullable(); // Poor credit history?
            $table->string('correspondence_address')->nullable();
            $table->string('occupation')->nullable();
            $table->string('vat_number')->nullable();
            $table->boolean('allow_email')->default(false)->change();
            $table->boolean('allow_post') ->default(false)->change();
            $table->boolean('allow_text') ->default(false)->change();
            $table->boolean('allow_call') ->default(false)->change();
            $table->json('emails')->nullable();  // ["foo@a.com","bar@b.com"]
            $table->json('phones')->nullable();  // ["+1 555 1234","+1 555 5678"]

            // Common Applicant fields
            $table->decimal('budget', 10, 2)->nullable() ;
            $table->string('area')->nullable()->after('budget');
            $table->date('tentative_move_in')->nullable()->after('area');
            $table->unsignedTinyInteger('no_of_beds')->nullable()->after('tentative_move_in');
            $table->unsignedTinyInteger('no_of_tenants')->nullable()->after('no_of_beds');

            // Contractor‑specific fields
            $table->json('specialisations')->nullable()->after('no_of_tenants');
            $table->string('cover_areas')->nullable()->after('specialisations');
            $table->boolean('pi_insurance')->default(false)->after('cover_areas');
            $table->string('pi_reference_number')->nullable()->after('pi_insurance');
            $table->string('pi_certificate')->nullable()->after('pi_reference_number');

            // user compliance fields
            $table->unsignedBigInteger('nationality_id')->nullable();
            $table->date('visa_expiry')->nullable();
            $table->string('passport_no')->nullable();
            $table->string('nrl_number')->nullable();
            $table->boolean('right_to_rent_check')->default(false);
            $table->unsignedBigInteger('checked_by_user')->nullable();
            $table->string('checked_by_external')->nullable(); // Assuming it's a name or ID as string

            $table->timestamps();

            // Foreign key constraint
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('checked_by_user')->references('id')->on('users')->onDelete('set null');
            $table->foreign('nationality_id')->references('id')->on('nationalities')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_details');
    }
};
