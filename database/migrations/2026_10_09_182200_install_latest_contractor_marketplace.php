<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->profiles();
        $this->documents();
        $this->trades();
        $this->reviewEvents();
        $this->accountContractors();
        $this->postcodeLocations();
        $this->coverageGaps();
        $this->contractorRequests();
        $this->jobOffers();
        $this->offerEvents();
        $this->messages();
        $this->variations();
        $this->invoices();
        $this->awardedOffer();
        $this->ensureDocumentPathNullable();
    }

    public function down(): void
    {
        if (Schema::hasTable('repair_issues') && Schema::hasColumn('repair_issues', 'awarded_offer_id')) {
            Schema::table('repair_issues', function (Blueprint $table) {
                $table->dropConstrainedForeignId('awarded_offer_id');
            });
        }

        Schema::dropIfExists('repair_job_invoices');
        Schema::dropIfExists('repair_job_variations');
        Schema::dropIfExists('repair_job_messages');
        Schema::dropIfExists('repair_job_offer_events');
        Schema::dropIfExists('repair_job_offers');
        Schema::dropIfExists('repair_contractor_requests');
        Schema::dropIfExists('contractor_coverage_gaps');
        Schema::dropIfExists('postcode_locations');
        Schema::dropIfExists('account_contractors');
        Schema::dropIfExists('contractor_review_events');
        Schema::dropIfExists('contractor_trades');
        Schema::dropIfExists('contractor_documents');
        Schema::dropIfExists('contractor_profiles');
    }

    private function profiles(): void
    {
        if (! Schema::hasTable('contractor_profiles')) {
            Schema::create('contractor_profiles', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
                $table->string('trading_type', 32)->nullable();
                $table->string('legal_name')->nullable();
                $table->string('trading_name')->nullable();
                $table->string('company_number', 16)->nullable();
                $table->string('utr', 16)->nullable();
                $table->boolean('vat_registered')->default(false);
                $table->string('vat_number', 20)->nullable();
                $table->string('cis_status', 32)->default('unverified');
                $table->boolean('employs_staff')->default(false);
                $table->string('address_line')->nullable();
                $table->string('city')->nullable();
                $table->string('postcode', 16)->nullable();
                $table->unsignedSmallInteger('coverage_radius_miles')->default(15);
                $table->decimal('latitude', 10, 7)->nullable();
                $table->decimal('longitude', 10, 7)->nullable();
                $table->string('phone', 32)->nullable();
                $table->json('trades')->nullable();
                $table->string('public_liability_insurer')->nullable();
                $table->string('public_liability_policy')->nullable();
                $table->decimal('public_liability_cover', 12, 2)->nullable();
                $table->date('public_liability_expires_on')->nullable();
                $table->string('employers_liability_insurer')->nullable();
                $table->string('employers_liability_policy')->nullable();
                $table->decimal('employers_liability_cover', 12, 2)->nullable();
                $table->date('employers_liability_expires_on')->nullable();
                $table->string('gas_safe_number')->nullable();
                $table->date('gas_safe_expires_on')->nullable();
                $table->string('electrical_scheme')->nullable();
                $table->string('electrical_registration')->nullable();
                $table->date('electrical_expires_on')->nullable();
                $table->string('approval_status', 32)->default('draft');
                $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('reviewed_at')->nullable();
                $table->text('review_note')->nullable();
                $table->timestamp('submitted_at')->nullable();
                $table->timestamp('terms_accepted_at')->nullable();
                $table->string('privacy_version', 16)->nullable();
                $table->timestamp('paused_at')->nullable();
                $table->string('pause_reason')->nullable();
                $table->timestamp('removed_at')->nullable();
                $table->unsignedTinyInteger('draft_reminder_stage')->default(0);
                $table->boolean('legacy_contractor')->default(false);
                $table->timestamp('claim_invited_at')->nullable();
                $table->timestamps();
            });

            return;
        }

        $this->addMissing('contractor_profiles', [
            'trading_type' => fn (Blueprint $table) => $table->string('trading_type', 32)->nullable(),
            'legal_name' => fn (Blueprint $table) => $table->string('legal_name')->nullable(),
            'trading_name' => fn (Blueprint $table) => $table->string('trading_name')->nullable(),
            'company_number' => fn (Blueprint $table) => $table->string('company_number', 16)->nullable(),
            'utr' => fn (Blueprint $table) => $table->string('utr', 16)->nullable(),
            'vat_registered' => fn (Blueprint $table) => $table->boolean('vat_registered')->default(false),
            'vat_number' => fn (Blueprint $table) => $table->string('vat_number', 20)->nullable(),
            'cis_status' => fn (Blueprint $table) => $table->string('cis_status', 32)->default('unverified'),
            'employs_staff' => fn (Blueprint $table) => $table->boolean('employs_staff')->default(false),
            'address_line' => fn (Blueprint $table) => $table->string('address_line')->nullable(),
            'city' => fn (Blueprint $table) => $table->string('city')->nullable(),
            'postcode' => fn (Blueprint $table) => $table->string('postcode', 16)->nullable(),
            'coverage_radius_miles' => fn (Blueprint $table) => $table->unsignedSmallInteger('coverage_radius_miles')->default(15),
            'latitude' => fn (Blueprint $table) => $table->decimal('latitude', 10, 7)->nullable(),
            'longitude' => fn (Blueprint $table) => $table->decimal('longitude', 10, 7)->nullable(),
            'phone' => fn (Blueprint $table) => $table->string('phone', 32)->nullable(),
            'trades' => fn (Blueprint $table) => $table->json('trades')->nullable(),
            'public_liability_insurer' => fn (Blueprint $table) => $table->string('public_liability_insurer')->nullable(),
            'public_liability_policy' => fn (Blueprint $table) => $table->string('public_liability_policy')->nullable(),
            'public_liability_cover' => fn (Blueprint $table) => $table->decimal('public_liability_cover', 12, 2)->nullable(),
            'public_liability_expires_on' => fn (Blueprint $table) => $table->date('public_liability_expires_on')->nullable(),
            'employers_liability_insurer' => fn (Blueprint $table) => $table->string('employers_liability_insurer')->nullable(),
            'employers_liability_policy' => fn (Blueprint $table) => $table->string('employers_liability_policy')->nullable(),
            'employers_liability_cover' => fn (Blueprint $table) => $table->decimal('employers_liability_cover', 12, 2)->nullable(),
            'employers_liability_expires_on' => fn (Blueprint $table) => $table->date('employers_liability_expires_on')->nullable(),
            'gas_safe_number' => fn (Blueprint $table) => $table->string('gas_safe_number')->nullable(),
            'gas_safe_expires_on' => fn (Blueprint $table) => $table->date('gas_safe_expires_on')->nullable(),
            'electrical_scheme' => fn (Blueprint $table) => $table->string('electrical_scheme')->nullable(),
            'electrical_registration' => fn (Blueprint $table) => $table->string('electrical_registration')->nullable(),
            'electrical_expires_on' => fn (Blueprint $table) => $table->date('electrical_expires_on')->nullable(),
            'approval_status' => fn (Blueprint $table) => $table->string('approval_status', 32)->default('draft'),
            'reviewed_by' => fn (Blueprint $table) => $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete(),
            'reviewed_at' => fn (Blueprint $table) => $table->timestamp('reviewed_at')->nullable(),
            'review_note' => fn (Blueprint $table) => $table->text('review_note')->nullable(),
            'submitted_at' => fn (Blueprint $table) => $table->timestamp('submitted_at')->nullable(),
            'terms_accepted_at' => fn (Blueprint $table) => $table->timestamp('terms_accepted_at')->nullable(),
            'privacy_version' => fn (Blueprint $table) => $table->string('privacy_version', 16)->nullable(),
            'paused_at' => fn (Blueprint $table) => $table->timestamp('paused_at')->nullable(),
            'pause_reason' => fn (Blueprint $table) => $table->string('pause_reason')->nullable(),
            'removed_at' => fn (Blueprint $table) => $table->timestamp('removed_at')->nullable(),
            'draft_reminder_stage' => fn (Blueprint $table) => $table->unsignedTinyInteger('draft_reminder_stage')->default(0),
            'legacy_contractor' => fn (Blueprint $table) => $table->boolean('legacy_contractor')->default(false),
            'claim_invited_at' => fn (Blueprint $table) => $table->timestamp('claim_invited_at')->nullable(),
        ]);
    }

    private function documents(): void
    {
        if (! Schema::hasTable('contractor_documents')) {
            Schema::create('contractor_documents', function (Blueprint $table) {
                $table->id();
                $table->foreignId('contractor_profile_id')->constrained('contractor_profiles')->cascadeOnDelete();
                $table->string('type', 64);
                $table->string('status', 24)->default('pending');
                $table->string('reference')->nullable();
                $table->string('issuer')->nullable();
                $table->decimal('cover_amount', 12, 2)->nullable();
                $table->string('disk', 32)->default('local');
                $table->string('path')->nullable();
                $table->string('original_name')->nullable();
                $table->string('mime', 100)->nullable();
                $table->boolean('encrypted')->default(false);
                $table->timestamp('expires_at')->nullable();
                $table->unsignedSmallInteger('reminder_stage')->nullable();
                $table->timestamp('expired_notified_at')->nullable();
                $table->timestamp('submitted_at')->nullable();
                $table->timestamp('verified_at')->nullable();
                $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
                $table->text('rejected_reason')->nullable();
                $table->timestamps();
                $table->unique(['contractor_profile_id', 'type']);
            });

            return;
        }

        $this->addMissing('contractor_documents', [
            'status' => fn (Blueprint $table) => $table->string('status', 24)->default('pending'),
            'reference' => fn (Blueprint $table) => $table->string('reference')->nullable(),
            'issuer' => fn (Blueprint $table) => $table->string('issuer')->nullable(),
            'cover_amount' => fn (Blueprint $table) => $table->decimal('cover_amount', 12, 2)->nullable(),
            'disk' => fn (Blueprint $table) => $table->string('disk', 32)->default('local'),
            'path' => fn (Blueprint $table) => $table->string('path')->nullable(),
            'original_name' => fn (Blueprint $table) => $table->string('original_name')->nullable(),
            'mime' => fn (Blueprint $table) => $table->string('mime', 100)->nullable(),
            'encrypted' => fn (Blueprint $table) => $table->boolean('encrypted')->default(false),
            'expires_at' => fn (Blueprint $table) => $table->timestamp('expires_at')->nullable(),
            'reminder_stage' => fn (Blueprint $table) => $table->unsignedSmallInteger('reminder_stage')->nullable(),
            'expired_notified_at' => fn (Blueprint $table) => $table->timestamp('expired_notified_at')->nullable(),
            'submitted_at' => fn (Blueprint $table) => $table->timestamp('submitted_at')->nullable(),
            'verified_at' => fn (Blueprint $table) => $table->timestamp('verified_at')->nullable(),
            'verified_by' => fn (Blueprint $table) => $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete(),
            'rejected_reason' => fn (Blueprint $table) => $table->text('rejected_reason')->nullable(),
        ]);
    }

    private function trades(): void
    {
        if (Schema::hasTable('contractor_trades')) {
            return;
        }

        Schema::create('contractor_trades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contractor_profile_id')->constrained('contractor_profiles')->cascadeOnDelete();
            $table->string('trade', 32);
            $table->string('status', 24)->default('pending');
            $table->text('reason')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            $table->unique(['contractor_profile_id', 'trade']);
        });
    }

    private function reviewEvents(): void
    {
        if (Schema::hasTable('contractor_review_events')) {
            return;
        }

        Schema::create('contractor_review_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contractor_profile_id')->constrained('contractor_profiles')->cascadeOnDelete();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 48);
            $table->string('subject', 64)->nullable();
            $table->text('note')->nullable();
            $table->json('snapshot')->nullable();
            $table->timestamps();
            $table->index(['contractor_profile_id', 'created_at']);
        });
    }

    private function accountContractors(): void
    {
        if (Schema::hasTable('account_contractors')) {
            return;
        }

        Schema::create('account_contractors', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('account_id')->index();
            $table->foreignId('contractor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('email')->nullable();
            $table->string('name')->nullable();
            $table->string('token', 64)->nullable()->unique();
            $table->boolean('invited')->default(false);
            $table->boolean('favourite')->default(false);
            $table->foreignId('invited_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('invited_at')->nullable();
            $table->timestamp('claimed_at')->nullable();
            $table->timestamps();
            $table->unique(['account_id', 'contractor_user_id']);
            $table->index(['account_id', 'email']);
        });
    }

    private function postcodeLocations(): void
    {
        if (Schema::hasTable('postcode_locations')) {
            return;
        }

        Schema::create('postcode_locations', function (Blueprint $table) {
            $table->id();
            $table->string('postcode', 16)->unique();
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->timestamps();
        });
    }

    private function coverageGaps(): void
    {
        if (Schema::hasTable('contractor_coverage_gaps')) {
            return;
        }

        Schema::create('contractor_coverage_gaps', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('account_id')->nullable();
            $table->foreignId('repair_issue_id')->nullable()->constrained('repair_issues')->nullOnDelete();
            $table->string('postcode_district', 8)->nullable();
            $table->string('trade', 32)->nullable();
            $table->timestamps();
            $table->index(['postcode_district', 'trade']);
        });
    }

    private function contractorRequests(): void
    {
        if (Schema::hasTable('repair_contractor_requests')) {
            return;
        }

        Schema::create('repair_contractor_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('account_id')->index();
            $table->foreignId('repair_issue_id')->unique()->constrained('repair_issues')->cascadeOnDelete();
            $table->string('urgency', 16)->default('routine');
            $table->string('trade', 32)->nullable();
            $table->timestamp('respond_by')->nullable();
            $table->decimal('budget_ceiling', 12, 2)->nullable();
            $table->boolean('show_budget')->default(false);
            $table->text('access_notes')->nullable();
            $table->boolean('availability_to_arrange')->default(false);
            $table->string('status', 24)->default('sent');
            $table->foreignId('sent_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('first_sent_at')->nullable();
            $table->timestamp('last_sent_at')->nullable();
            $table->timestamp('first_offer_at')->nullable();
            $table->timestamp('awarded_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancel_reason')->nullable();
            $table->timestamp('details_changed_at')->nullable();
            $table->timestamp('first_offer_notified_at')->nullable();
            $table->timestamp('last_digest_at')->nullable();
            $table->timestamps();
        });
    }

    private function jobOffers(): void
    {
        if (! Schema::hasTable('repair_job_offers')) {
            Schema::create('repair_job_offers', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('account_id');
                $table->foreignId('repair_issue_id')->constrained('repair_issues')->cascadeOnDelete();
                $table->foreignId('repair_contractor_request_id')->nullable()->constrained('repair_contractor_requests')->nullOnDelete();
                $table->foreignId('contractor_user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('invited_by')->nullable()->constrained('users')->nullOnDelete();
                $table->string('status', 32)->default('invited');
                $table->string('job_status', 24)->nullable();
                $table->timestamp('scheduled_for')->nullable();
                $table->timestamp('started_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->text('completion_note')->nullable();
                $table->json('completion_photos')->nullable();
                $table->timestamp('confirmed_at')->nullable();
                $table->timestamp('tenant_confirmed_at')->nullable();
                $table->timestamp('reschedule_requested_at')->nullable();
                $table->string('reschedule_requested_by', 16)->nullable();
                $table->json('reschedule_slots')->nullable();
                $table->string('reschedule_note', 500)->nullable();
                $table->timestamp('disputed_at')->nullable();
                $table->text('dispute_reason')->nullable();
                $table->text('cancel_reason')->nullable();
                $table->unsignedTinyInteger('rating')->nullable();
                $table->string('rating_comment', 1000)->nullable();
                $table->timestamp('rated_at')->nullable();
                $table->string('pricing_type', 16)->nullable();
                $table->string('vat_treatment', 32)->nullable();
                $table->decimal('vat_rate', 5, 2)->nullable();
                $table->decimal('net_amount', 12, 2)->nullable();
                $table->decimal('vat_amount', 12, 2)->nullable();
                $table->decimal('gross_amount', 12, 2)->nullable();
                $table->decimal('hourly_rate', 12, 2)->nullable();
                $table->decimal('minimum_hours', 8, 2)->nullable();
                $table->decimal('call_out_fee', 12, 2)->nullable();
                $table->decimal('estimated_hours', 8, 2)->nullable();
                $table->decimal('price_cap', 12, 2)->nullable();
                $table->decimal('inspection_fee', 12, 2)->nullable();
                $table->string('materials', 16)->nullable();
                $table->decimal('materials_estimate', 12, 2)->nullable();
                $table->boolean('price_is_estimate')->default(false);
                $table->text('offer_note')->nullable();
                $table->text('decline_reason')->nullable();
                $table->string('decline_code', 24)->nullable();
                $table->text('withdraw_reason')->nullable();
                $table->unsignedInteger('revision')->default(0);
                $table->timestamp('availability_snapshot')->nullable();
                $table->json('visit_slots')->nullable();
                $table->timestamp('invited_at')->nullable();
                $table->timestamp('viewed_at')->nullable();
                $table->timestamp('invite_expires_at')->nullable();
                $table->timestamp('offer_sent_at')->nullable();
                $table->timestamp('offer_expires_at')->nullable();
                $table->timestamp('decided_at')->nullable();
                $table->timestamp('compliance_notified_at')->nullable();
                $table->timestamp('details_changed_at')->nullable();
                $table->timestamp('inspection_accepted_at')->nullable();
                $table->timestamp('reconfirm_requested_at')->nullable();
                $table->timestamp('reminded_at')->nullable();
                $table->timestamp('digested_at')->nullable();
                $table->timestamps();
                $table->unique(['repair_issue_id', 'contractor_user_id']);
                $table->index(['account_id', 'status']);
            });

            return;
        }

        $this->addMissing('repair_job_offers', [
            'repair_contractor_request_id' => fn (Blueprint $table) => $table->foreignId('repair_contractor_request_id')->nullable()->constrained('repair_contractor_requests')->nullOnDelete(),
            'job_status' => fn (Blueprint $table) => $table->string('job_status', 24)->nullable(),
            'scheduled_for' => fn (Blueprint $table) => $table->timestamp('scheduled_for')->nullable(),
            'started_at' => fn (Blueprint $table) => $table->timestamp('started_at')->nullable(),
            'completed_at' => fn (Blueprint $table) => $table->timestamp('completed_at')->nullable(),
            'completion_note' => fn (Blueprint $table) => $table->text('completion_note')->nullable(),
            'completion_photos' => fn (Blueprint $table) => $table->json('completion_photos')->nullable(),
            'confirmed_at' => fn (Blueprint $table) => $table->timestamp('confirmed_at')->nullable(),
            'tenant_confirmed_at' => fn (Blueprint $table) => $table->timestamp('tenant_confirmed_at')->nullable(),
            'reschedule_requested_at' => fn (Blueprint $table) => $table->timestamp('reschedule_requested_at')->nullable(),
            'reschedule_requested_by' => fn (Blueprint $table) => $table->string('reschedule_requested_by', 16)->nullable(),
            'reschedule_slots' => fn (Blueprint $table) => $table->json('reschedule_slots')->nullable(),
            'reschedule_note' => fn (Blueprint $table) => $table->string('reschedule_note', 500)->nullable(),
            'disputed_at' => fn (Blueprint $table) => $table->timestamp('disputed_at')->nullable(),
            'dispute_reason' => fn (Blueprint $table) => $table->text('dispute_reason')->nullable(),
            'cancel_reason' => fn (Blueprint $table) => $table->text('cancel_reason')->nullable(),
            'rating' => fn (Blueprint $table) => $table->unsignedTinyInteger('rating')->nullable(),
            'rating_comment' => fn (Blueprint $table) => $table->string('rating_comment', 1000)->nullable(),
            'rated_at' => fn (Blueprint $table) => $table->timestamp('rated_at')->nullable(),
            'pricing_type' => fn (Blueprint $table) => $table->string('pricing_type', 16)->nullable(),
            'vat_treatment' => fn (Blueprint $table) => $table->string('vat_treatment', 32)->nullable(),
            'vat_rate' => fn (Blueprint $table) => $table->decimal('vat_rate', 5, 2)->nullable(),
            'net_amount' => fn (Blueprint $table) => $table->decimal('net_amount', 12, 2)->nullable(),
            'vat_amount' => fn (Blueprint $table) => $table->decimal('vat_amount', 12, 2)->nullable(),
            'gross_amount' => fn (Blueprint $table) => $table->decimal('gross_amount', 12, 2)->nullable(),
            'hourly_rate' => fn (Blueprint $table) => $table->decimal('hourly_rate', 12, 2)->nullable(),
            'minimum_hours' => fn (Blueprint $table) => $table->decimal('minimum_hours', 8, 2)->nullable(),
            'call_out_fee' => fn (Blueprint $table) => $table->decimal('call_out_fee', 12, 2)->nullable(),
            'estimated_hours' => fn (Blueprint $table) => $table->decimal('estimated_hours', 8, 2)->nullable(),
            'price_cap' => fn (Blueprint $table) => $table->decimal('price_cap', 12, 2)->nullable(),
            'inspection_fee' => fn (Blueprint $table) => $table->decimal('inspection_fee', 12, 2)->nullable(),
            'materials' => fn (Blueprint $table) => $table->string('materials', 16)->nullable(),
            'materials_estimate' => fn (Blueprint $table) => $table->decimal('materials_estimate', 12, 2)->nullable(),
            'price_is_estimate' => fn (Blueprint $table) => $table->boolean('price_is_estimate')->default(false),
            'offer_note' => fn (Blueprint $table) => $table->text('offer_note')->nullable(),
            'decline_reason' => fn (Blueprint $table) => $table->text('decline_reason')->nullable(),
            'decline_code' => fn (Blueprint $table) => $table->string('decline_code', 24)->nullable(),
            'withdraw_reason' => fn (Blueprint $table) => $table->text('withdraw_reason')->nullable(),
            'revision' => fn (Blueprint $table) => $table->unsignedInteger('revision')->default(0),
            'availability_snapshot' => fn (Blueprint $table) => $table->timestamp('availability_snapshot')->nullable(),
            'visit_slots' => fn (Blueprint $table) => $table->json('visit_slots')->nullable(),
            'invited_at' => fn (Blueprint $table) => $table->timestamp('invited_at')->nullable(),
            'viewed_at' => fn (Blueprint $table) => $table->timestamp('viewed_at')->nullable(),
            'invite_expires_at' => fn (Blueprint $table) => $table->timestamp('invite_expires_at')->nullable(),
            'offer_sent_at' => fn (Blueprint $table) => $table->timestamp('offer_sent_at')->nullable(),
            'offer_expires_at' => fn (Blueprint $table) => $table->timestamp('offer_expires_at')->nullable(),
            'decided_at' => fn (Blueprint $table) => $table->timestamp('decided_at')->nullable(),
            'compliance_notified_at' => fn (Blueprint $table) => $table->timestamp('compliance_notified_at')->nullable(),
            'details_changed_at' => fn (Blueprint $table) => $table->timestamp('details_changed_at')->nullable(),
            'inspection_accepted_at' => fn (Blueprint $table) => $table->timestamp('inspection_accepted_at')->nullable(),
            'reconfirm_requested_at' => fn (Blueprint $table) => $table->timestamp('reconfirm_requested_at')->nullable(),
            'reminded_at' => fn (Blueprint $table) => $table->timestamp('reminded_at')->nullable(),
            'digested_at' => fn (Blueprint $table) => $table->timestamp('digested_at')->nullable(),
        ]);
    }

    private function offerEvents(): void
    {
        if (Schema::hasTable('repair_job_offer_events')) {
            return;
        }

        Schema::create('repair_job_offer_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('repair_job_offer_id')->constrained('repair_job_offers')->cascadeOnDelete();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('from_status', 32)->nullable();
            $table->string('to_status', 32);
            $table->text('note')->nullable();
            $table->json('snapshot')->nullable();
            $table->timestamps();
        });
    }

    private function messages(): void
    {
        if (Schema::hasTable('repair_job_messages')) {
            return;
        }

        Schema::create('repair_job_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('repair_job_offer_id')->constrained('repair_job_offers')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('side', 16);
            $table->text('body');
            $table->boolean('masked')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            $table->index(['repair_job_offer_id', 'created_at']);
        });
    }

    private function variations(): void
    {
        if (Schema::hasTable('repair_job_variations')) {
            return;
        }

        Schema::create('repair_job_variations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('repair_job_offer_id')->constrained('repair_job_offers')->cascadeOnDelete();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('reason');
            $table->decimal('net_amount', 10, 2);
            $table->decimal('vat_rate', 5, 2)->default(0);
            $table->decimal('vat_amount', 10, 2)->default(0);
            $table->decimal('gross_amount', 10, 2);
            $table->string('status', 16)->default('pending');
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->string('decision_note', 500)->nullable();
            $table->timestamps();
        });
    }

    private function invoices(): void
    {
        if (Schema::hasTable('repair_job_invoices')) {
            return;
        }

        Schema::create('repair_job_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('repair_job_offer_id')->unique()->constrained('repair_job_offers')->cascadeOnDelete();
            $table->unsignedBigInteger('account_id')->index();
            $table->foreignId('contractor_user_id')->constrained('users');
            $table->string('number', 32)->unique();
            $table->date('issued_on');
            $table->date('due_on');
            $table->json('lines');
            $table->decimal('net_amount', 10, 2);
            $table->decimal('vat_amount', 10, 2)->default(0);
            $table->decimal('gross_amount', 10, 2);
            $table->string('vat_number', 20)->nullable();
            $table->string('cis_status', 24)->nullable();
            $table->text('notes')->nullable();
            $table->string('status', 16)->default('issued');
            $table->timestamp('paid_at')->nullable();
            $table->foreignId('paid_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('void_reason', 500)->nullable();
            $table->timestamps();
        });
    }

    private function awardedOffer(): void
    {
        if (! Schema::hasTable('repair_issues') || Schema::hasColumn('repair_issues', 'awarded_offer_id')) {
            return;
        }

        Schema::table('repair_issues', function (Blueprint $table) {
            $table->foreignId('awarded_offer_id')->nullable()->constrained('repair_job_offers')->nullOnDelete();
        });
    }

    private function ensureDocumentPathNullable(): void
    {
        if (! Schema::hasTable('contractor_documents') || ! Schema::hasColumn('contractor_documents', 'path')) {
            return;
        }

        $column = collect(Schema::getColumns('contractor_documents'))->firstWhere('name', 'path');
        if (! $column || ! empty($column['nullable'])) {
            return;
        }

        DB::statement('ALTER TABLE `contractor_documents` MODIFY `path` VARCHAR(255) NULL');
    }

    /**
     * @param  array<string, callable(Blueprint): void>  $columns
     */
    private function addMissing(string $table, array $columns): void
    {
        $missing = [];
        foreach ($columns as $name => $define) {
            if (! Schema::hasColumn($table, $name)) {
                $missing[] = $define;
            }
        }

        if ($missing === []) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($missing) {
            foreach ($missing as $define) {
                $define($blueprint);
            }
        });
    }
};
