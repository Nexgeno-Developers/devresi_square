<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('accounts')) {
            Schema::create('accounts', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('owner_user_id');
                $table->enum('account_type', ['landlord', 'estate_agent_freelance', 'estate_agent_company']);
                $table->string('account_name')->nullable();
                $table->string('billing_email')->nullable();
                $table->string('billing_phone')->nullable();
                $table->string('currency')->default('GBP');
                $table->enum('status', ['trialing', 'active', 'past_due', 'suspended', 'cancelled'])->default('trialing');
                $table->timestamp('trial_started_at')->nullable();
                $table->timestamp('trial_ends_at')->nullable();
                $table->string('stripe_customer_id')->nullable();
                $table->timestamps();

                $table->index('owner_user_id');
                $table->index('account_type');
                $table->index('status');
            });
        }

        if (! Schema::hasTable('account_users')) {
            Schema::create('account_users', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('account_id');
                $table->unsignedBigInteger('user_id');
                $table->enum('member_type', [
                    'owner',
                    'admin',
                    'staff',
                    'contact',
                    'landlord',
                    'owner_contact',
                    'tenant',
                    'contractor',
                    'property_manager',
                ]);
                $table->enum('access_level', ['full', 'edit', 'view', 'no_login'])->default('view');
                $table->boolean('can_login')->default(false);
                $table->unsignedBigInteger('branch_id')->nullable();
                $table->unsignedBigInteger('designation_id')->nullable();
                $table->enum('status', ['active', 'invited', 'disabled'])->default('active');
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();

                $table->unique(['account_id', 'user_id']);
                $table->index(['account_id', 'member_type']);
                $table->index('user_id');
            });
        }

        if (! Schema::hasTable('plans')) {
            Schema::create('plans', function (Blueprint $table) {
                $table->id();
                $table->string('code')->unique();
                $table->string('name');
                $table->enum('target_account_type', ['landlord', 'estate_agent_freelance', 'estate_agent_company']);
                $table->text('description')->nullable();
                $table->integer('monthly_price_minor')->default(0);
                $table->integer('annual_price_minor')->default(0);
                $table->string('currency')->default('GBP');
                $table->string('stripe_monthly_price_id')->nullable();
                $table->string('stripe_annual_price_id')->nullable();
                $table->integer('trial_days')->default(7);
                $table->integer('property_limit')->default(0);
                $table->integer('branch_limit')->default(0);
                $table->integer('staff_limit')->default(0);
                $table->integer('property_manager_limit')->default(0);
                $table->boolean('allow_company_profile')->default(false);
                $table->boolean('allow_invoice_branding')->default(false);
                $table->boolean('allow_roles_permissions')->default(false);
                $table->boolean('allow_contact_login')->default(true);
                $table->boolean('is_active')->default(true);
                $table->integer('sort_order')->default(0);
                $table->timestamps();

                $table->index('target_account_type');
                $table->index('is_active');
            });
        }

        if (! Schema::hasTable('addons')) {
            Schema::create('addons', function (Blueprint $table) {
                $table->id();
                $table->string('code')->unique();
                $table->string('name');
                $table->enum('addon_type', ['property', 'branch', 'staff', 'property_manager']);
                $table->integer('monthly_price_minor')->default(0);
                $table->integer('annual_price_minor')->default(0);
                $table->string('currency')->default('GBP');
                $table->string('stripe_monthly_price_id')->nullable();
                $table->string('stripe_annual_price_id')->nullable();
                $table->integer('grant_quantity')->default(1);
                $table->boolean('is_stackable')->default(true);
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->index('addon_type');
                $table->index('is_active');
            });
        }

        if (! Schema::hasTable('account_subscriptions')) {
            Schema::create('account_subscriptions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('account_id');
                $table->unsignedBigInteger('plan_id');
                $table->enum('billing_cycle', ['monthly', 'annual']);
                $table->enum('status', ['trialing', 'active', 'past_due', 'cancelled', 'expired'])->default('trialing');
                $table->timestamp('trial_started_at')->nullable();
                $table->timestamp('trial_ends_at')->nullable();
                $table->timestamp('current_period_start')->nullable();
                $table->timestamp('current_period_end')->nullable();
                $table->string('stripe_subscription_id')->nullable();
                $table->string('stripe_price_id')->nullable();
                $table->boolean('cancel_at_period_end')->default(false);
                $table->timestamp('cancelled_at')->nullable();
                $table->timestamps();

                $table->index(['account_id', 'status']);
                $table->index('plan_id');
                $table->index('stripe_subscription_id');
            });
        }

        if (! Schema::hasTable('account_subscription_addons')) {
            Schema::create('account_subscription_addons', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('account_subscription_id');
                $table->unsignedBigInteger('account_id');
                $table->unsignedBigInteger('addon_id');
                $table->integer('quantity')->default(1);
                $table->enum('billing_cycle', ['monthly', 'annual']);
                $table->enum('status', ['active', 'cancelled'])->default('active');
                $table->string('stripe_subscription_item_id')->nullable();
                $table->string('stripe_price_id')->nullable();
                $table->timestamps();

                $table->index(['account_id', 'status']);
                $table->index('account_subscription_id');
                $table->index('addon_id');
            });
        }

        if (! Schema::hasTable('property_participants')) {
            Schema::create('property_participants', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('account_id');
                $table->unsignedBigInteger('property_id');
                $table->unsignedBigInteger('user_id');
                $table->enum('participant_type', ['landlord', 'owner', 'tenant', 'contractor', 'property_manager', 'staff']);
                $table->enum('access_level', ['view', 'edit', 'full'])->default('view');
                $table->boolean('can_view_finance')->default(false);
                $table->boolean('can_view_documents')->default(false);
                $table->boolean('can_upload_documents')->default(false);
                $table->enum('status', ['active', 'inactive'])->default('active');
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();

                $table->unique(['account_id', 'property_id', 'user_id', 'participant_type'], 'property_participants_account_property_user_type_unique');
                $table->index(['account_id', 'user_id']);
                $table->index('property_id');
                $table->index('participant_type');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('property_participants');
        Schema::dropIfExists('account_subscription_addons');
        Schema::dropIfExists('account_subscriptions');
        Schema::dropIfExists('addons');
        Schema::dropIfExists('plans');
        Schema::dropIfExists('account_users');
        Schema::dropIfExists('accounts');
    }
};
