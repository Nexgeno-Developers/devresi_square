<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;

return new class extends Migration
{
    private array $permissions = [
        'view accounting reports',
        'manage gl accounts',
        'manage gl journals',
        'manage receipts',
        'manage bank reconciliation',
        'manage fixed assets',
        'close accounting periods',
    ];

    public function up(): void
    {
        foreach ($this->permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }
    }

    public function down(): void
    {
        Permission::whereIn('name', $this->permissions)->delete();
    }
};
