<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('repair_issues', function (Blueprint $table) {
            if (! Schema::hasColumn('repair_issues', 'complaint_code')) {
                $table->string('complaint_code')->nullable()->index();
            }
            if (! Schema::hasColumn('repair_issues', 'classification_snapshot')) {
                $table->json('classification_snapshot')->nullable();
            }
            if (! Schema::hasColumn('repair_issues', 'sla_due_at')) {
                $table->timestamp('sla_due_at')->nullable()->index();
            }
            if (! Schema::hasColumn('repair_issues', 'make_safe_due_at')) {
                $table->timestamp('make_safe_due_at')->nullable();
            }
            if (! Schema::hasColumn('repair_issues', 'emergency_access')) {
                $table->boolean('emergency_access')->default(false);
            }
            if (! Schema::hasColumn('repair_issues', 'reported_at')) {
                $table->timestamp('reported_at')->nullable();
            }
            if (! Schema::hasColumn('repair_issues', 'dispatched_at')) {
                $table->timestamp('dispatched_at')->nullable();
            }
            if (! Schema::hasColumn('repair_issues', 'dispatched_by')) {
                $table->unsignedBigInteger('dispatched_by')->nullable();
            }
            if (! Schema::hasColumn('repair_issues', 'make_safe_at')) {
                $table->timestamp('make_safe_at')->nullable();
            }
            if (! Schema::hasColumn('repair_issues', 'make_safe_by')) {
                $table->unsignedBigInteger('make_safe_by')->nullable();
            }
            if (! Schema::hasColumn('repair_issues', 'resolved_at')) {
                $table->timestamp('resolved_at')->nullable();
            }
            if (! Schema::hasColumn('repair_issues', 'resolved_by')) {
                $table->unsignedBigInteger('resolved_by')->nullable();
            }
        });

        if (! Schema::hasTable('repair_sla_events')) {
            Schema::create('repair_sla_events', function (Blueprint $table) {
                $table->id();
                $table->foreignId('repair_issue_id')->constrained('repair_issues')->cascadeOnDelete();
                $table->string('event');
                $table->timestamp('occurred_at');
                $table->unsignedBigInteger('actor_id')->nullable();
                $table->text('note')->nullable();
                $table->json('payload')->nullable();
                $table->timestamps();

                $table->index(['repair_issue_id', 'event']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('repair_sla_events');

        Schema::table('repair_issues', function (Blueprint $table) {
            foreach ([
                'resolved_by',
                'resolved_at',
                'make_safe_by',
                'make_safe_at',
                'dispatched_by',
                'dispatched_at',
                'reported_at',
                'emergency_access',
                'make_safe_due_at',
                'sla_due_at',
                'classification_snapshot',
                'complaint_code',
            ] as $column) {
                if (Schema::hasColumn('repair_issues', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
