<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('compliance_job_offers', function (Blueprint $table) {
            if (! Schema::hasColumn('compliance_job_offers', 'reschedule_requested_at')) {
                $table->timestamp('reschedule_requested_at')->nullable();
            }
            if (! Schema::hasColumn('compliance_job_offers', 'reschedule_requested_by')) {
                $table->string('reschedule_requested_by', 16)->nullable();
            }
            if (! Schema::hasColumn('compliance_job_offers', 'reschedule_slots')) {
                $table->json('reschedule_slots')->nullable();
            }
            if (! Schema::hasColumn('compliance_job_offers', 'reschedule_note')) {
                $table->string('reschedule_note', 500)->nullable();
            }
            if (! Schema::hasColumn('compliance_job_offers', 'disputed_at')) {
                $table->timestamp('disputed_at')->nullable();
            }
            if (! Schema::hasColumn('compliance_job_offers', 'dispute_reason')) {
                $table->text('dispute_reason')->nullable();
            }
            if (! Schema::hasColumn('compliance_job_offers', 'cancel_reason')) {
                $table->string('cancel_reason', 500)->nullable();
            }
            if (! Schema::hasColumn('compliance_job_offers', 'rating')) {
                $table->unsignedTinyInteger('rating')->nullable();
            }
            if (! Schema::hasColumn('compliance_job_offers', 'rating_comment')) {
                $table->string('rating_comment', 1000)->nullable();
            }
            if (! Schema::hasColumn('compliance_job_offers', 'rated_at')) {
                $table->timestamp('rated_at')->nullable();
            }
        });

        if (! Schema::hasTable('compliance_job_messages')) {
            Schema::create('compliance_job_messages', function (Blueprint $table) {
                $table->id();
                $table->foreignId('compliance_job_offer_id')->constrained('compliance_job_offers')->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('side', 16);
                $table->text('body');
                $table->boolean('masked')->default(false);
                $table->timestamp('read_at')->nullable();
                $table->timestamps();
                $table->index(['compliance_job_offer_id', 'created_at']);
            });
        }

        if (! Schema::hasTable('compliance_job_variations')) {
            Schema::create('compliance_job_variations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('compliance_job_offer_id')->constrained('compliance_job_offers')->cascadeOnDelete();
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
    }

    public function down(): void
    {
        Schema::dropIfExists('compliance_job_variations');
        Schema::dropIfExists('compliance_job_messages');

        Schema::table('compliance_job_offers', function (Blueprint $table) {
            foreach ([
                'rated_at', 'rating_comment', 'rating', 'cancel_reason', 'dispute_reason', 'disputed_at',
                'reschedule_note', 'reschedule_slots', 'reschedule_requested_by', 'reschedule_requested_at',
            ] as $column) {
                if (Schema::hasColumn('compliance_job_offers', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
