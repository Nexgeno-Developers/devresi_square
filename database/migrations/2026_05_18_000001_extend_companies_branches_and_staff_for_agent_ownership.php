<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            if (! Schema::hasColumn('companies', 'owner_user_id')) {
                $table->foreignId('owner_user_id')->nullable()->after('id')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('companies', 'registration_number')) {
                $table->string('registration_number')->nullable()->after('name');
            }
            if (! Schema::hasColumn('companies', 'registered_address')) {
                $table->text('registered_address')->nullable()->after('registration_number');
            }
            if (! Schema::hasColumn('companies', 'communication_address')) {
                $table->text('communication_address')->nullable()->after('registered_address');
            }
            if (! Schema::hasColumn('companies', 'emails')) {
                $table->json('emails')->nullable()->after('communication_address');
            }
            if (! Schema::hasColumn('companies', 'phones')) {
                $table->json('phones')->nullable()->after('emails');
            }
            if (! Schema::hasColumn('companies', 'logo_path')) {
                $table->string('logo_path')->nullable()->after('phones');
            }
            if (! Schema::hasColumn('companies', 'stamp_path')) {
                $table->string('stamp_path')->nullable()->after('logo_path');
            }
            if (! Schema::hasColumn('companies', 'vat_number')) {
                $table->string('vat_number')->nullable()->after('stamp_path');
            }
            if (! Schema::hasColumn('companies', 'website')) {
                $table->string('website')->nullable()->after('vat_number');
            }
            if (! Schema::hasColumn('companies', 'social_media')) {
                $table->json('social_media')->nullable()->after('website');
            }
            if (! Schema::hasColumn('companies', 'services')) {
                $table->json('services')->nullable()->after('social_media');
            }
        });

        Schema::table('branches', function (Blueprint $table) {
            if (! Schema::hasColumn('branches', 'company_id')) {
                $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            }
            if (! Schema::hasColumn('branches', 'address_line_1')) {
                $table->string('address_line_1')->nullable()->after('name');
            }
            if (! Schema::hasColumn('branches', 'address_line_2')) {
                $table->string('address_line_2')->nullable()->after('address_line_1');
            }
            if (! Schema::hasColumn('branches', 'county')) {
                $table->string('county')->nullable()->after('city');
            }
            if (! Schema::hasColumn('branches', 'user_email')) {
                $table->string('user_email')->nullable();
            }
            if (! Schema::hasColumn('branches', 'user_phone')) {
                $table->string('user_phone')->nullable();
            }
            if (! Schema::hasColumn('branches', 'alternate_phone')) {
                $table->string('alternate_phone')->nullable();
            }
            if (! Schema::hasColumn('branches', 'alternate_email')) {
                $table->string('alternate_email')->nullable();
            }
            if (! Schema::hasColumn('branches', 'social_media')) {
                $table->json('social_media')->nullable();
            }
        });

        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'profile_picture')) {
                $table->string('profile_picture')->nullable()->after('phone');
            }
        });

        Schema::table('staff', function (Blueprint $table) {
            if (! Schema::hasColumn('staff', 'branch_id')) {
                $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            }
        });

        $permissions = [
            ['name' => 'manage own company', 'guard_name' => 'web'],
            ['name' => 'transfer company owner', 'guard_name' => 'web'],
            ['name' => 'download property brochure', 'guard_name' => 'web'],
        ];

        foreach ($permissions as $permission) {
            DB::table('permissions')->updateOrInsert(
                ['name' => $permission['name'], 'guard_name' => $permission['guard_name']],
                array_merge($permission, ['created_at' => now(), 'updated_at' => now()])
            );
        }

        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        cache()->forget('all_permissions');
    }

    public function down(): void
    {
        Schema::table('staff', function (Blueprint $table) {
            if (Schema::hasColumn('staff', 'branch_id')) {
                $table->dropConstrainedForeignId('branch_id');
            }
        });

        Schema::table('branches', function (Blueprint $table) {
            foreach (['address_line_1', 'address_line_2', 'county', 'alternate_phone', 'alternate_email', 'social_media'] as $column) {
                if (Schema::hasColumn('branches', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('companies', function (Blueprint $table) {
            if (Schema::hasColumn('companies', 'owner_user_id')) {
                $table->dropConstrainedForeignId('owner_user_id');
            }

            foreach ([
                'registration_number',
                'registered_address',
                'communication_address',
                'emails',
                'phones',
                'logo_path',
                'stamp_path',
                'vat_number',
                'website',
                'social_media',
                'services',
            ] as $column) {
                if (Schema::hasColumn('companies', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
