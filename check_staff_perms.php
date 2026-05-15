<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$staff = \App\Models\Staff::with(['user'])->latest()->first();
if (!$staff) { echo "No staff found\n"; exit; }

$role = \Spatie\Permission\Models\Role::find($staff->role_id);

echo "User: " . $staff->user->name . "\n";
echo "Role ID: " . $staff->role_id . "\n";
echo "Role: " . ($role?->name ?? 'none') . "\n";
echo "Direct permissions: " . $staff->user->getDirectPermissions()->pluck('name')->implode(', ') . "\n";
echo "Role permissions: " . ($role?->permissions->pluck('name')->implode(', ') ?? 'none') . "\n";
echo "All direct permissions:\n";
foreach ($staff->user->getDirectPermissions() as $p) {
    echo "  - " . $p->name . "\n";
}
echo "\nChecking 'view properties' specifically:\n";
$hasDirect = $staff->user->getDirectPermissions()->contains('name', 'view properties');
echo "Has as direct permission: " . ($hasDirect ? 'YES' : 'NO') . "\n";
echo "Can (combined check): " . ($staff->user->can('view properties') ? 'YES' : 'NO') . "\n";

// Check if Super Admin wildcard is in play
echo "Is Super Admin: " . ($staff->user->hasRole('Super Admin') ? 'YES' : 'NO') . "\n";
echo "Roles: " . $staff->user->getRoleNames()->implode(', ') . "\n";
