<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$staff = \App\Models\Staff::with(['user', 'role.permissions'])->latest()->first();
if (!$staff) { echo "No staff found\n"; exit; }

echo "User: " . $staff->user->name . "\n";
echo "Role: " . ($staff->role?->name ?? 'none') . "\n";
echo "Direct permissions: " . $staff->user->getDirectPermissions()->pluck('name')->implode(', ') . "\n";
echo "Role permissions: " . ($staff->role?->permissions->pluck('name')->implode(', ') ?? 'none') . "\n";
echo "Can 'view properties': " . ($staff->user->can('view properties') ? 'YES' : 'NO') . "\n";
echo "Can 'view all staffs': " . ($staff->user->can('view all staffs') ? 'YES' : 'NO') . "\n";
